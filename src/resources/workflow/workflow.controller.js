import { PublisherModel } from '../publisher/publisher.model';
import { DataRequestModel } from '../datarequest/datarequest.model';
import { WorkflowModel } from './workflow.model';
import teamController from '../team/team.controller';
import helper from '../utilities/helper.util';

import moment from 'moment';
import _ from 'lodash';
import mongoose from 'mongoose';
import { UserModel } from '../user/user.model';
import emailGenerator from '../utilities/emailGenerator.util';

const hdrukEmail = `enquiry@healthdatagateway.org`;

	// GET api/v1/workflows/:id
	const getWorkflowById = async (req, res) => {
		try {
			// 1. Get the workflow from the database including the team members to check authorisation and the number of in-flight applications
			const workflow = await WorkflowModel.findOne({
				_id: req.params.id,
			}).populate([
				{
					path: 'publisher',
					select: 'team',
					populate: {
						path: 'team',
						select: 'members -_id',
					},
				},
				{
					path: 'steps.reviewers',
					model: 'User',
					select: '_id id firstname lastname',
				},
				{
					path: 'applications',
					select: 'aboutApplication',
					match: { applicationStatus: 'inReview' },
				},
			]);
			if (!workflow) {
				return res.status(404).json({ success: false });
			}
			// 2. Check the requesting user is a manager of the custodian team
			let { _id: userId } = req.user;
			let authorised = teamController.checkTeamPermissions(
				teamController.roleTypes.MANAGER,
				workflow.publisher.team.toObject(),
				userId
			);
			// 3. If not return unauthorised
			if (!authorised) {
				return res.status(401).json({ success: false });
			}
			// 4. Build workflow response
			let {
				active,
				_id,
				id,
				workflowName,
				version,
				steps,
				applications = [],
			} = workflow.toObject();
			applications = applications.map((app) => {
				let { aboutApplication, _id } = app;
				if(typeof aboutApplication === 'string') {
					aboutApplication = JSON.parse(aboutApplication) || {};
				}
				let { projectName = 'No project name' } = aboutApplication;
				return { projectName, _id };
			});
			// Set operation permissions
			let canDelete = applications.length === 0,
				canEdit = applications.length === 0;
			// 5. Return payload
			return res.status(200).json({
				success: true,
				workflow: {
					active,
					_id,
					id,
					workflowName,
					version,
					steps,
					applications,
					appCount: applications.length,
					canDelete,
					canEdit,
				},
			});
		} catch (err) {
			console.error(err.message);
			return res.status(500).json({
				success: false,
				message: 'An error occurred searching for the specified workflow',
			});
		}
	};

	// POST api/v1/workflows
	const createWorkflow = async (req, res) => {
		try {
			const { _id: userId } = req.user;
			// 1. Look at the payload for the publisher passed
			const { workflowName = '', publisher = '', steps = [] } = req.body;
			if (
				_.isEmpty(workflowName.trim()) ||
				_.isEmpty(publisher.trim()) ||
				_.isEmpty(steps)
			) {
				return res.status(400).json({
					success: false,
					message:
						'You must supply a workflow name, publisher, and at least one step definition to create a workflow',
				});
			}
			// 2. Look up publisher and team
			const publisherObj = await PublisherModel.findOne({
				_id: publisher,
			}).populate('team', 'members');
			if (!publisherObj) {
				return res.status(400).json({
					success: false,
					message:
						'You must supply a valid publisher to create the workflow against',
				});
			}
			// 3. Check the requesting user is a manager of the custodian team
			let authorised = teamController.checkTeamPermissions(
				teamController.roleTypes.MANAGER,
				publisherObj.team.toObject(),
				userId
			);

			// 4. Refuse access if not authorised
			if (!authorised) {
				return res
					.status(401)
					.json({ status: 'failure', message: 'Unauthorised' });
			}
			// 5. Create new workflow model
			const id = helper.generatedNumericId();
			let workflow = new WorkflowModel({
				id,
				workflowName,
				publisher,
				steps,
				createdBy: new mongoose.Types.ObjectId(userId),
			});
			// 6. Submit save
			workflow.save(function (err) {
				if (err) {
					console.error(err);
					return res.status(400).json({
						success: false,
						message: err.message,
					});
				} else {
					// 7. Return workflow payload
					return res.status(201).json({
						success: true,
						workflow,
					});
				}
			});
			// 7.  Send email to workflow phase reviewers
			// Get manager (workflow creator) details
			const manager = await UserModel.findById(userId);

			// Get details on the reviewers of each phase in the workflow
			const workflowReviewers = await Promise.all(steps.map(async(step, index) => {

				let phaseDetails = { phase: index+1, phaseName: step.stepName, reviewers: []};

				phaseDetails.reviewers = await Promise.all(step.reviewers.map(async(reviewer) => {
				  
					const reviewerDetails = {};
					const user = await UserModel.findById(reviewer).exec();
				
					reviewerDetails.firstName = user.firstname;
					reviewerDetails.lastName = user.lastname;
					reviewerDetails.email = user.email;
					reviewerDetails.phaseName = step.stepName;
					reviewerDetails.phase = index + 1; 
					
					return reviewerDetails;
				}));
				return phaseDetails;
			}));

			// Build email
			let { emailRecipients = [], subject = '', html = ''} = emailGenerator.generateNewWorkflowCreatedEmail(manager, workflowName, workflowReviewers);

			emailGenerator.sendEmail(
				emailRecipients,
				`${hdrukEmail}`,
				subject,
				html,
				false
			);
		} catch (err) {
			console.error(err.message);
			return res.status(500).json({
				success: false,
				message: 'An error occurred creating the workflow',
			});
		}
	};

	// PUT api/v1/workflows/:id
	const updateWorkflow = async (req, res) => {
		try {
			const { _id: userId } = req.user;
			const { id: workflowId } = req.params;
			// 1. Look up workflow
			let workflow = await WorkflowModel.findOne({
				_id: req.params.id,
			}).populate({
				path: 'publisher steps.reviewers',
				select: 'team',
				populate: {
					path: 'team',
					select: 'members -_id',
				},
			});
			if (!workflow) {
				return res.status(404).json({ success: false });
			}
			// 2. Check the requesting user is a manager of the custodian team
			let authorised = teamController.checkTeamPermissions(
				teamController.roleTypes.MANAGER,
				workflow.publisher.team.toObject(),
				userId
			);
			// 3. Refuse access if not authorised
			if (!authorised) {
				return res
					.status(401)
					.json({ status: 'failure', message: 'Unauthorised' });
			}
			// 4. Ensure there are no in-review DARs with this workflow
			const applications = await DataRequestModel.countDocuments({
				workflowId,
				applicationStatus: 'inReview',
			});
			if (applications > 0) {
				return res.status(400).json({
					success: false,
					message:
						'A workflow which is attached to applications currently in review cannot be edited',
				});
			}
			// 5. Edit workflow
			const { workflowName = '', steps = [] } = req.body;
			let isDirty = false;
			// Check if workflow name updated
			if (!_.isEmpty(workflowName)) {
				workflow.workflowName = workflowName;
				isDirty = true;
			} // Check if steps updated
			if (!_.isEmpty(steps)) {
				workflow.steps = steps;
				isDirty = true;
			} // Perform save if changes have been made
			if (isDirty) {
				workflow.save(async (err) => {
					if (err) {
						console.error(err);
						return res.status(400).json({
							success: false,
							message: err.message,
						});
					} else {
						// 7. Return workflow payload
						return res.status(204).json({
							success: true,
							workflow
						});
					}
				});
			} else {
				return res.status(200).json({
					success: true
				});
			}
		} catch (err) {
			console.error(err.message);
			return res.status(500).json({
				success: false,
				message: 'An error occurred editing the workflow',
			});
		}
	};

	// DELETE api/v1/workflows/:id
	const deleteWorkflow = async (req, res) => {
		try {
			const { _id: userId } = req.user;
			const { id: workflowId } = req.params;
			// 1. Look up workflow
			const workflow = await WorkflowModel.findOne({
				_id: req.params.id,
			}).populate({
				path: 'publisher steps.reviewers',
				select: 'team',
				populate: {
					path: 'team',
					select: 'members -_id',
				},
			});
			if (!workflow) {
				return res.status(404).json({ success: false });
			}
			// 2. Check the requesting user is a manager of the custodian team
			let authorised = teamController.checkTeamPermissions(
				teamController.roleTypes.MANAGER,
				workflow.publisher.team.toObject(),
				userId
			);
			// 3. Refuse access if not authorised
			if (!authorised) {
				return res
					.status(401)
					.json({ status: 'failure', message: 'Unauthorised' });
			}
			// 4. Ensure there are no in-review DARs with this workflow
			const applications = await DataRequestModel.countDocuments({
				workflowId,
				applicationStatus: 'inReview',
			});
			if (applications > 0) {
				return res.status(400).json({
					success: false,
					message:
						'A workflow which is attached to applications currently in review cannot be deleted',
				});
			}
			// 5. Delete workflow
			WorkflowModel.deleteOne({ _id: workflowId }, function (err) {
				if (err) {
					console.error(err);
					return res.status(400).json({
						success: false,
						message: 'An error occurred deleting the workflow',
					});
				} else {
					// 7. Return workflow payload
					return res.status(204).json({
						success: true,
					});
				}
			});
		} catch (err) {
			console.error(err.message);
			return res.status(500).json({
				success: false,
				message: 'An error occurred deleting the workflow',
			});
		}
	};

	const calculateStepDeadlineReminderDate = (step) => {
		// Extract deadline and reminder offset in days from step definition
		let { deadline, reminderOffset } = step;
		// Subtract SLA reminder offset
		let reminderPeriod = +deadline - +reminderOffset;
		return `P${reminderPeriod}D`;
	};

	const workflowStepContainsManager = (reviewers, team) => {
		let managerExists = false;
		// 1. Extract team members
		let { members } = team;
		// 2. Iterate through each reviewer to check if they are a manager of the team
		reviewers.forEach(reviewer => {
			// 3. Find the current user
			let userMember = members.find(
				(member) => member.memberid.toString() === reviewer.toString()
			);
			// 3. If the user was found check if they are a manager
			if (userMember) {
				let { roles } = userMember;
				if (roles.includes(roleTypes.MANAGER)) {
					managerExists = true;
				}
			}
		})
		return managerExists;
	};

	const buildNextStep = (userId, application, activeStepIndex, override) => {
		// Check the current position of the application within its assigned workflow
		const finalStep = activeStepIndex === application.workflow.steps.length -1;
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
			stepComplete
		}
		if(!finalStep) {
			// Extract the information for the next step defintion
			let { name: dataRequestPublisher } = application.publisherObj;
			let nextStep = application.workflow.steps[activeStepIndex+1];
			let reviewerList = nextStep.reviewers.map((reviewer) => reviewer._id.toString());
			let { stepName: dataRequestStepName } = nextStep;
			// Update Camunda payload with the next step information
			bpmContext = { 
				...bpmContext,
				dataRequestPublisher,
				dataRequestStepName,
				notifyReviewerSLA: calculateStepDeadlineReminderDate(
					nextStep
				),
				reviewerList
			};
		}
		return bpmContext;
	};

	const getWorkflowCompleted = (workflow = {}) => {
		let workflowCompleted = false;
		if (!_.isEmpty(workflow)) {
			let { steps } = workflow;
			workflowCompleted = steps.every((step) => step.completed);
		}
		return workflowCompleted;
	};

	const getActiveWorkflowStep = (workflow = {}) => {
		let activeStep = {};
		if (!_.isEmpty(workflow)) {
			let { steps } = workflow;
			activeStep = steps.find((step) => {
				return step.active;
			});
		}
		return activeStep;
	};

	const getStepReviewers = (step = {}) => {
		let stepReviewers = [];
		// Attempt to get step reviewers if workflow passed
		if (!_.isEmpty(step)) {
			// Get active reviewers
			if(step) {
				({ reviewers: stepReviewers } = step);
			}
		}
		return stepReviewers;
	};

	const getRemainingReviewers = (Step = {}, users) => {
		let { reviewers = [], recommendations = []} = Step;
		let remainingActioners = reviewers.filter(
			(reviewer) =>
				!recommendations.some(
					(rec) => rec.reviewer.toString() === reviewer._id.toString()
				)
		);
		remainingActioners = [...users]
			.filter((user) =>
				remainingActioners.some(
					(actioner) => actioner._id.toString() === user._id.toString()
				)
			);

		return remainingActioners;
	}

	const getActiveStepStatus = (activeStep, users = [], userId = '') => {
		let reviewStatus = '',
			deadlinePassed = false,
			remainingActioners = [],
			decisionMade = false,
			decisionComments = '',
			decisionApproved = false,
			decisionDate = '',
			decisionStatus = '';
		let {
			stepName,
			deadline,
			startDateTime,
			reviewers = [],
			recommendations = [],
			sections = [],
		} = activeStep;
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
		remainingActioners = reviewers.filter(
			(reviewer) =>
				!recommendations.some(
					(rec) => rec.reviewer.toString() === reviewer._id.toString()
				)
		);
		remainingActioners = users
			.filter((user) =>
				remainingActioners.some(
					(actioner) => actioner._id.toString() === user._id.toString()
				)
			)
			.map((user) => {
				let isCurrentUser = user._id.toString() === userId.toString();
				return `${user.firstname} ${user.lastname}${isCurrentUser ? ` (you)`:``}`;
			});
	
		let isReviewer = reviewers.some(
			(reviewer) => reviewer._id.toString() === userId.toString()
		);
		let hasRecommended = recommendations.some(
			(rec) => rec.reviewer.toString() === userId.toString()
		);
	
		decisionMade = isReviewer && hasRecommended;
	
		if (decisionMade) {
			decisionStatus = 'Decision made for this phase';
		} else if (isReviewer) {
			decisionStatus = 'Decision required';
		} else {
			decisionStatus = '';
		}
	
		if (hasRecommended) {
			let recommendation = recommendations.find(
				(rec) => rec.reviewer.toString() === userId.toString()
			);
			({
				comments: decisionComments,
				approved: decisionApproved,
				createdDate: decisionDate,
			} = recommendation);
		}
	
		let reviewPanels = sections
			.map((section) => helper.darPanelMapper[section])
			.join(', ');
	
		return {
			stepName,
			remainingActioners: remainingActioners.join(', '),
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
	};
	
	const getWorkflowStatus = (application) => {
		let workflowStatus = {};
		let { workflow = {} } = application;
		if (!_.isEmpty(workflow)) {
			let { workflowName, steps } = workflow;
			// Find the active step in steps
			let activeStep = getActiveWorkflowStep(workflow);
			let activeStepIndex = steps.findIndex((step) => {
				return step.active === true;
			});
			if (activeStep) {
				let {
					reviewStatus,
					deadlinePassed,
				} = getActiveStepStatus(activeStep);
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
					sections: [...item.sections].map(
						(section) => helper.darPanelMapper[section]
					),
				};
				arr.push(step);
				return arr;
			}, []);
	
			workflowStatus = {
				workflowName,
				steps: formattedSteps,
				isCompleted: getWorkflowCompleted(workflow),
			};
		}
		return workflowStatus;
	};

	const getReviewStatus = (application, userId) => {
		let inReviewMode = false,
			reviewSections = [],
			isActiveStepReviewer = false,
			hasRecommended = false;
		// Get current application status
		let { applicationStatus } = application;
		// Check if the current user is a reviewer on the current step of an attached workflow
		let { workflow = {} } = application;
		if (!_.isEmpty(workflow)) {
			let { steps } = workflow;
			let activeStep = steps.find((step) => {
				return step.active === true;
			});
			if (activeStep) {
				isActiveStepReviewer = activeStep.reviewers.some(
					(reviewer) => reviewer._id.toString() === userId.toString()
				);
				reviewSections = [...activeStep.sections];
	
				let { recommendations = [] } = activeStep;
				if (!_.isEmpty(recommendations)) {
					hasRecommended = recommendations.some(
						(rec) => rec.reviewer.toString() === userId.toString()
					);
				}
			}
		}
		// Return active review mode if conditions apply
		if (applicationStatus === 'inReview' && isActiveStepReviewer) {
			inReviewMode = true;
		}
	
		return { inReviewMode, reviewSections, hasRecommended };
	};
	
	const getWorkflowEmailContext = (accessRecord, workflow, relatedStepIndex) => {
		// Extract workflow email variables
		const { dateReviewStart = '' } = accessRecord;
		const { workflowName, steps } = workflow;
		const { stepName, startDateTime = '', endDateTime = '', completed = false, deadline: stepDeadline = 0, reminderOffset = 0 } = steps[relatedStepIndex];
		const stepReviewers = getStepReviewers(steps[relatedStepIndex]);
		const reviewerNames = [...stepReviewers].map((reviewer) => `${reviewer.firstname} ${reviewer.lastname}`).join(', ');
		const reviewSections = [...steps[relatedStepIndex].sections].map((section) => helper.darPanelMapper[section]).join(', ');
		const stepReviewerUserIds = [...stepReviewers].map((user) => user.id);
		const currentDeadline = stepDeadline === 0 ? 'No deadline specified' : moment().add(stepDeadline, 'days');
		let nextStepName = '', nextReviewerNames = '', nextReviewSections = '', duration = '', totalDuration = '', nextDeadline = '', dateDeadline = '', deadlineElapsed = false, deadlineApproaching = false, remainingReviewers = [], remainingReviewerUserIds = [];

		// Calculate duration for step if it is completed
		if(completed) {
			if(!_.isEmpty(startDateTime.toString()) && !_.isEmpty(endDateTime.toString())) {
				duration = moment(endDateTime).diff(moment(startDateTime), 'days');
				duration = duration === 0 ? `Same day` : duration === 1 ? `1 day` : `${duration} days`;
			}
		} else {
			//If related step is not completed, check if deadline has elapsed or is approaching
			if(!_.isEmpty(startDateTime.toString()) && stepDeadline != 0) {
				dateDeadline = moment(startDateTime).add(stepDeadline, 'days');
				deadlineElapsed = moment().isAfter(dateDeadline, 'second');

				// If deadline is not elapsed, check if it is within SLA period
				if(!deadlineElapsed && reminderOffset !== 0) {
					let deadlineReminderDate = moment(dateDeadline).subtract(reminderOffset, 'days');
					deadlineApproaching = moment().isAfter(deadlineReminderDate, 'second');
				}
			}
			// Find reviewers of the current incomplete phase
			let accessRecordObj = accessRecord.toObject();
			if(_.has(accessRecordObj, 'publisherObj.team.users')){
				let { publisherObj: { team: { users = [] } } } = accessRecordObj;
				remainingReviewers = getRemainingReviewers(steps[relatedStepIndex], users);
				remainingReviewerUserIds = [...remainingReviewers].map((user) => user.id);
			}
		}

		// Check if there is another step after the current related step
		if(relatedStepIndex + 1 === steps.length) {
			// If workflow completed
			nextStepName = 'No next step';
			// Calculate total duration for workflow
			if(steps[relatedStepIndex].completed && !_.isEmpty(dateReviewStart.toString())){
				totalDuration = moment().diff(moment(dateReviewStart), 'days');
				totalDuration = totalDuration === 0 ? `Same day` : duration === 1 ? `1 day` : `${duration} days`;
			}
		} else {
			// Get details of next step if this is not the final step
			({ stepName: nextStepName } = steps[relatedStepIndex + 1]);
			let nextStepReviewers = getStepReviewers(steps[relatedStepIndex + 1]);
			nextReviewerNames = [...nextStepReviewers].map((reviewer) => `${reviewer.firstname} ${reviewer.lastname}`).join(', ');
			nextReviewSections = [...steps[relatedStepIndex + 1].sections].map((section) => helper.darPanelMapper[section]).join(', ');
			let { deadline = 0 } = steps[relatedStepIndex + 1];
			nextDeadline = deadline === 0 ? 'No deadline specified' : moment().add(deadline, 'days');
		}
		return { 
			workflowName, 
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
			remainingReviewerUserIds
		};
	};

export default {
	getWorkflowById: getWorkflowById,
	createWorkflow: createWorkflow,
	updateWorkflow: updateWorkflow,
	deleteWorkflow: deleteWorkflow,
	calculateStepDeadlineReminderDate: calculateStepDeadlineReminderDate,
	workflowStepContainsManager: workflowStepContainsManager,
	buildNextStep: buildNextStep,
	getWorkflowCompleted: getWorkflowCompleted,
	getActiveWorkflowStep: getActiveWorkflowStep,
	getStepReviewers: getStepReviewers,
	getActiveStepStatus: getActiveStepStatus,
	getWorkflowStatus: getWorkflowStatus,
	getReviewStatus: getReviewStatus,
	getWorkflowEmailContext: getWorkflowEmailContext
};