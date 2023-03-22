import moment from 'moment';
import { isEmpty, has } from 'lodash';

import teamController from '../team/team.controller';
import constants from '../utilities/constants.util';
import emailGenerator from '../utilities/emailGenerator.util';
import notificationBuilder from '../utilities/notificationBuilder';

const bpmController = require('../bpmnworkflow/bpmnworkflow.controller');

export default class WorkflowService {
	constructor(workflowRepository) {
		this.workflowRepository = workflowRepository;
	}

	getApplicationWorkflowStatusForUser(accessRecord, requestingUserObjectId) {
		// Set the review mode if user is a custodian reviewing the current step
		let { inReviewMode, reviewSections, hasRecommended } = this.getReviewStatus(accessRecord, requestingUserObjectId);
		// Get the workflow/voting status
		let workflow = this.getWorkflowStatus(accessRecord);
		let isManager = false;
		// Check if the current user can override the current step
		if (has(accessRecord, 'publisherObj.team')) {
			const { team } = accessRecord.publisherObj;
			isManager = teamController.checkTeamPermissions(constants.roleTypes.MANAGER, team, requestingUserObjectId);
			// Set the workflow override capability if there is an active step and user is a manager
			if (!isEmpty(workflow)) {
				workflow.canOverrideStep = !workflow.isCompleted && isManager;
			}
		}
		return { inReviewMode, reviewSections, hasRecommended, isManager, workflow };
	}

	async getWorkflowsByPublisher(id) {
		const workflows = await this.workflowRepository.getWorkflowsByPublisher(id);

		const formattedWorkflows = [...workflows].map(workflow => {
			let { active, _id, id, workflowName, version, applications = [], publisher } = workflow;
			const formattedSteps = this.formatWorkflowSteps(workflow, 'displaySections');
			return {
				active,
				_id,
				id,
				workflowName,
				version,
				steps: formattedSteps,
				appCount: applications.length,
				canDelete: applications.length === 0,
				canEdit: applications.length === 0,
				publisher,
			};
		});

		return formattedWorkflows;
	}

	async assignWorkflowToApplication(accessRecord, workflowId) {
		return this.workflowRepository.assignWorkflowToApplication(accessRecord, workflowId);
	}

	getWorkflowById(id) {
		return this.workflowRepository.getWorkflowById(id);
	}

	getWorkflowDetails(accessRecord, requestingUserId) {
		if (!has(accessRecord, 'publisherObj.team.members')) return accessRecord;

		let { workflow = {} } = accessRecord;

		const activeStep = this.getActiveWorkflowStep(workflow);
		accessRecord = this.getRemainingActioners(accessRecord, activeStep, requestingUserId);

		if (isEmpty(workflow)) return accessRecord;

		accessRecord.workflowName = workflow.workflowName;
		accessRecord.workflowCompleted = this.getWorkflowCompleted(workflow);

		if (isEmpty(activeStep)) return accessRecord;

		const activeStepDetails = this.getActiveStepStatus(activeStep, requestingUserId);
		accessRecord = { ...accessRecord, ...activeStepDetails };

		accessRecord = this.setActiveStepReviewStatus(accessRecord);

		accessRecord.workflow.steps = this.formatWorkflowSteps(workflow, 'sections');

		return accessRecord;
	}

	formatWorkflowSteps(workflow, sectionsKey) {
		// Set review section to display format
		const { steps = [] } = workflow;
		let formattedSteps = [...steps].reduce((arr, item) => {
			let step = {
				...item,
				[sectionsKey]: [...item.sections].map(section => constants.darPanelMapper[section]),
			};
			arr.push(step);
			return arr;
		}, []);
		return [...formattedSteps];
	}

	setActiveStepReviewStatus(accessRecord) {
		const { workflow } = accessRecord;
		if (!workflow) return '';

		let activeStepIndex = workflow.steps.findIndex(step => {
			return step.active === true;
		});

		if (activeStepIndex === -1) return '';

		accessRecord.workflow.steps[activeStepIndex].reviewStatus = accessRecord.reviewStatus;

		return accessRecord;
	}

	getRemainingActioners(accessRecord, activeStep = {}, requestingUserId) {
		let {
			workflow = {},
			applicationStatus,
			publisherObj: { team },
		} = accessRecord;

		if (
			applicationStatus === constants.applicationStatuses.SUBMITTED ||
			(applicationStatus === constants.applicationStatuses.INREVIEW && isEmpty(workflow))
		) {
			accessRecord.remainingActioners = this.getReviewManagers(team, requestingUserId).join(', ');
		} else if (!isEmpty(workflow) && isEmpty(activeStep) && applicationStatus === constants.applicationStatuses.INREVIEW) {
			accessRecord.remainingActioners = this.getReviewManagers(team, requestingUserId).join(', ');
			accessRecord.reviewStatus = 'Final decision required';
		} else {
			accessRecord.remainingActioners = this.getRemainingReviewerNames(activeStep, team.users, requestingUserId);
		}

		return accessRecord;
	}

	getReviewManagers(team, requestingUserId) {
		const { members = [], users = [] } = team;
		const managers = members.filter(mem => {
			return mem.roles.includes('manager');
		});
		return users
			.filter(user => managers.some(manager => manager.memberid.toString() === user._id.toString()))
			.map(user => {
				const isCurrentUser = user._id.toString() === requestingUserId.toString();
				return `${user.firstname} ${user.lastname}${isCurrentUser ? ` (you)` : ``}`;
			});
	}

	async createNotifications(context, type = '') {
		if (!isEmpty(type)) {
			// local variables set here
			let custodianManagers = [],
				managerUserIds = [],
				options = {},
				html = '';

			// deconstruct context
			let { publisherObj, workflow = {}, actioner = '' } = context;

			custodianManagers = teamController.getTeamMembersByRole(publisherObj, 'All');
			if (publisherObj.notifications[0].optIn) {
				publisherObj.notifications[0].subscribedEmails.map(teamEmail => {
					custodianManagers.push({ email: teamEmail });
				});
			}
			managerUserIds = custodianManagers.map(user => user.id);
			let { workflowName = 'Workflow Title', _id, steps, createdAt } = workflow;
			const action = type.replace('Workflow', '').toLowerCase();
			options = {
				actioner,
				workflowName,
				_id,
				steps,
				createdAt,
				action,
			};

			// switch over types
			switch (type) {
				case constants.notificationTypes.WORKFLOWCREATED:
					// 1. Get managers for publisher
					// 4. Create notifications for the managers only
					await notificationBuilder.triggerNotificationMessage(
						managerUserIds,
						`A new workflow of ${workflowName} has been created`,
						'workflow',
						_id
					);
					// 5. Generate the email
					html = await emailGenerator.generateWorkflowActionEmail(options);
					// 6. Send email to custodian managers only within the team
					await emailGenerator.sendEmail(custodianManagers, constants.hdrukEmail, `A Workflow has been created`, html, false);
					break;

				case constants.notificationTypes.WORKFLOWUPDATED:
					// 1. Get managers for publisher
					// 4. Create notifications for the managers only
					await notificationBuilder.triggerNotificationMessage(
						managerUserIds,
						`A workflow of ${workflowName} has been updated`,
						'workflow',
						_id
					);
					// 5. Generate the email
					html = await emailGenerator.generateWorkflowActionEmail(options);
					// 6. Send email to custodian managers only within the team
					await emailGenerator.sendEmail(custodianManagers, constants.hdrukEmail, `A Workflow has been updated`, html, false);
					break;

				case constants.notificationTypes.WORKFLOWDELETED:
					// 1. Get managers for publisher
					// 4. Create notifications for the managers only
					await notificationBuilder.triggerNotificationMessage(
						managerUserIds,
						`A workflow of ${workflowName} has been deleted`,
						'workflow',
						_id
					);
					// 5. Generate the email
					html = await emailGenerator.generateWorkflowActionEmail(options);
					// 6. Send email to custodian managers only within the team
					await emailGenerator.sendEmail(custodianManagers, constants.hdrukEmail, `A Workflow has been deleted`, html, false);
					break;
			}
		}
	}

	calculateStepDeadlineReminderDate(step) {
		// Extract deadline and reminder offset in days from step definition
		let { deadline, reminderOffset } = step;
		// Subtract SLA reminder offset
		let reminderPeriod = +deadline - +reminderOffset;
		return `P${reminderPeriod}D`;
	}

	buildNextStep(userId, application, activeStepIndex, override) {
		// Check the current position of the application within its assigned workflow
		const finalStep = activeStepIndex === application.workflow.steps.length - 1;
		const requiredReviews = application.workflow.steps[activeStepIndex].reviewers.length;
		const completedReviews = application.workflow.steps[activeStepIndex].recommendations.length;
		const stepComplete = completedReviews === requiredReviews;
		// Establish base payload for Camunda
		// (1) phaseApproved is passed as true when the manager is overriding the current step/phase
		//		this short circuits the review process in the workflow and closes any remaining user tasks
		//		i.e. reviewers within the active step OR when the last reviewer in the step submits a vote
		// (2) managerApproved is passed as true when the manager is approving the entire application
		//		from any point within the review process
		// (3) finalPhaseApproved is passed as true when the final step is completed naturally through all
		//		reviewers casting their votes
		let bpmContext = {
			businessKey: application._id,
			dataRequestUserId: userId.toString(),
			managerApproved: override,
			phaseApproved: (override && !finalStep) || stepComplete,
			finalPhaseApproved: finalStep,
			stepComplete,
		};
		if (!finalStep) {
			// Extract the information for the next step defintion
			let { name: dataRequestPublisher } = application.publisherObj;
			let nextStep = application.workflow.steps[activeStepIndex + 1];
			let reviewerList = nextStep.reviewers.map(reviewer => reviewer._id.toString());
			let { stepName: dataRequestStepName } = nextStep;
			// Update Camunda payload with the next step information
			bpmContext = {
				...bpmContext,
				dataRequestPublisher,
				dataRequestStepName,
				notifyReviewerSLA: this.calculateStepDeadlineReminderDate(nextStep),
				reviewerList,
			};
		}
		return bpmContext;
	}

	getWorkflowCompleted(workflow = {}) {
		let workflowCompleted = false;
		if (!isEmpty(workflow)) {
			let { steps } = workflow;
			workflowCompleted = steps.every(step => step.completed);
		}
		return workflowCompleted;
	}

	getActiveWorkflowStep(workflow = {}) {
		let activeStep = {};
		if (!isEmpty(workflow)) {
			let { steps } = workflow;
			activeStep = steps.find(step => {
				return step.active;
			});
		}
		return activeStep;
	}

	getStepReviewers(step = {}) {
		let stepReviewers = [];
		// Attempt to get step reviewers if workflow passed
		if (!isEmpty(step)) {
			// Get active reviewers
			if (step) {
				({ reviewers: stepReviewers } = step);
			}
		}
		return stepReviewers;
	}

	getRemainingReviewers(step = {}, users) {
		let { reviewers = [], recommendations = [] } = step;
		let remainingActioners = reviewers.filter(
			reviewer => !recommendations.some(rec => rec.reviewer.toString() === reviewer._id.toString())
		);
		remainingActioners = [...users].filter(user => remainingActioners.some(actioner => actioner._id.toString() === user._id.toString()));

		return remainingActioners;
	}

	getRemainingReviewerNames(step = {}, users, requestingUserId) {
		if (isEmpty(step)) return '';
		let remainingActioners = this.getRemainingReviewers(step, users);

		if (isEmpty(remainingActioners)) return '';

		let remainingReviewerNames = remainingActioners.map(user => {
			let isCurrentUser = user._id.toString() === requestingUserId.toString();
			return `${user.firstname} ${user.lastname}${isCurrentUser ? ` (you)` : ``}`;
		});

		return remainingReviewerNames.join(', ');
	}

	getActiveStepStatus(activeStep, userId = '') {
		let reviewStatus = '',
			deadlinePassed = false,
			decisionMade = false,
			decisionComments = '',
			decisionApproved = false,
			decisionDate = '',
			decisionStatus = '';
		let { stepName = '', deadline, startDateTime, reviewers = [], recommendations = [], sections = [] } = activeStep;
		let deadlineDate = moment(startDateTime).add(deadline, 'days');
		let diff = parseInt(deadlineDate.diff(new Date(), 'days'));
		if (diff > 0) {
			reviewStatus = `Deadline in ${diff} days`;
		} else if (diff < 0) {
			reviewStatus = `Deadline was ${Math.abs(diff)} days ago`;
			deadlinePassed = true;
		} else {
			reviewStatus = `Deadline is today`;
		}

		let isReviewer = reviewers.some(reviewer => reviewer._id.toString() === userId.toString());
		let hasRecommended = recommendations.some(rec => rec.reviewer.toString() === userId.toString());

		decisionMade = isReviewer && hasRecommended;

		if (decisionMade) {
			decisionStatus = 'Decision made for this phase';
		} else if (isReviewer) {
			decisionStatus = 'Decision required';
		} else {
			decisionStatus = '';
		}

		if (hasRecommended) {
			let recommendation = recommendations.find(rec => rec.reviewer.toString() === userId.toString());
			({ comments: decisionComments, approved: decisionApproved, createdDate: decisionDate } = recommendation);
		}

		let reviewPanels = sections.map(section => constants.darPanelMapper[section]).join(', ');

		return {
			stepName,
			deadlinePassed,
			isReviewer,
			reviewStatus,
			decisionMade,
			decisionApproved,
			decisionDate,
			decisionStatus,
			decisionComments,
			reviewPanels,
		};
	}

	getWorkflowStatus(application) {
		let workflowStatus = {};
		let { workflow = {} } = application;
		if (!isEmpty(workflow)) {
			let { workflowName, steps } = workflow;
			// Find the active step in steps
			let activeStep = this.getActiveWorkflowStep(workflow);
			let activeStepIndex = steps.findIndex(step => {
				return step.active === true;
			});
			if (activeStep) {
				let { reviewStatus, deadlinePassed } = this.getActiveStepStatus(activeStep);
				//Update active step with review status
				steps[activeStepIndex] = {
					...steps[activeStepIndex],
					reviewStatus,
					deadlinePassed,
				};
			}
			//Update steps with user friendly review sections
			let formattedSteps = [...steps].reduce((arr, item) => {
				let step = {
					...item,
					sections: [...item.sections].map(section => constants.darPanelMapper[section]),
				};
				arr.push(step);
				return arr;
			}, []);

			workflowStatus = {
				workflowName,
				steps: formattedSteps,
				isCompleted: this.getWorkflowCompleted(workflow),
			};
		}
		return workflowStatus;
	}

	getReviewStatus(application, userId) {
		let inReviewMode = false,
			reviewSections = [],
			isActiveStepReviewer = false,
			hasRecommended = false;
		// Get current application status
		let { applicationStatus } = application;
		// Check if the current user is a reviewer on the current step of an attached workflow
		let { workflow = {} } = application;
		if (!isEmpty(workflow)) {
			let { steps } = workflow;
			let activeStep = steps.find(step => {
				return step.active === true;
			});
			if (activeStep) {
				isActiveStepReviewer = activeStep.reviewers.some(reviewer => reviewer._id.toString() === userId.toString());
				reviewSections = [...activeStep.sections];

				let { recommendations = [] } = activeStep;
				if (!isEmpty(recommendations)) {
					hasRecommended = recommendations.some(rec => rec.reviewer.toString() === userId.toString());
				}
			}
		}
		// Return active review mode if conditions apply
		if (applicationStatus === 'inReview' && isActiveStepReviewer) {
			inReviewMode = true;
		}

		return { inReviewMode, reviewSections, hasRecommended };
	}

	getWorkflowEmailContext(accessRecord, relatedStepIndex = 0) {
		// Extract workflow email variables
		const { dateReviewStart = '', workflow = {} } = accessRecord;
		const { workflowName, steps } = workflow;
		const {
			stepName,
			startDateTime = '',
			endDateTime = '',
			completed = false,
			deadline: stepDeadline = 0,
			reminderOffset = 0,
		} = steps[relatedStepIndex];
		const stepReviewers = this.getStepReviewers(steps[relatedStepIndex]);
		const reviewerNames = [...stepReviewers].map(reviewer => `${reviewer.firstname} ${reviewer.lastname}`).join(', ');
		const reviewSections = [...steps[relatedStepIndex].sections].map(section => constants.darPanelMapper[section]).join(', ');
		const stepReviewerUserIds = [...stepReviewers].map(user => user.id);
		const currentDeadline = stepDeadline === 0 ? 'No deadline specified' : moment().add(stepDeadline, 'days');
		let nextStepName = '',
			nextReviewerNames = '',
			nextReviewSections = '',
			duration = '',
			totalDuration = '',
			nextDeadline = '',
			dateDeadline = '',
			deadlineElapsed = false,
			deadlineApproaching = false,
			remainingReviewers = [],
			remainingReviewerUserIds = [];

		// Calculate duration for step if it is completed
		if (completed) {
			if (!isEmpty(startDateTime.toString()) && !isEmpty(endDateTime.toString())) {
				duration = moment(endDateTime).diff(moment(startDateTime), 'days');
				duration = duration === 0 ? `Same day` : duration === 1 ? `1 day` : `${duration} days`;
			}
		} else {
			//If related step is not completed, check if deadline has elapsed or is approaching
			if (!isEmpty(startDateTime.toString()) && stepDeadline != 0) {
				dateDeadline = moment(startDateTime).add(stepDeadline, 'days');
				deadlineElapsed = moment().isAfter(dateDeadline, 'second');

				// If deadline is not elapsed, check if it is within SLA period
				if (!deadlineElapsed && reminderOffset !== 0) {
					let deadlineReminderDate = moment(dateDeadline).subtract(reminderOffset, 'days');
					deadlineApproaching = moment().isAfter(deadlineReminderDate, 'second');
				}
			}
			// Find reviewers of the current incomplete phase
			let accessRecordObj = accessRecord.toObject();
			if (has(accessRecordObj, 'publisherObj.team.users')) {
				let {
					publisherObj: {
						team: { users = [] },
					},
				} = accessRecordObj;
				remainingReviewers = this.getRemainingReviewers(steps[relatedStepIndex], users);
				remainingReviewerUserIds = [...remainingReviewers].map(user => user.id);
			}
		}

		// Check if there is another step after the current related step
		if (relatedStepIndex + 1 === steps.length) {
			// If workflow completed
			nextStepName = 'No next step';
			// Calculate total duration for workflow
			if (steps[relatedStepIndex].completed && !isEmpty(dateReviewStart.toString())) {
				totalDuration = moment().diff(moment(dateReviewStart), 'days');
				if (totalDuration === 0) {
					totalDuration = `Same day`;
				} else {
					if (duration === 1) {
						totalDuration = `1 day`;
					} else {
						totalDuration = `${duration} days`;
					}
				}
			}
		} else {
			// Get details of next step if this is not the final step
			({ stepName: nextStepName } = steps[relatedStepIndex + 1]);
			let nextStepReviewers = this.getStepReviewers(steps[relatedStepIndex + 1]);
			nextReviewerNames = [...nextStepReviewers].map(reviewer => `${reviewer.firstname} ${reviewer.lastname}`).join(', ');
			nextReviewSections = [...steps[relatedStepIndex + 1].sections].map(section => constants.darPanelMapper[section]).join(', ');
			let { deadline = 0 } = steps[relatedStepIndex + 1];
			nextDeadline = deadline === 0 ? 'No deadline specified' : moment().add(deadline, 'days');
		}
		return {
			workflowName,
			steps,
			stepName,
			startDateTime,
			endDateTime,
			stepReviewers,
			duration,
			totalDuration,
			reviewerNames,
			stepReviewerUserIds,
			reviewSections,
			currentDeadline,
			nextStepName,
			nextReviewerNames,
			nextReviewSections,
			nextDeadline,
			dateDeadline,
			deadlineElapsed,
			deadlineApproaching,
			remainingReviewers,
			remainingReviewerUserIds,
		};
	}

	startWorkflow(accessRecord, requestingUserObjectId) {
		const {
			publisherObj: { name: dataRequestPublisher },
			_id,
			workflow,
		} = accessRecord;
		const reviewerList = workflow.steps[0].reviewers.map(reviewer => reviewer._id.toString());
		const bpmContext = {
			businessKey: _id,
			dataRequestStatus: constants.applicationStatuses.INREVIEW,
			dataRequestUserId: requestingUserObjectId.toString(),
			dataRequestPublisher,
			dataRequestStepName: workflow.steps[0].stepName,
			notifyReviewerSLA: this.calculateStepDeadlineReminderDate(workflow.steps[0]),
			reviewerList,
		};
		bpmController.postStartStepReview(bpmContext);
	}
}
