import { DataRequestModel } from './datarequest.model';
import { WorkflowModel } from '../workflow/workflow.model';
import { Data as ToolModel } from '../tool/data.model';
import { DataRequestSchemaModel } from './datarequest.schemas.model';
import { UserModel } from '../user/user.model';

import teamController from '../team/team.controller';
import workflowController from '../workflow/workflow.controller';
import datarequestUtil from './utils/datarequest.util';
import notificationBuilder from '../utilities/notificationBuilder';

import emailGenerator from '../utilities/emailGenerator.util';
import helper from '../utilities/helper.util';
import dynamicForm from '../utilities/dynamicForms/dynamicForm.util';
import constants from '../utilities/constants.util';
import { processFile, getFile, fileStatus } from '../utilities/cloudStorage.util';
import _ from 'lodash';
import inputSanitizer from '../utilities/inputSanitizer';

import moment from 'moment';
import mongoose from 'mongoose';

const amendmentController = require('./amendment/amendment.controller');
const bpmController = require('../bpmnworkflow/bpmnworkflow.controller');

module.exports = {
	//GET api/v1/data-access-request
	getAccessRequestsByUser: async (req, res) => {
		try {
			// 1. Deconstruct the parameters passed
			let { id: userId } = req.user;
			let { query = {} } = req;

			// 2. Find all data access request applications created with multi dataset version
			let applications = await DataRequestModel.find({
				$and: [{ ...query }, { $or: [{ userId: parseInt(userId) }, { authorIds: userId }] }],
			})
				.select("-jsonSchema -questionAnswers -files")
				.populate('datasets mainApplicant')
				.lean();

			// 3. Append project name and applicants
			let modifiedApplications = [...applications]
				.map(app => {
					return module.exports.createApplicationDTO(app, constants.userTypes.APPLICANT);
				})
				.sort((a, b) => b.updatedAt - a.updatedAt);

			// 4. Calculate average decision time across submitted applications
			let avgDecisionTime = module.exports.calculateAvgDecisionTime(applications);

			// 5. Return payload
			return res.status(200).json({
				success: true,
				data: modifiedApplications,
				avgDecisionTime,
				canViewSubmitted: true,
			});
		} catch (err) {
			console.error(err.message);
			return res.status(500).json({
				success: false,
				message: 'An error occurred searching for user applications',
			});
		}
	},

	//GET api/v1/data-access-request/:requestId
	getAccessRequestById: async (req, res) => {
		try {
			// 1. Get dataSetId from params
			let {
				params: { requestId },
			} = req;
			// 2. Find the matching record and include attached datasets records with publisher details
			let accessRecord = await DataRequestModel.findOne({
				_id: requestId,
			}).populate([
				{ path: 'mainApplicant', select: 'firstname lastname -id' },
				{
					path: 'datasets dataset authors',
					populate: { path: 'publisher', populate: { path: 'team' } },
				},
				{ path: 'workflow.steps.reviewers', select: 'firstname lastname' },
				{ path: 'files.owner', select: 'firstname lastname' },
			]);
			// 3. If no matching application found, return 404
			if (!accessRecord) {
				return res.status(404).json({ status: 'error', message: 'Application not found.' });
			} else {
				accessRecord = accessRecord.toObject();
			}
			// 4. Ensure single datasets are mapped correctly into array
			if (_.isEmpty(accessRecord.datasets)) {
				accessRecord.datasets = [accessRecord.dataset];
			}
			// 5. Check if requesting user is custodian member or applicant/contributor
			let { authorised, userType } = datarequestUtil.getUserPermissionsForApplication(accessRecord, req.user.id, req.user._id);
			let readOnly = true;
			if (!authorised) {
				return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
			}
			// 6. Set edit mode for applicants who have not yet submitted
			if (userType === constants.userTypes.APPLICANT && accessRecord.applicationStatus === constants.applicationStatuses.INPROGRESS) {
				readOnly = false;
			}
			// 7. Count unsubmitted amendments
			let countUnsubmittedAmendments = amendmentController.countUnsubmittedAmendments(accessRecord, userType);
			// 8. Set the review mode if user is a custodian reviewing the current step
			let { inReviewMode, reviewSections, hasRecommended } = workflowController.getReviewStatus(accessRecord, req.user._id);
			// 9. Get the workflow/voting status
			let workflow = workflowController.getWorkflowStatus(accessRecord);
			let isManager = false;
			// 10. Check if the current user can override the current step
			if (_.has(accessRecord.datasets[0], 'publisher.team')) {
				isManager = teamController.checkTeamPermissions(constants.roleTypes.MANAGER, accessRecord.datasets[0].publisher.team, req.user._id);
				// Set the workflow override capability if there is an active step and user is a manager
				if (!_.isEmpty(workflow)) {
					workflow.canOverrideStep = !workflow.isCompleted && isManager;
				}
			}
			// 11. Update json schema and question answers with modifications since original submission
			accessRecord.questionAnswers = JSON.parse(accessRecord.questionAnswers);
			accessRecord.jsonSchema = JSON.parse(accessRecord.jsonSchema);
			accessRecord = amendmentController.injectAmendments(accessRecord, userType, req.user);
			// 12. Determine the current active party handling the form
			let activeParty = amendmentController.getAmendmentIterationParty(accessRecord);
			// 13. Append question actions depending on user type and application status
			let userRole =
				userType === constants.userTypes.APPLICANT ? '' : isManager ? constants.roleTypes.MANAGER : constants.roleTypes.REVIEWER;
			accessRecord.jsonSchema = datarequestUtil.injectQuestionActions(
				accessRecord.jsonSchema,
				userType,
				accessRecord.applicationStatus,
				userRole
			);
			// 14. Return application form
			return res.status(200).json({
				status: 'success',
				data: {
					...accessRecord,
					aboutApplication:
						typeof accessRecord.aboutApplication === 'string' ? JSON.parse(accessRecord.aboutApplication) : accessRecord.aboutApplication,
					datasets: accessRecord.datasets,
					readOnly,
					...countUnsubmittedAmendments,
					userType,
					activeParty,
					projectId: accessRecord.projectId || helper.generateFriendlyId(accessRecord._id),
					inReviewMode,
					reviewSections,
					hasRecommended,
					workflow,
					files: accessRecord.files || [],
				},
			});
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	//GET api/v1/data-access-request/dataset/:datasetId
	getAccessRequestByUserAndDataset: async (req, res) => {
		let accessRecord;
		let data = {};
		let dataset;
		try {
			// 1. Get dataSetId from params
			let {
				params: { dataSetId },
			} = req;
			// 2. Get the userId
			let { id: userId, firstname, lastname } = req.user;
			// 3. Find the matching record
			accessRecord = await DataRequestModel.findOne({
				dataSetId,
				userId,
				applicationStatus: constants.applicationStatuses.INPROGRESS,
			}).populate({
				path: 'mainApplicant',
				select: 'firstname lastname -id -_id',
			});
			// 4. Get dataset
			dataset = await ToolModel.findOne({ datasetid: dataSetId }).populate('publisher');
			// 5. If no record create it and pass back
			if (!accessRecord) {
				if (!dataset) {
					return res.status(500).json({ status: 'error', message: 'No dataset available.' });
				}
				let {
					datasetfields: { publisher = '' },
				} = dataset;
				// 1. GET the template from the custodian
				const accessRequestTemplate = await DataRequestSchemaModel.findOne({
					$or: [{ dataSetId }, { publisher }, { dataSetId: 'default' }],
					status: 'active',
				}).sort({ createdAt: -1 });

				if (!accessRequestTemplate) {
					return res.status(400).json({
						status: 'error',
						message: 'No Data Access request schema.',
					});
				}
				// 2. Build up the accessModel for the user
				let { jsonSchema, version, _id: schemaId } = accessRequestTemplate;
				// 3. create new DataRequestModel
				let record = new DataRequestModel({
					version,
					userId,
					dataSetId,
					datasetIds: [dataSetId],
					datasetTitles: [dataset.name],
					jsonSchema,
					schemaId,
					publisher,
					questionAnswers: '{}',
					aboutApplication: {},
					applicationStatus: constants.applicationStatuses.INPROGRESS,
				});
				// 4. save record
				const newApplication = await record.save();
				newApplication.projectId = helper.generateFriendlyId(newApplication._id);
				await newApplication.save();

				// 5. return record
				data = {
					...newApplication._doc,
					mainApplicant: { firstname, lastname },
				};
			} else {
				data = { ...accessRecord.toObject() };
			}
			// 6. Parse json to allow us to modify schema
			data.jsonSchema = JSON.parse(data.jsonSchema);
			// 7. Append question actions depending on user type and application status
			data.jsonSchema = datarequestUtil.injectQuestionActions(data.jsonSchema, constants.userTypes.APPLICANT, data.applicationStatus);
			// 8. Return payload
			return res.status(200).json({
				status: 'success',
				data: {
					...data,
					questionAnswers: JSON.parse(data.questionAnswers),
					aboutApplication: typeof data.aboutApplication === 'string' ? JSON.parse(data.aboutApplication) : data.aboutApplication,
					dataset,
					projectId: data.projectId || helper.generateFriendlyId(data._id),
					userType: constants.userTypes.APPLICANT,
					activeParty: constants.userTypes.APPLICANT,
					inReviewMode: false,
					reviewSections: [],
					files: data.files || [],
				},
			});
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	//GET api/v1/data-access-request/datasets/:datasetIds
	getAccessRequestByUserAndMultipleDatasets: async (req, res) => {
		let accessRecord;
		let data = {};
		let datasets = [];
		try {
			// 1. Get datasetIds from params
			let {
				params: { datasetIds },
			} = req;
			let arrDatasetIds = datasetIds.split(',');
			// 2. Get the userId
			let { id: userId, firstname, lastname } = req.user;
			// 3. Find the matching record
			accessRecord = await DataRequestModel.findOne({
				datasetIds: { $all: arrDatasetIds },
				userId,
				applicationStatus: constants.applicationStatuses.INPROGRESS,
			})
				.populate([
					{
						path: 'mainApplicant',
						select: 'firstname lastname -id -_id',
					},
					{ path: 'files.owner', select: 'firstname lastname' },
				])
				.sort({ createdAt: 1 });
			// 4. Get datasets
			datasets = await ToolModel.find({
				datasetid: { $in: arrDatasetIds },
			}).populate('publisher');
			const arrDatasetNames = datasets.map(dataset => dataset.name);
			// 5. If no record create it and pass back
			if (!accessRecord) {
				if (_.isEmpty(datasets)) {
					return res.status(500).json({ status: 'error', message: 'No datasets available.' });
				}
				let {
					datasetfields: { publisher = '' },
				} = datasets[0];

				// 1. GET the template from the custodian or take the default (Cannot have dataset specific question sets for multiple datasets)
				const accessRequestTemplate = await DataRequestSchemaModel.findOne({
					$or: [{ publisher }, { dataSetId: 'default' }],
					status: 'active',
				}).sort({ createdAt: -1 });
				// 2. Ensure a question set was found
				if (!accessRequestTemplate) {
					return res.status(400).json({
						status: 'error',
						message: 'No Data Access request schema.',
					});
				}
				// 3. Build up the accessModel for the user
				let { jsonSchema, version, _id: schemaId } = accessRequestTemplate;
				// 4. Create new DataRequestModel
				let record = new DataRequestModel({
					version,
					userId,
					datasetIds: arrDatasetIds,
					datasetTitles: arrDatasetNames,
					jsonSchema,
					schemaId,
					publisher,
					questionAnswers: '{}',
					aboutApplication: {},
					applicationStatus: constants.applicationStatuses.INPROGRESS,
				});
				// 4. save record
				const newApplication = await record.save();
				newApplication.projectId = helper.generateFriendlyId(newApplication._id);
				await newApplication.save();
				// 5. return record
				data = {
					...newApplication._doc,
					mainApplicant: { firstname, lastname },
				};
			} else {
				data = { ...accessRecord.toObject() };
			}
			// 6. Parse json to allow us to modify schema
			data.jsonSchema = JSON.parse(data.jsonSchema);
			// 7. Append question actions depending on user type and application status
			data.jsonSchema = datarequestUtil.injectQuestionActions(data.jsonSchema, constants.userTypes.APPLICANT, data.applicationStatus);
			// 8. Return payload
			return res.status(200).json({
				status: 'success',
				data: {
					...data,
					questionAnswers: JSON.parse(data.questionAnswers),
					aboutApplication: typeof data.aboutApplication === 'string' ? JSON.parse(data.aboutApplication) : data.aboutApplication,
					datasets,
					projectId: data.projectId || helper.generateFriendlyId(data._id),
					userType: constants.userTypes.APPLICANT,
					activeParty: constants.userTypes.APPLICANT,
					inReviewMode: false,
					reviewSections: [],
					files: data.files || [],
				},
			});
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	//PATCH api/v1/data-access-request/:id
	updateAccessRequestDataElement: async (req, res) => {
		try {
			// 1. Id is the _id object in mongoo.db not the generated id or dataset Id
			const {
				params: { id },
				body: data,
			} = req;
			// 2. Destructure body and update only specific fields by building a segregated non-user specified update object
			let updateObj = module.exports.buildUpdateObject({
				...data,
				user: req.user,
			});
			// 3. Find data request by _id to determine current status
			let accessRequestRecord = await DataRequestModel.findOne({
				_id: id,
			});
			// 4. Check access record
			if (!accessRequestRecord) {
				return res.status(404).json({ status: 'error', message: 'Data Access Request not found.' });
			}
			// 5. Update record object
			module.exports.updateApplication(accessRequestRecord, updateObj).then(accessRequestRecord => {
				const { unansweredAmendments = 0, answeredAmendments = 0, dirtySchema = false } = accessRequestRecord;
				if (dirtySchema) {
					accessRequestRecord.jsonSchema = JSON.parse(accessRequestRecord.jsonSchema);
					accessRequestRecord = amendmentController.injectAmendments(accessRequestRecord, constants.userTypes.APPLICANT, req.user);
				}
				let data = {
					status: 'success',
					unansweredAmendments,
					answeredAmendments,
				};
				if (dirtySchema) {
					data = {
						...data,
						jsonSchema: accessRequestRecord.jsonSchema,
					};
				}
				// 6. Return new data object
				return res.status(200).json(data);
			});
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	buildUpdateObject: data => {
		let updateObj = {};
		let { aboutApplication, questionAnswers, updatedQuestionId, user, jsonSchema = '' } = data;
		if (aboutApplication) {
			if (typeof aboutApplication === 'string') {
				aboutApplication = JSON.parse(aboutApplication);
			}
			const { datasetIds, datasetTitles } = aboutApplication.selectedDatasets.reduce(
				(newObj, dataset) => {
					newObj.datasetIds = [...newObj.datasetIds, dataset.datasetId];
					newObj.datasetTitles = [...newObj.datasetTitles, dataset.name];
					return newObj;
				},
				{ datasetIds: [], datasetTitles: [] }
			);

			updateObj = { aboutApplication, datasetIds, datasetTitles };
		}
		if (questionAnswers) {
			updateObj = { ...updateObj, questionAnswers, updatedQuestionId, user };
		}

		if (!_.isEmpty(jsonSchema)) {
			updateObj = { ...updateObj, jsonSchema };
		}

		return updateObj;
	},

	updateApplication: async (accessRecord, updateObj) => {
		// 1. Extract properties
		let { applicationStatus, _id } = accessRecord;
		let { updatedQuestionId = '', user } = updateObj;
		// 2. If application is in progress, update initial question answers
		if (applicationStatus === constants.applicationStatuses.INPROGRESS) {
			await DataRequestModel.findByIdAndUpdate(_id, updateObj, { new: true }, err => {
				if (err) {
					console.error(err.message);
					throw err;
				}
			});
			return accessRecord;
			// 3. Else if application has already been submitted make amendment
		} else if (
			applicationStatus === constants.applicationStatuses.INREVIEW ||
			applicationStatus === constants.applicationStatuses.SUBMITTED
		) {
			if (_.isNil(updateObj.questionAnswers)) {
				return accessRecord;
			}
			let updatedAnswer = JSON.parse(updateObj.questionAnswers)[updatedQuestionId];
			accessRecord = amendmentController.handleApplicantAmendment(accessRecord.toObject(), updatedQuestionId, '', updatedAnswer, user);
			await DataRequestModel.replaceOne({ _id }, accessRecord, err => {
				if (err) {
					console.error(err.message);
					throw err;
				}
			});
			return accessRecord;
		}
	},

	//PUT api/v1/data-access-request/:id
	updateAccessRequestById: async (req, res) => {
		try {
			// 1. Id is the _id object in MongoDb not the generated id or dataset Id
			const {
				params: { id },
			} = req;
			// 2. Get the userId
			let { _id, id: userId } = req.user;
			let applicationStatus = '',
				applicationStatusDesc = '';

			// 3. Find the relevant data request application
			let accessRecord = await DataRequestModel.findOne({ _id: id }).populate([
				{
					path: 'datasets dataset mainApplicant authors',
					populate: {
						path: 'publisher additionalInfo',
						populate: {
							path: 'team',
							populate: {
								path: 'users',
								populate: {
									path: 'additionalInfo',
								},
							},
						},
					},
				},
				{
					path: 'publisherObj',
					populate: {
						path: 'team',
					},
				},
				{
					path: 'workflow.steps.reviewers',
					select: 'id email',
				},
			]);

			if (!accessRecord) {
				return res.status(404).json({ status: 'error', message: 'Application not found.' });
			}
			// 4. Ensure single datasets are mapped correctly into array (backward compatibility for single dataset applications)
			if (_.isEmpty(accessRecord.datasets)) {
				accessRecord.datasets = [accessRecord.dataset];
			}

			// 5. Check if the user is permitted to perform update to application
			let isDirty = false,
				statusChange = false,
				contributorChange = false;
			let { authorised, userType } = datarequestUtil.getUserPermissionsForApplication(accessRecord.toObject(), userId, _id);

			if (!authorised) {
				return res.status(401).json({
					status: 'error',
					message: 'Unauthorised to perform this update.',
				});
			}

			let { authorIds: currentAuthors } = accessRecord;
			let newAuthors = [];

			// 6. Extract new application status and desc to save updates
			if (userType === constants.userTypes.CUSTODIAN) {
				// Only a custodian manager can set the final status of an application
				authorised = false;
				let team = {};
				if (_.isNull(accessRecord.publisherObj)) {
					({ team = {} } = accessRecord.datasets[0].publisher.toObject());
				} else {
					({ team = {} } = accessRecord.publisherObj.toObject());
				}

				if (!_.isEmpty(team)) {
					authorised = teamController.checkTeamPermissions(constants.roleTypes.MANAGER, team, _id);
				}

				if (!authorised) {
					return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
				}
				// Extract params from body
				({ applicationStatus, applicationStatusDesc } = req.body);
				const finalStatuses = [
					constants.applicationStatuses.SUBMITTED,
					constants.applicationStatuses.APPROVED,
					constants.applicationStatuses.REJECTED,
					constants.applicationStatuses.APPROVEDWITHCONDITIONS,
					constants.applicationStatuses.WITHDRAWN,
				];
				if (applicationStatus) {
					accessRecord.applicationStatus = applicationStatus;

					if (finalStatuses.includes(applicationStatus)) {
						accessRecord.dateFinalStatus = new Date();
					}
					isDirty = true;
					statusChange = true;

					// Update any attached workflow in Mongo to show workflow is finished
					let { workflow = {} } = accessRecord;
					if (!_.isEmpty(workflow)) {
						accessRecord.workflow.steps = accessRecord.workflow.steps.map(step => {
							let updatedStep = {
								...step.toObject(),
								active: false,
							};
							if (step.active) {
								updatedStep = {
									...updatedStep,
									endDateTime: new Date(),
									completed: true,
								};
							}
							return updatedStep;
						});
					}
				}
				if (applicationStatusDesc) {
					accessRecord.applicationStatusDesc = inputSanitizer.removeNonBreakingSpaces(applicationStatusDesc);
					isDirty = true;
				}
				// If applicant, allow update to contributors/authors
			} else if (userType === constants.userTypes.APPLICANT) {
				// Extract new contributor/author IDs
				if (req.body.authorIds) {
					({ authorIds: newAuthors } = req.body);

					// Perform comparison between new and existing authors to determine if an update is required
					if (newAuthors && !helper.arraysEqual(newAuthors, currentAuthors)) {
						accessRecord.authorIds = newAuthors;
						isDirty = true;
						contributorChange = true;
					}
				}
			}
			// 7. If a change has been made, notify custodian and main applicant
			if (isDirty) {
				await accessRecord.save(async err => {
					if (err) {
						console.error(err.message);
						return res.status(500).json({ status: 'error', message: err.message });
					} else {
						// If save has succeeded - send notifications
						// Send notifications to added/removed contributors
						if (contributorChange) {
							await module.exports.createNotifications(
								constants.notificationTypes.CONTRIBUTORCHANGE,
								{ newAuthors, currentAuthors },
								accessRecord,
								req.user
							);
						}
						if (statusChange) {
							// Send notifications to custodian team, main applicant and contributors regarding status change
							await module.exports.createNotifications(
								constants.notificationTypes.STATUSCHANGE,
								{ applicationStatus, applicationStatusDesc },
								accessRecord,
								req.user
							);
							// Ensure Camunda ends workflow processes given that manager has made final decision
							let { name: dataRequestPublisher } = accessRecord.datasets[0].publisher;
							let bpmContext = {
								dataRequestStatus: applicationStatus,
								dataRequestManagerId: _id.toString(),
								dataRequestPublisher,
								managerApproved: true,
								businessKey: id,
							};
							bpmController.postManagerApproval(bpmContext);
						}
					}
				});
			}
			// 8. Return application
			return res.status(200).json({
				status: 'success',
				data: accessRecord._doc,
			});
		} catch (err) {
			console.error(err.message);
			res.status(500).json({
				status: 'error',
				message: 'An error occurred updating the application status',
			});
		}
	},

	//PUT api/v1/data-access-request/:id/assignworkflow
	assignWorkflow: async (req, res) => {
		try {
			// 1. Get the required request params
			const {
				params: { id },
			} = req;
			let { _id: userId } = req.user;
			let { workflowId = '' } = req.body;
			if (_.isEmpty(workflowId)) {
				return res.status(400).json({
					success: false,
					message: 'You must supply the unique identifier to assign a workflow to this application',
				});
			}
			// 2. Retrieve DAR from database
			let accessRecord = await DataRequestModel.findOne({ _id: id }).populate({
				path: 'datasets dataset mainApplicant authors',
				populate: {
					path: 'publisher',
					populate: {
						path: 'team',
						populate: {
							path: 'users',
						},
					},
				},
			});
			if (!accessRecord) {
				return res.status(404).json({ status: 'error', message: 'Application not found.' });
			}
			// 3. Ensure single datasets are mapped correctly into array (backward compatibility for single dataset applications)
			if (_.isEmpty(accessRecord.datasets)) {
				accessRecord.datasets = [accessRecord.dataset];
			}
			// 4. Check permissions of user is manager of associated team
			let authorised = false;
			if (_.has(accessRecord.datasets[0].toObject(), 'publisher.team')) {
				let {
					publisher: { team },
				} = accessRecord.datasets[0];
				authorised = teamController.checkTeamPermissions(constants.roleTypes.MANAGER, team.toObject(), userId);
			}
			// 5. Refuse access if not authorised
			if (!authorised) {
				return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
			}
			// 6. Check publisher allows workflows
			let workflowEnabled = false;
			if (_.has(accessRecord.datasets[0].toObject(), 'publisher.workflowEnabled')) {
				({
					publisher: { workflowEnabled },
				} = accessRecord.datasets[0]);
				if (!workflowEnabled) {
					return res.status(400).json({
						success: false,
						message: 'This custodian has not enabled workflows',
					});
				}
			}
			// 7. Check no workflow already assigned
			let { workflowId: currentWorkflowId = '' } = accessRecord;
			if (!_.isEmpty(currentWorkflowId)) {
				return res.status(400).json({
					success: false,
					message: 'This application already has a workflow assigned',
				});
			}
			// 8. Check application is in-review
			let { applicationStatus } = accessRecord;
			if (applicationStatus !== constants.applicationStatuses.INREVIEW) {
				return res.status(400).json({
					success: false,
					message: 'The application status must be set to in review to assign a workflow',
				});
			}
			// 9. Retrieve workflow using ID from database
			const workflow = await WorkflowModel.findOne({
				_id: workflowId,
			}).populate([
				{
					path: 'steps.reviewers',
					model: 'User',
					select: '_id id firstname lastname email',
				},
			]);
			if (!workflow) {
				return res.status(404).json({ success: false });
			}
			// 10. Set first workflow step active and ensure all others are false
			let workflowObj = workflow.toObject();
			workflowObj.steps = workflowObj.steps.map(step => {
				return { ...step, active: false };
			});
			workflowObj.steps[0].active = true;
			workflowObj.steps[0].startDateTime = new Date();
			// 11. Update application with attached workflow
			accessRecord.workflowId = workflowId;
			accessRecord.workflow = workflowObj;
			// 12. Submit save
			accessRecord.save(function (err) {
				if (err) {
					console.error(err.message);
					return res.status(400).json({
						success: false,
						message: err.message,
					});
				} else {
					// 13. Contact Camunda to start workflow process
					let { name: dataRequestPublisher } = accessRecord.datasets[0].publisher;
					let reviewerList = workflowObj.steps[0].reviewers.map(reviewer => reviewer._id.toString());
					let bpmContext = {
						businessKey: id,
						dataRequestStatus: constants.applicationStatuses.INREVIEW,
						dataRequestUserId: userId.toString(),
						dataRequestPublisher,
						dataRequestStepName: workflowObj.steps[0].stepName,
						notifyReviewerSLA: workflowController.calculateStepDeadlineReminderDate(workflowObj.steps[0]),
						reviewerList,
					};
					bpmController.postStartStepReview(bpmContext);
					// 14. Gather context for notifications
					const emailContext = workflowController.getWorkflowEmailContext(accessRecord, workflowObj, 0);
					// 15. Create notifications to reviewers of the step that has been completed
					module.exports.createNotifications(constants.notificationTypes.REVIEWSTEPSTART, emailContext, accessRecord, req.user);
					// 16. Create our notifications to the custodian team managers if assigned a workflow to a DAR application
					module.exports.createNotifications(constants.notificationTypes.WORKFLOWASSIGNED, emailContext, accessRecord, req.user);
					// 16. Return workflow payload
					return res.status(200).json({
						success: true,
					});
				}
			});
		} catch (err) {
			console.error(err.message);
			return res.status(500).json({
				success: false,
				message: 'An error occurred assigning the workflow',
			});
		}
	},

	//PUT api/v1/data-access-request/:id/startreview
	updateAccessRequestStartReview: async (req, res) => {
		try {
			// 1. Get the required request params
			const {
				params: { id },
			} = req;
			let { _id: userId } = req.user;
			// 2. Retrieve DAR from database
			let accessRecord = await DataRequestModel.findOne({ _id: id }).populate({
				path: 'publisherObj',
				populate: {
					path: 'team',
				},
			});
			if (!accessRecord) {
				return res.status(404).json({ status: 'error', message: 'Application not found.' });
			}
			// 3. Check permissions of user is reviewer of associated team
			let authorised = false;
			if (_.has(accessRecord.toObject(), 'publisherObj.team')) {
				let { team } = accessRecord.publisherObj;
				authorised = teamController.checkTeamPermissions(constants.roleTypes.MANAGER, team.toObject(), userId);
			}
			// 4. Refuse access if not authorised
			if (!authorised) {
				return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
			}
			// 5. Check application is in submitted state
			let { applicationStatus } = accessRecord;
			if (applicationStatus !== constants.applicationStatuses.SUBMITTED) {
				return res.status(400).json({
					success: false,
					message: 'The application status must be set to submitted to start a review',
				});
			}
			// 6. Update application to 'in review'
			accessRecord.applicationStatus = constants.applicationStatuses.INREVIEW;
			accessRecord.dateReviewStart = new Date();
			// 7. Save update to access record
			await accessRecord.save(async err => {
				if (err) {
					console.error(err.message);
					res.status(500).json({ status: 'error', message: err.message });
				} else {
					// 8. Call Camunda controller to get pre-review process
					let response = await bpmController.getProcess(id);
					let { data = {} } = response;
					if (!_.isEmpty(data)) {
						let [obj] = data;
						let { id: taskId } = obj;
						let {
							publisherObj: { name },
						} = accessRecord;
						let bpmContext = {
							taskId,
							applicationStatus,
							managerId: userId.toString(),
							publisher: name,
							notifyManager: 'P999D',
						};
						// 9. Call Camunda controller to start manager review process
						bpmController.postStartManagerReview(bpmContext);
					}
				}
			});
			// 14. Return aplication and successful response
			return res.status(200).json({ status: 'success' });
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	//POST api/v1/data-access-request/:id/upload
	uploadFiles: async (req, res) => {
		try {
			// 1. get DAR ID
			const {
				params: { id },
			} = req;
			// 2. get files
			let files = req.files;
			// 3. descriptions and uniqueIds file from FE
			let { descriptions, ids } = req.body;
			// 4. get access record
			let accessRecord = await DataRequestModel.findOne({ _id: id });
			if (!accessRecord) {
				return res.status(404).json({ status: 'error', message: 'Application not found.' });
			}
			// 5. Check if requesting user is custodian member or applicant/contributor
			// let { authorised } = datarequestUtil.getUserPermissionsForApplication(accessRecord, req.user.id, req.user._id);
			// 6. check authorisation
			// if (!authorised) {
			// 	return res
			// 		.status(401)
			// 		.json({ status: 'failure', message: 'Unauthorised' });
			// }
			// 7. check files
			if (_.isEmpty(files)) {
				return res.status(400).json({ status: 'error', message: 'No files to upload' });
			}
			let fileArr = [];
			// check and see if descriptions and ids are an array
			let descriptionArray = Array.isArray(descriptions);
			let idArray = Array.isArray(ids);
			// 8. process the files for scanning
			for (let i = 0; i < files.length; i++) {
				// get description information
				let description = descriptionArray ? descriptions[i] : descriptions;
				// get uniqueId
				let generatedId = idArray ? ids[i] : ids;
				// remove - from uuidV4
				let uniqueId = generatedId.replace(/-/gim, '');
				// send to db
				const response = await processFile(files[i], id, uniqueId);
				// deconstruct response
				let { status } = response;
				// setup fileArr for mongoo
				let newFile = {
					status: status.trim(),
					description: description.trim(),
					fileId: uniqueId,
					size: files[i].size,
					name: files[i].originalname,
					owner: req.user._id,
					error: status === fileStatus.ERROR ? 'Could not upload. Unknown error. Please try again.' : '',
				};
				// update local for post back to FE
				fileArr.push(newFile);
				// mongoo db update files array
				accessRecord.files.push(newFile);
			}
			// 9. write back into mongo [{userId, fileName, status: enum, size}]
			await accessRecord.save();
			// 10. get the latest updates with the users
			let updatedRecord = await DataRequestModel.findOne({ _id: id }).populate([
				{
					path: 'files.owner',
					select: 'firstname lastname id',
				},
			]);

			// 11. process access record into object
			let record = updatedRecord._doc;
			// 12. fet files
			let mediaFiles = record.files.map(f => {
				return f._doc;
			});
			// 10. return response
			return res.status(200).json({ status: 'success', mediaFiles });
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	//GET api/v1/data-access-request/:id/file/:fileId/status
	getFileStatus: async (req, res) => {
		try {
			// 1. get params
			const {
				params: { id, fileId },
			} = req;

			// 2. get AccessRecord
			let accessRecord = await DataRequestModel.findOne({ _id: id });
			if (!accessRecord) {
				return res.status(404).json({ status: 'error', message: 'Application not found.' });
			}

			// 3. get file
			const fileIndex = accessRecord.files.findIndex(file => file.fileId === fileId);
			if (fileIndex === -1) return res.status(404).json({ status: 'error', message: 'File not found.' });

			// 4. Return successful response
			return res.status(200).json({ status: accessRecord.files[fileIndex].status });
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	//GET api/v1/data-access-request/:id/file/:fileId
	getFile: async (req, res) => {
		try {
			// 1. get params
			const {
				params: { id, fileId },
			} = req;

			// 2. get AccessRecord
			let accessRecord = await DataRequestModel.findOne({ _id: id });
			if (!accessRecord) {
				return res.status(404).json({ status: 'error', message: 'Application not found.' });
			}
			// 3. process access record into object
			let record = accessRecord._doc;
			// 4. find the file in the files array from db
			let mediaFile =
				record.files.find(f => {
					let { fileId: dbFileId } = f._doc;
					return dbFileId === fileId;
				}) || {};
			// 5. no file return
			if (_.isEmpty(mediaFile)) {
				return res.status(400).json({
					status: 'error',
					message: 'No file to download, please try again later',
				});
			}
			// 6. get the name of the file
			let { name, fileId: dbFileId } = mediaFile._doc;
			// 7. get the file
			await getFile(name, dbFileId, id);
			// 8. send file back to user
			return res.status(200).sendFile(`${process.env.TMPDIR}${id}/${dbFileId}_${name}`);
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	//PUT api/v1/data-access-request/:id/vote
	updateAccessRequestReviewVote: async (req, res) => {
		try {
			// 1. Get the required request params
			const {
				params: { id },
			} = req;
			let { _id: userId } = req.user;
			let { approved, comments = '' } = req.body;
			if (_.isUndefined(approved) || _.isEmpty(comments)) {
				return res.status(400).json({
					success: false,
					message: 'You must supply the approved status with a reason',
				});
			}
			// 2. Retrieve DAR from database
			let accessRecord = await DataRequestModel.findOne({ _id: id }).populate([
				{
					path: 'publisherObj',
					populate: {
						path: 'team',
						populate: {
							path: 'users',
						},
					},
				},
				{
					path: 'workflow.steps.reviewers',
					select: 'firstname lastname id email',
				},
				{
					path: 'datasets dataset',
				},
				{
					path: 'mainApplicant',
				},
			]);
			if (!accessRecord) {
				return res.status(404).json({ status: 'error', message: 'Application not found.' });
			}
			// 3. Check permissions of user is reviewer of associated team
			let authorised = false;
			if (_.has(accessRecord.toObject(), 'publisherObj.team')) {
				let { team } = accessRecord.publisherObj;
				authorised = teamController.checkTeamPermissions(constants.roleTypes.REVIEWER, team.toObject(), userId);
			}
			// 4. Refuse access if not authorised
			if (!authorised) {
				return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
			}
			// 5. Check application is in-review
			let { applicationStatus } = accessRecord;
			if (applicationStatus !== constants.applicationStatuses.INREVIEW) {
				return res.status(400).json({
					success: false,
					message: 'The application status must be set to in review to cast a vote',
				});
			}
			// 6. Ensure a workflow has been attached to this application
			let { workflow } = accessRecord;
			if (!workflow) {
				return res.status(400).json({
					success: false,
					message: 'There is no workflow attached to this application in order to cast a vote',
				});
			}
			// 7. Ensure the requesting user is expected to cast a vote
			let { steps } = workflow;
			let activeStepIndex = steps.findIndex(step => {
				return step.active === true;
			});
			if (!steps[activeStepIndex].reviewers.map(reviewer => reviewer._id.toString()).includes(userId.toString())) {
				return res.status(400).json({
					success: false,
					message: 'You have not been assigned to vote on this review phase',
				});
			}
			//8. Ensure the requesting user has not already voted
			let { recommendations = [] } = steps[activeStepIndex];
			if (recommendations) {
				let found = recommendations.some(rec => {
					return rec.reviewer.equals(userId);
				});
				if (found) {
					return res.status(400).json({
						success: false,
						message: 'You have already voted on this review phase',
					});
				}
			}
			// 9. Create new recommendation
			let newRecommendation = {
				approved,
				comments,
				reviewer: new mongoose.Types.ObjectId(userId),
				createdDate: new Date(),
			};
			// 10. Update access record with recommendation
			accessRecord.workflow.steps[activeStepIndex].recommendations = [
				...accessRecord.workflow.steps[activeStepIndex].recommendations,
				newRecommendation,
			];
			// 11. Workflow management - construct Camunda payloads
			let bpmContext = workflowController.buildNextStep(userId, accessRecord, activeStepIndex, false);
			// 12. If step is now complete, update database record
			if (bpmContext.stepComplete) {
				accessRecord.workflow.steps[activeStepIndex].active = false;
				accessRecord.workflow.steps[activeStepIndex].completed = true;
				accessRecord.workflow.steps[activeStepIndex].endDateTime = new Date();
			}
			// 13. If it was not the final phase that was completed, move to next step in database
			if (!bpmContext.finalPhaseApproved) {
				accessRecord.workflow.steps[activeStepIndex + 1].active = true;
				accessRecord.workflow.steps[activeStepIndex + 1].startDateTime = new Date();
			}
			// 14. Update MongoDb record for DAR
			await accessRecord.save(async err => {
				if (err) {
					console.error(err.message);
					res.status(500).json({ status: 'error', message: err.message });
				} else {
					// 15. Create emails and notifications
					let relevantStepIndex = 0,
						relevantNotificationType = '';
					if (bpmContext.stepComplete && !bpmContext.finalPhaseApproved) {
						// Create notifications to reviewers of the next step that has been activated
						relevantStepIndex = activeStepIndex + 1;
						relevantNotificationType = constants.notificationTypes.REVIEWSTEPSTART;
					} else if (bpmContext.stepComplete && bpmContext.finalPhaseApproved) {
						// Create notifications to managers that the application is awaiting final approval
						relevantStepIndex = activeStepIndex;
						relevantNotificationType = constants.notificationTypes.FINALDECISIONREQUIRED;
					}
					// Continue only if notification required
					if (!_.isEmpty(relevantNotificationType)) {
						const emailContext = workflowController.getWorkflowEmailContext(accessRecord, workflow, relevantStepIndex);
						module.exports.createNotifications(relevantNotificationType, emailContext, accessRecord, req.user);
					}
					// 16. Call Camunda controller to update workflow process
					bpmController.postCompleteReview(bpmContext);
				}
			});
			// 17. Return aplication and successful response
			return res.status(200).json({ status: 'success', data: accessRecord._doc });
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	//PUT api/v1/data-access-request/:id/stepoverride
	updateAccessRequestStepOverride: async (req, res) => {
		try {
			// 1. Get the required request params
			const {
				params: { id },
			} = req;
			let { _id: userId } = req.user;
			// 2. Retrieve DAR from database
			let accessRecord = await DataRequestModel.findOne({ _id: id }).populate([
				{
					path: 'publisherObj',
					populate: {
						path: 'team',
						populate: {
							path: 'users',
						},
					},
				},
				{
					path: 'workflow.steps.reviewers',
					select: 'firstname lastname id email',
				},
				{
					path: 'datasets dataset',
				},
				{
					path: 'mainApplicant',
				},
			]);
			if (!accessRecord) {
				return res.status(404).json({ status: 'error', message: 'Application not found.' });
			}
			// 3. Check permissions of user is manager of associated team
			let authorised = false;
			if (_.has(accessRecord.toObject(), 'publisherObj.team')) {
				let { team } = accessRecord.publisherObj;
				authorised = teamController.checkTeamPermissions(constants.roleTypes.MANAGER, team.toObject(), userId);
			}
			// 4. Refuse access if not authorised
			if (!authorised) {
				return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
			}
			// 5. Check application is in review state
			let { applicationStatus } = accessRecord;
			if (applicationStatus !== constants.applicationStatuses.INREVIEW) {
				return res.status(400).json({
					success: false,
					message: 'The application status must be set to in review',
				});
			}
			// 6. Check a workflow is assigned with valid steps
			let { workflow = {} } = accessRecord;
			let { steps = [] } = workflow;
			if (_.isEmpty(workflow) || _.isEmpty(steps)) {
				return res.status(400).json({
					success: false,
					message: 'A valid workflow has not been attached to this application',
				});
			}
			// 7. Get the attached active workflow step
			let activeStepIndex = steps.findIndex(step => {
				return step.active === true;
			});
			if (activeStepIndex === -1) {
				return res.status(400).json({
					success: false,
					message: 'There is no active step to override for this workflow',
				});
			}
			// 8. Update the step to be completed closing off end date/time
			accessRecord.workflow.steps[activeStepIndex].active = false;
			accessRecord.workflow.steps[activeStepIndex].completed = true;
			accessRecord.workflow.steps[activeStepIndex].endDateTime = new Date();
			// 9. Set up Camunda payload
			let bpmContext = workflowController.buildNextStep(userId, accessRecord, activeStepIndex, true);
			// 10. If it was not the final phase that was completed, move to next step
			if (!bpmContext.finalPhaseApproved) {
				accessRecord.workflow.steps[activeStepIndex + 1].active = true;
				accessRecord.workflow.steps[activeStepIndex + 1].startDateTime = new Date();
			}
			// 11. Save changes to the DAR
			await accessRecord.save(async err => {
				if (err) {
					console.error(err.message);
					res.status(500).json({ status: 'error', message: err.message });
				} else {
					// 12. Gather context for notifications (active step)
					let emailContext = workflowController.getWorkflowEmailContext(accessRecord, workflow, activeStepIndex);
					// 13. Create notifications to reviewers of the step that has been completed
					module.exports.createNotifications(constants.notificationTypes.STEPOVERRIDE, emailContext, accessRecord, req.user);
					// 14. Create emails and notifications
					let relevantStepIndex = 0,
						relevantNotificationType = '';
					if (bpmContext.finalPhaseApproved) {
						// Create notifications to managers that the application is awaiting final approval
						relevantStepIndex = activeStepIndex;
						relevantNotificationType = constants.notificationTypes.FINALDECISIONREQUIRED;
					} else {
						// Create notifications to reviewers of the next step that has been activated
						relevantStepIndex = activeStepIndex + 1;
						relevantNotificationType = constants.notificationTypes.REVIEWSTEPSTART;
					}
					// Get the email context only if required
					if (relevantStepIndex !== activeStepIndex) {
						emailContext = workflowController.getWorkflowEmailContext(accessRecord, workflow, relevantStepIndex);
					}
					module.exports.createNotifications(relevantNotificationType, emailContext, accessRecord, req.user);
					// 15. Call Camunda controller to start manager review process
					bpmController.postCompleteReview(bpmContext);
				}
			});
			// 16. Return aplication and successful response
			return res.status(200).json({ status: 'success' });
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	//PUT api/v1/data-access-request/:id/deletefile
	updateAccessRequestDeleteFile: async (req, res) => {
		try {
			const {
				params: { id },
			} = req;

			// 1. Id of the file to delete
			let { fileId } = req.body;

			// 2. Find the relevant data request application
			let accessRecord = await DataRequestModel.findOne({ _id: id });

			if (!accessRecord) {
				return res.status(404).json({ status: 'error', message: 'Application not found.' });
			}

			// 4. Ensure single datasets are mapped correctly into array
			if (_.isEmpty(accessRecord.datasets)) {
				accessRecord.datasets = [accessRecord.dataset];
			}

			// 5. If application is not in progress, actions cannot be performed
			if (accessRecord.applicationStatus !== constants.applicationStatuses.INPROGRESS) {
				return res.status(400).json({
					success: false,
					message: 'This application is no longer in pre-submission status and therefore this action cannot be performed',
				});
			}

			// 6. Get the requesting users permission levels
			let { authorised, userType } = datarequestUtil.getUserPermissionsForApplication(accessRecord.toObject(), req.user.id, req.user._id);
			// 7. Return unauthorised message if the requesting user is not an applicant
			if (!authorised || userType !== constants.userTypes.APPLICANT) {
				return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
			}

			// 8. Remove the file from the application
			const newFileList = accessRecord.files.filter(file => file.fileId !== fileId);

			accessRecord.files = newFileList;

			// 9. write back into mongo
			await accessRecord.save();

			// 10. Return successful response
			return res.status(200).json({ status: 'success' });
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	//POST api/v1/data-access-request/:id
	submitAccessRequestById: async (req, res) => {
		try {
			// 1. id is the _id object in mongoo.db not the generated id or dataset Id
			let {
				params: { id },
			} = req;
			// 2. Find the relevant data request application
			let accessRecord = await DataRequestModel.findOne({ _id: id }).populate([
				{
					path: 'datasets dataset',
					populate: {
						path: 'publisher',
						populate: {
							path: 'team',
							populate: {
								path: 'users',
								populate: {
									path: 'additionalInfo',
								},
							},
						},
					},
				},
				{
					path: 'mainApplicant authors',
					populate: {
						path: 'additionalInfo',
					},
				},
				{
					path: 'publisherObj',
				},
			]);
			if (!accessRecord) {
				return res.status(404).json({ status: 'error', message: 'Application not found.' });
			}
			// 3. Check user type and authentication to submit application
			let { authorised, userType } = datarequestUtil.getUserPermissionsForApplication(accessRecord, req.user.id, req.user._id);
			if (!authorised) {
				return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
			}
			// 4. Ensure single datasets are mapped correctly into array (backward compatibility for single dataset applications)
			if (_.isEmpty(accessRecord.datasets)) {
				accessRecord.datasets = [accessRecord.dataset];
			}
			// 5. Perform either initial submission or resubmission depending on application status
			if (accessRecord.applicationStatus === constants.applicationStatuses.INPROGRESS) {
				accessRecord = module.exports.doInitialSubmission(accessRecord);
			} else if (
				accessRecord.applicationStatus === constants.applicationStatuses.INREVIEW ||
				accessRecord.applicationStatus === constants.applicationStatuses.SUBMITTED
			) {
				accessRecord = amendmentController.doResubmission(accessRecord.toObject(), req.user._id.toString());
			}
			// 6. Ensure a valid submission is taking place
			if (_.isNil(accessRecord.submissionType)) {
				return res.status(400).json({
					status: 'error',
					message: 'Application cannot be submitted as it has reached a final decision status.',
				});
			}
			// 7. Save changes to db
			await DataRequestModel.replaceOne({ _id: id }, accessRecord, async err => {
				if (err) {
					console.error(err.message);
					return res.status(500).json({
						status: 'error',
						message: 'An error occurred saving the changes',
					});
				} else {
					// 8. Send notifications and emails with amendments
					accessRecord.questionAnswers = JSON.parse(accessRecord.questionAnswers);
					accessRecord.jsonSchema = JSON.parse(accessRecord.jsonSchema);
					accessRecord = amendmentController.injectAmendments(accessRecord, userType, req.user);
					await module.exports.createNotifications(
						accessRecord.submissionType === constants.submissionTypes.INITIAL
							? constants.notificationTypes.SUBMITTED
							: constants.notificationTypes.RESUBMITTED,
						{},
						accessRecord,
						req.user
					);
					// 8. Start workflow process in Camunda if publisher requires it and it is the first submission
					if (accessRecord.workflowEnabled && accessRecord.submissionType === constants.submissionTypes.INITIAL) {
						let {
							publisherObj: { name: publisher },
							dateSubmitted,
						} = accessRecord;
						let bpmContext = {
							dateSubmitted,
							applicationStatus: constants.applicationStatuses.SUBMITTED,
							publisher,
							businessKey: id,
						};
						bpmController.postStartPreReview(bpmContext);
					}
				}
			});
			// 9. Return aplication and successful response
			return res.status(200).json({ status: 'success', data: accessRecord._doc });
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	doInitialSubmission: accessRecord => {
		// 1. Update application to submitted status
		accessRecord.submissionType = constants.submissionTypes.INITIAL;
		accessRecord.applicationStatus = constants.applicationStatuses.SUBMITTED;
		// 2. Check if workflow/5 Safes based application, set final status date if status will never change again
		if (_.has(accessRecord.datasets[0].toObject(), 'publisher') && !_.isNull(accessRecord.datasets[0].publisher)) {
			if (!accessRecord.datasets[0].publisher.workflowEnabled) {
				accessRecord.dateFinalStatus = new Date();
				accessRecord.workflowEnabled = false;
			} else {
				accessRecord.workflowEnabled = true;
			}
		}
		let dateSubmitted = new Date();
		accessRecord.dateSubmitted = dateSubmitted;
		// 3. Return updated access record for saving
		return accessRecord;
	},

	//POST api/v1/data-access-request/:id/email
	mailDataAccessRequestInfoById: async (req, res) => {
		try {
			// 1. Get the required request params
			const {
				params: { id },
			} = req;

			// 2. Retrieve DAR from database
			let accessRecord = await DataRequestModel.findOne({ _id: id }).populate([
				{
					path: 'datasets dataset',
				},
				{
					path: 'mainApplicant',
				},
			]);

			if (!accessRecord) {
				return res.status(404).json({ status: 'error', message: 'Application not found.' });
			}

			// 3. Ensure single datasets are mapped correctly into array
			if (_.isEmpty(accessRecord.datasets)) {
				accessRecord.datasets = [accessRecord.dataset];
			}

			// 4. If application is not in progress, actions cannot be performed
			if (accessRecord.applicationStatus !== constants.applicationStatuses.INPROGRESS) {
				return res.status(400).json({
					success: false,
					message: 'This application is no longer in pre-submission status and therefore this action cannot be performed',
				});
			}

			// 5. Get the requesting users permission levels
			let { authorised, userType } = datarequestUtil.getUserPermissionsForApplication(accessRecord.toObject(), req.user.id, req.user._id);
			// 6. Return unauthorised message if the requesting user is not an applicant
			if (!authorised || userType !== constants.userTypes.APPLICANT) {
				return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
			}

			// 7. Send notification to the authorised user
			module.exports.createNotifications(constants.notificationTypes.INPROGRESS, {}, accessRecord, req.user);

			return res.status(200).json({ status: 'success' });
		} catch (err) {
			console.error(err.message);
			return res.status(500).json({
				success: false,
				message: 'An error occurred',
			});
		}
	},

	//POST api/v1/data-access-request/:id/notify
	notifyAccessRequestById: async (req, res) => {
		// 1. Get the required request params
		const {
			params: { id },
		} = req;
		// 2. Retrieve DAR from database
		let accessRecord = await DataRequestModel.findOne({ _id: id }).populate([
			{
				path: 'publisherObj',
				populate: {
					path: 'team',
					populate: {
						path: 'users',
					},
				},
			},
			{
				path: 'workflow.steps.reviewers',
				select: 'firstname lastname id email',
			},
			{
				path: 'datasets dataset',
			},
			{
				path: 'mainApplicant',
			},
		]);
		if (!accessRecord) {
			return res.status(404).json({ status: 'error', message: 'Application not found.' });
		}
		let { workflow } = accessRecord;
		if (_.isEmpty(workflow)) {
			return res.status(400).json({
				status: 'error',
				message: 'There is no workflow attached to this application.',
			});
		}
		let activeStepIndex = workflow.steps.findIndex(step => {
			return step.active === true;
		});
		// 3. Determine email context if deadline has elapsed or is approaching
		const emailContext = workflowController.getWorkflowEmailContext(accessRecord, workflow, activeStepIndex);
		// 4. Send emails based on deadline elapsed or approaching
		if (emailContext.deadlineElapsed) {
			module.exports.createNotifications(constants.notificationTypes.DEADLINEPASSED, emailContext, accessRecord, req.user);
		} else {
			module.exports.createNotifications(constants.notificationTypes.DEADLINEWARNING, emailContext, accessRecord, req.user);
		}
		return res.status(200).json({ status: 'success' });
	},

	//POST api/v1/data-access-request/:id/actions
	performAction: async (req, res) => {
		try {
			// 1. Get the required request params
			const {
				params: { id },
			} = req;
			let { questionId, questionSetId, questionIds = [], mode, separatorText = '' } = req.body;
			if (_.isEmpty(questionId) || _.isEmpty(questionSetId)) {
				return res.status(400).json({
					success: false,
					message: 'You must supply the unique identifiers for the question to perform an action',
				});
			}
			// 2. Retrieve DAR from database
			let accessRecord = await DataRequestModel.findOne({ _id: id }).populate([
				{
					path: 'datasets dataset',
				},
				{
					path: 'publisherObj',
					populate: {
						path: 'team',
						populate: {
							path: 'users',
						},
					},
				},
			]);
			if (!accessRecord) {
				return res.status(404).json({ status: 'error', message: 'Application not found.' });
			}
			// 3. If application is not in progress, actions cannot be performed
			if (accessRecord.applicationStatus !== constants.applicationStatuses.INPROGRESS) {
				return res.status(400).json({
					success: false,
					message: 'This application is no longer in pre-submission status and therefore this action cannot be performed',
				});
			}
			// 4. Get the requesting users permission levels
			let { authorised, userType } = datarequestUtil.getUserPermissionsForApplication(accessRecord.toObject(), req.user.id, req.user._id);
			// 5. Return unauthorised message if the requesting user is not an applicant
			if (!authorised || userType !== constants.userTypes.APPLICANT) {
				return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
			}
			// 6. Parse the json schema to be modified
			let jsonSchema = JSON.parse(accessRecord.jsonSchema);
			let questionAnswers = JSON.parse(accessRecord.questionAnswers);
			// 7. Perform different action depending on mode passed
			switch (mode) {
				case constants.formActions.ADDREPEATABLESECTION:
					let duplicateQuestionSet = dynamicForm.duplicateQuestionSet(questionSetId, jsonSchema);
					jsonSchema = dynamicForm.insertQuestionSet(questionSetId, duplicateQuestionSet, jsonSchema);
					break;
				case constants.formActions.REMOVEREPEATABLESECTION:
					jsonSchema = dynamicForm.removeQuestionSetReferences(questionSetId, questionId, jsonSchema);
					questionAnswers = dynamicForm.removeQuestionSetAnswers(questionId, questionAnswers);
					break;
				case constants.formActions.ADDREPEATABLEQUESTIONS:
					if (_.isEmpty(questionIds)) {
						return res.status(400).json({
							success: false,
							message: 'You must supply the question identifiers to duplicate when performing this action',
						});
					}
					let duplicateQuestions = dynamicForm.duplicateQuestions(questionSetId, questionIds, separatorText, jsonSchema);
					jsonSchema = dynamicForm.insertQuestions(questionSetId, questionId, duplicateQuestions, jsonSchema);
					break;
				case constants.formActions.REMOVEREPEATABLEQUESTIONS:
					if (_.isEmpty(questionIds)) {
						return res.status(400).json({
							success: false,
							message: 'You must supply the question identifiers to remove when performing this action',
						});
					}
					// Add clicked 'remove' button to questions to delete (questionId)
					questionIds = [...questionIds, questionId];
					jsonSchema = dynamicForm.removeQuestionReferences(questionSetId, questionIds, jsonSchema);
					questionAnswers = dynamicForm.removeQuestionAnswers(questionIds, questionAnswers);
					break;
				default:
					return res.status(400).json({
						success: false,
						message: 'You must supply a valid action to perform',
					});
			}
			// 8. Save changes to database
			accessRecord.jsonSchema = JSON.stringify(jsonSchema);
			accessRecord.questionAnswers = JSON.stringify(questionAnswers);

			await accessRecord.save(async err => {
				if (err) {
					console.error(err.message);
					return res.status(500).json({ status: 'error', message: err.message });
				} else {
					// 9. Append question actions for in progress applicant
					jsonSchema = datarequestUtil.injectQuestionActions(
						jsonSchema,
						constants.userTypes.APPLICANT, // current user type
						constants.applicationStatuses.INPROGRESS,
						constants.userTypes.APPLICANT // active party
					);
					// 10. Return necessary object to reflect schema update
					return res.status(200).json({
						success: true,
						accessRecord: {
							jsonSchema,
							questionAnswers,
						},
					});
				}
			});
		} catch (err) {
			console.error(err.message);
			return res.status(500).json({
				success: false,
				message: 'An error occurred updating the application amendment',
			});
		}
	},

	updateFileStatus: async (req, res) => {
		try {
			// 1. Get the required request params
			const {
				params: { id, fileId },
			} = req;

			let { status } = req.body;

			// 2. Find the relevant data request application
			let accessRecord = await DataRequestModel.findOne({ _id: id });

			if (!accessRecord) {
				return res.status(404).json({ status: 'error', message: 'Application not found.' });
			}

			//3. Check the status is valid
			if (
				status !== fileStatus.UPLOADED &&
				status !== fileStatus.SCANNED &&
				status !== fileStatus.ERROR &&
				status !== fileStatus.QUARANTINED
			) {
				return res.status(400).json({ status: 'error', message: 'File status not valid' });
			}

			//4. get the file
			const fileIndex = accessRecord.files.findIndex(file => file.fileId === fileId);
			if (fileIndex === -1) return res.status(404).json({ status: 'error', message: 'File not found.' });

			//5. update the status
			accessRecord.files[fileIndex].status = status;

			//6. write back into mongo
			await accessRecord.save();

			return res.status(200).json({
				success: true,
			});
		} catch (err) {
			console.error(err.message);
			return res.status(500).json({
				success: false,
				message: err.message,
			});
		}
	},

	createNotifications: async (type, context, accessRecord, user) => {
		// Project details from about application if 5 Safes
		let { aboutApplication = {} } = accessRecord;
		if (typeof aboutApplication === 'string') {
			aboutApplication = JSON.parse(accessRecord.aboutApplication);
		}
		let { projectName = 'No project name set' } = aboutApplication;
		let { projectId, _id, workflow = {}, dateSubmitted = '', jsonSchema, questionAnswers } = accessRecord;
		if (_.isEmpty(projectId)) {
			projectId = _id;
		}
		// Parse the schema
		if (typeof jsonSchema === 'string') {
			jsonSchema = JSON.parse(accessRecord.jsonSchema);
		}
		if (typeof questionAnswers === 'string') {
			questionAnswers = JSON.parse(accessRecord.questionAnswers);
		}
		let { pages, questionPanels, questionSets: questions } = jsonSchema;
		// Publisher details from single dataset
		let {
			datasetfields: { contactPoint, publisher },
		} = accessRecord.datasets[0];
		let datasetTitles = accessRecord.datasets.map(dataset => dataset.name).join(', ');
		// Main applicant (user obj)
		let { firstname: appFirstName, lastname: appLastName, email: appEmail } = accessRecord.mainApplicant;
		// Requesting user
		let { firstname, lastname } = user;
		// Instantiate default params
		let custodianManagers = [],
			custodianUserIds = [],
			managerUserIds = [],
			emailRecipients = [],
			options = {},
			html = '',
			attachmentContent = '',
			filename = '',
			jsonContent = {},
			authors = [],
			attachments = [];
		let applicants = datarequestUtil.extractApplicantNames(questionAnswers).join(', ');
		// Fall back for single applicant on short application form
		if (_.isEmpty(applicants)) {
			applicants = `${appFirstName} ${appLastName}`;
		}
		// Get authors/contributors (user obj)
		if (!_.isEmpty(accessRecord.authors)) {
			authors = accessRecord.authors.map(author => {
				let { firstname, lastname, email, id } = author;
				return { firstname, lastname, email, id };
			});
		}
		// Deconstruct workflow context if passed
		let {
			workflowName = '',
			steps = [],
			stepName = '',
			reviewerNames = '',
			reviewSections = '',
			nextStepName = '',
			stepReviewers = [],
			stepReviewerUserIds = [],
			currentDeadline = '',
			remainingReviewers = [],
			remainingReviewerUserIds = [],
			dateDeadline,
		} = context;

		switch (type) {
			case constants.notificationTypes.INPROGRESS:
				await notificationBuilder.triggerNotificationMessage(
					[user.id],
					`An email with the data access request info for ${datasetTitles} has been sent to you`,
					'data access request',
					accessRecord._id
				);

				options = {
					userType: '',
					userEmail: appEmail,
					publisher,
					datasetTitles,
					userName: `${appFirstName} ${appLastName}`,
					userType: 'applicant',
					submissionType: constants.submissionTypes.INPROGRESS,
				};

				// Build email template
				({ html, jsonContent } = await emailGenerator.generateEmail(
					aboutApplication,
					questions,
					pages,
					questionPanels,
					questionAnswers,
					options
				));
				await emailGenerator.sendEmail(
					[user],
					constants.hdrukEmail,
					`Data Access Request in progress for ${datasetTitles}`,
					html,
					false,
					attachments
				);
				break;
			case constants.notificationTypes.STATUSCHANGE:
				// 1. Create notifications
				// Custodian manager and current step reviewer notifications
				if (_.has(accessRecord.datasets[0].toObject(), 'publisher.team.users')) {
					// Retrieve all custodian manager user Ids and active step reviewers
					custodianManagers = teamController.getTeamMembersByRole(accessRecord.datasets[0].publisher.team, constants.roleTypes.MANAGER);
					let activeStep = workflowController.getActiveWorkflowStep(workflow);
					stepReviewers = workflowController.getStepReviewers(activeStep);
					// Create custodian notification
					let statusChangeUserIds = [...custodianManagers, ...stepReviewers].map(user => user.id);
					await notificationBuilder.triggerNotificationMessage(
						statusChangeUserIds,
						`${appFirstName} ${appLastName}'s Data Access Request for ${datasetTitles} was ${context.applicationStatus} by ${firstname} ${lastname}`,
						'data access request',
						accessRecord._id
					);
				}
				// Create applicant notification
				await notificationBuilder.triggerNotificationMessage(
					[accessRecord.userId],
					`Your Data Access Request for ${datasetTitles} was ${context.applicationStatus} by ${publisher}`,
					'data access request',
					accessRecord._id
				);

				// Create authors notification
				if (!_.isEmpty(authors)) {
					await notificationBuilder.triggerNotificationMessage(
						authors.map(author => author.id),
						`A Data Access Request you are contributing to for ${datasetTitles} was ${context.applicationStatus} by ${publisher}`,
						'data access request',
						accessRecord._id
					);
				}

				// 2. Send emails to relevant users
				// Aggregate objects for custodian and applicant
				emailRecipients = [accessRecord.mainApplicant, ...custodianManagers, ...stepReviewers, ...accessRecord.authors];
				if (!dateSubmitted) ({ updatedAt: dateSubmitted } = accessRecord);
				// Create object to pass through email data
				options = {
					id: accessRecord._id,
					applicationStatus: context.applicationStatus,
					applicationStatusDesc: context.applicationStatusDesc,
					publisher,
					projectId,
					projectName,
					datasetTitles,
					dateSubmitted,
					applicants,
				};
				// Create email body content
				html = emailGenerator.generateDARStatusChangedEmail(options);
				// Send email
				await emailGenerator.sendEmail(
					emailRecipients,
					constants.hdrukEmail,
					`Data Access Request for ${datasetTitles} was ${context.applicationStatus} by ${publisher}`,
					html,
					false
				);
				break;
			case constants.notificationTypes.SUBMITTED:
				// 1. Create notifications
				// Custodian notification
				if (_.has(accessRecord.datasets[0].toObject(), 'publisher.team.users')) {
					// Retrieve all custodian user Ids to generate notifications
					custodianManagers = teamController.getTeamMembersByRole(accessRecord.datasets[0].publisher.team, constants.roleTypes.MANAGER);
					custodianUserIds = custodianManagers.map(user => user.id);
					await notificationBuilder.triggerNotificationMessage(
						custodianUserIds,
						`A Data Access Request has been submitted to ${publisher} for ${datasetTitles} by ${appFirstName} ${appLastName}`,
						'data access request',
						accessRecord._id
					);
				} else {
					const dataCustodianEmail = process.env.DATA_CUSTODIAN_EMAIL || contactPoint;
					custodianManagers = [{ email: dataCustodianEmail }];
				}
				// Applicant notification
				await notificationBuilder.triggerNotificationMessage(
					[accessRecord.userId],
					`Your Data Access Request for ${datasetTitles} was successfully submitted to ${publisher}`,
					'data access request',
					accessRecord._id
				);
				// Contributors/authors notification
				if (!_.isEmpty(authors)) {
					await notificationBuilder.triggerNotificationMessage(
						accessRecord.authors.map(author => author.id),
						`A Data Access Request you are contributing to for ${datasetTitles} was successfully submitted to ${publisher} by ${firstname} ${lastname}`,
						'data access request',
						accessRecord._id
					);
				}
				// 2. Send emails to custodian and applicant
				// Create object to pass to email generator
				options = {
					userType: '',
					userEmail: appEmail,
					publisher,
					datasetTitles,
					userName: `${appFirstName} ${appLastName}`,
				};
				// Iterate through the recipient types
				for (let emailRecipientType of constants.submissionEmailRecipientTypes) {
					// Establish email context object
					options = {
						...options,
						userType: emailRecipientType,
						submissionType: constants.submissionTypes.INITIAL,
					};
					// Build email template
					({ html, jsonContent } = await emailGenerator.generateEmail(
						aboutApplication,
						questions,
						pages,
						questionPanels,
						questionAnswers,
						options
					));
					// Send emails to custodian team members who have opted in to email notifications
					if (emailRecipientType === 'dataCustodian') {
						emailRecipients = [...custodianManagers];
						// Generate json attachment for external system integration
						attachmentContent = Buffer.from(JSON.stringify({ id: accessRecord._id, ...jsonContent })).toString('base64');
						filename = `${helper.generateFriendlyId(accessRecord._id)} ${moment().format().toString()}.json`;
						attachments = [await emailGenerator.generateAttachment(filename, attachmentContent, 'application/json')];
					} else {
						// Send email to main applicant and contributors if they have opted in to email notifications
						emailRecipients = [accessRecord.mainApplicant, ...accessRecord.authors];
					}
					// Send email
					if (!_.isEmpty(emailRecipients)) {
						await emailGenerator.sendEmail(
							emailRecipients,
							constants.hdrukEmail,
							`Data Access Request has been submitted to ${publisher} for ${datasetTitles}`,
							html,
							false,
							attachments
						);
					}
				}
				break;
			case constants.notificationTypes.RESUBMITTED:
				// 1. Create notifications
				// Custodian notification
				if (_.has(accessRecord.datasets[0], 'publisher.team.users')) {
					// Retrieve all custodian user Ids to generate notifications
					custodianManagers = teamController.getTeamMembersByRole(accessRecord.datasets[0].publisher.team, constants.roleTypes.MANAGER);
					custodianUserIds = custodianManagers.map(user => user.id);
					await notificationBuilder.triggerNotificationMessage(
						custodianUserIds,
						`A Data Access Request has been resubmitted with updates to ${publisher} for ${datasetTitles} by ${appFirstName} ${appLastName}`,
						'data access request',
						accessRecord._id
					);
				} else {
					const dataCustodianEmail = process.env.DATA_CUSTODIAN_EMAIL || contactPoint;
					custodianManagers = [{ email: dataCustodianEmail }];
				}
				// Applicant notification
				await notificationBuilder.triggerNotificationMessage(
					[accessRecord.userId],
					`Your Data Access Request for ${datasetTitles} was successfully resubmitted with updates to ${publisher}`,
					'data access request',
					accessRecord._id
				);
				// Contributors/authors notification
				if (!_.isEmpty(authors)) {
					await notificationBuilder.triggerNotificationMessage(
						accessRecord.authors.map(author => author.id),
						`A Data Access Request you are contributing to for ${datasetTitles} was successfully resubmitted with updates to ${publisher} by ${firstname} ${lastname}`,
						'data access request',
						accessRecord._id
					);
				}
				// 2. Send emails to custodian and applicant
				// Create object to pass to email generator
				options = {
					userType: '',
					userEmail: appEmail,
					publisher,
					datasetTitles,
					userName: `${appFirstName} ${appLastName}`,
				};
				// Iterate through the recipient types
				for (let emailRecipientType of constants.submissionEmailRecipientTypes) {
					// Establish email context object
					options = {
						...options,
						userType: emailRecipientType,
						submissionType: constants.submissionTypes.RESUBMISSION,
					};
					// Build email template
					({ html, jsonContent } = await emailGenerator.generateEmail(
						aboutApplication,
						questions,
						pages,
						questionPanels,
						questionAnswers,
						options
					));
					// Send emails to custodian team members who have opted in to email notifications
					if (emailRecipientType === 'dataCustodian') {
						emailRecipients = [...custodianManagers];
						// Generate json attachment for external system integration
						attachmentContent = Buffer.from(JSON.stringify({ id: accessRecord._id, ...jsonContent })).toString('base64');
						filename = `${helper.generateFriendlyId(accessRecord._id)} ${moment().format().toString()}.json`;
						attachments = [await emailGenerator.generateAttachment(filename, attachmentContent, 'application/json')];
					} else {
						// Send email to main applicant and contributors if they have opted in to email notifications
						emailRecipients = [accessRecord.mainApplicant, ...accessRecord.authors];
					}
					// Send email
					if (!_.isEmpty(emailRecipients)) {
						await emailGenerator.sendEmail(
							emailRecipients,
							constants.hdrukEmail,
							`Data Access Request to ${publisher} for ${datasetTitles} has been updated with updates`,
							html,
							false,
							attachments
						);
					}
				}
				break;
			case constants.notificationTypes.CONTRIBUTORCHANGE:
				// 1. Deconstruct authors array from context to compare with existing Mongo authors
				const { newAuthors, currentAuthors } = context;
				// 2. Determine authors who have been removed
				let addedAuthors = [...newAuthors].filter(author => !currentAuthors.includes(author));
				// 3. Determine authors who have been added
				let removedAuthors = [...currentAuthors].filter(author => !newAuthors.includes(author));
				// 4. Create emails and notifications for added/removed contributors
				// Set required data for email generation
				options = {
					id: accessRecord._id,
					projectName,
					projectId,
					datasetTitles,
					userName: `${appFirstName} ${appLastName}`,
					actioner: `${firstname} ${lastname}`,
					applicants,
				};
				// Notifications for added contributors
				if (!_.isEmpty(addedAuthors)) {
					options.change = 'added';
					html = emailGenerator.generateContributorEmail(options);
					// Find related user objects and filter out users who have not opted in to email communications
					let addedUsers = await UserModel.find({
						id: { $in: addedAuthors },
					}).populate('additionalInfo');

					await notificationBuilder.triggerNotificationMessage(
						addedUsers.map(user => user.id),
						`You have been added as a contributor for a Data Access Request to ${publisher} by ${firstname} ${lastname}`,
						'data access request',
						accessRecord._id
					);
					await emailGenerator.sendEmail(
						addedUsers,
						constants.hdrukEmail,
						`You have been added as a contributor for a Data Access Request to ${publisher} by ${firstname} ${lastname}`,
						html,
						false
					);
				}
				// Notifications for removed contributors
				if (!_.isEmpty(removedAuthors)) {
					options.change = 'removed';
					html = await emailGenerator.generateContributorEmail(options);
					// Find related user objects and filter out users who have not opted in to email communications
					let removedUsers = await UserModel.find({
						id: { $in: removedAuthors },
					}).populate('additionalInfo');

					await notificationBuilder.triggerNotificationMessage(
						removedUsers.map(user => user.id),
						`You have been removed as a contributor from a Data Access Request to ${publisher} by ${firstname} ${lastname}`,
						'data access request unlinked',
						accessRecord._id
					);
					await emailGenerator.sendEmail(
						removedUsers,
						constants.hdrukEmail,
						`You have been removed as a contributor from a Data Access Request to ${publisher} by ${firstname} ${lastname}`,
						html,
						false
					);
				}
				break;
			case constants.notificationTypes.STEPOVERRIDE:
				// 1. Create reviewer notifications
				notificationBuilder.triggerNotificationMessage(
					stepReviewerUserIds,
					`${firstname} ${lastname} has approved a Data Access Request application phase that you were assigned to review`,
					'data access request',
					accessRecord._id
				);
				// 2. Create reviewer emails
				options = {
					id: accessRecord._id,
					projectName,
					projectId,
					datasetTitles,
					userName: `${appFirstName} ${appLastName}`,
					actioner: `${firstname} ${lastname}`,
					applicants,
					dateSubmitted,
					...context,
				};
				html = emailGenerator.generateStepOverrideEmail(options);
				emailGenerator.sendEmail(
					stepReviewers,
					constants.hdrukEmail,
					`${firstname} ${lastname} has approved a Data Access Request application phase that you were assigned to review`,
					html,
					false
				);
				break;
			case constants.notificationTypes.REVIEWSTEPSTART:
				// 1. Create reviewer notifications
				notificationBuilder.triggerNotificationMessage(
					stepReviewerUserIds,
					`You are required to review a new Data Access Request application for ${publisher} by ${moment(currentDeadline).format(
						'D MMM YYYY HH:mm'
					)}`,
					'data access request',
					accessRecord._id
				);
				// 2. Create reviewer emails
				options = {
					id: accessRecord._id,
					projectName,
					projectId,
					datasetTitles,
					userName: `${appFirstName} ${appLastName}`,
					actioner: `${firstname} ${lastname}`,
					applicants,
					dateSubmitted,
					...context,
				};
				html = emailGenerator.generateNewReviewPhaseEmail(options);
				emailGenerator.sendEmail(
					stepReviewers,
					constants.hdrukEmail,
					`You are required to review a new Data Access Request application for ${publisher} by ${moment(currentDeadline).format(
						'D MMM YYYY HH:mm'
					)}`,
					html,
					false
				);
				break;
			case constants.notificationTypes.FINALDECISIONREQUIRED:
				// 1. Get managers for publisher
				custodianManagers = teamController.getTeamMembersByRole(accessRecord.publisherObj.team, constants.roleTypes.MANAGER);
				managerUserIds = custodianManagers.map(user => user.id);

				// 2. Create manager notifications
				notificationBuilder.triggerNotificationMessage(
					managerUserIds,
					`Action is required as a Data Access Request application for ${publisher} is now awaiting a final decision`,
					'data access request',
					accessRecord._id
				);
				// 3. Create manager emails
				options = {
					id: accessRecord._id,
					projectName,
					projectId,
					datasetTitles,
					userName: `${appFirstName} ${appLastName}`,
					actioner: `${firstname} ${lastname}`,
					applicants,
					dateSubmitted,
					...context,
				};
				html = emailGenerator.generateFinalDecisionRequiredEmail(options);
				emailGenerator.sendEmail(
					custodianManagers,
					constants.hdrukEmail,
					`Action is required as a Data Access Request application for ${publisher} is now awaiting a final decision`,
					html,
					false
				);
				break;
			case constants.notificationTypes.DEADLINEWARNING:
				// 1. Create reviewer notifications
				await notificationBuilder.triggerNotificationMessage(
					remainingReviewerUserIds,
					`The deadline is approaching for a Data Access Request application you are reviewing`,
					'data access request',
					accessRecord._id
				);
				// 2. Create reviewer emails
				options = {
					id: accessRecord._id,
					projectName,
					projectId,
					datasetTitles,
					userName: `${appFirstName} ${appLastName}`,
					actioner: `${firstname} ${lastname}`,
					applicants,
					workflowName,
					stepName,
					reviewSections,
					reviewerNames,
					nextStepName,
					dateDeadline,
				};
				html = await emailGenerator.generateReviewDeadlineWarning(options);
				await emailGenerator.sendEmail(
					remainingReviewers,
					constants.hdrukEmail,
					`The deadline is approaching for a Data Access Request application you are reviewing`,
					html,
					false
				);
				break;
			case constants.notificationTypes.DEADLINEPASSED:
				// 1. Get all managers
				custodianManagers = teamController.getTeamMembersByRole(accessRecord.publisherObj.team, constants.roleTypes.MANAGER);
				managerUserIds = custodianManagers.map(user => user.id);
				// 2. Combine managers and reviewers remaining
				let deadlinePassedUserIds = [...remainingReviewerUserIds, ...managerUserIds];
				let deadlinePassedUsers = [...remainingReviewers, ...custodianManagers];

				// 3. Create notifications
				await notificationBuilder.triggerNotificationMessage(
					deadlinePassedUserIds,
					`The deadline for a Data Access Request review phase has now elapsed`,
					'data access request',
					accessRecord._id
				);
				// 4. Create emails
				options = {
					id: accessRecord._id,
					projectName,
					projectId,
					datasetTitles,
					userName: `${appFirstName} ${appLastName}`,
					actioner: `${firstname} ${lastname}`,
					applicants,
					workflowName,
					stepName,
					reviewSections,
					reviewerNames,
					nextStepName,
					dateDeadline,
				};
				html = await emailGenerator.generateReviewDeadlinePassed(options);
				await emailGenerator.sendEmail(
					deadlinePassedUsers,
					constants.hdrukEmail,
					`The deadline for a Data Access Request review phase has now elapsed`,
					html,
					false
				);
				break;
			case constants.notificationTypes.WORKFLOWASSIGNED:
				// 1. Get managers for publisher
				custodianManagers = teamController.getTeamMembersByRole(accessRecord.datasets[0].publisher.team, constants.roleTypes.MANAGER);
				// 2. Get managerIds for notifications
				managerUserIds = custodianManagers.map(user => user.id);
				// 3. deconstruct and set options for notifications and email
				options = {
					id: accessRecord._id,
					steps,
					projectId,
					projectName,
					applicants,
					actioner: `${firstname} ${lastname}`,
					workflowName,
					dateSubmitted,
					datasetTitles,
				};
				// 4. Create notifications for the managers only
				await notificationBuilder.triggerNotificationMessage(
					managerUserIds,
					`Workflow of ${workflowName} has been assiged to an appplication`,
					'data access request',
					accessRecord._id
				);
				// 5. Generate the email
				html = await emailGenerator.generateWorkflowAssigned(options);
				// 6. Send email to custodian managers only within the team
				await emailGenerator.sendEmail(
					custodianManagers,
					constants.hdrukEmail,
					`A Workflow has been assigned to an application request`,
					html,
					false
				);
				break;
		}
	},

	createApplicationDTO: (app, userType, userId = '') => {
		let projectName = '',
			applicants = '',
			workflowName = '',
			workflowCompleted = false,
			remainingActioners = [],
			decisionDuration = '',
			decisionMade = false,
			decisionStatus = '',
			decisionComments = '',
			decisionDate = '',
			decisionApproved = false,
			managerUsers = [],
			stepName = '',
			deadlinePassed = '',
			reviewStatus = '',
			isReviewer = false,
			reviewPanels = [],
			amendmentStatus = '';

		// Check if the application has a workflow assigned
		let { workflow = {}, applicationStatus } = app;
		if (_.has(app, 'publisherObj.team.members')) {
			let {
				publisherObj: {
					team: { members, users },
				},
			} = app;
			let managers = members.filter(mem => {
				return mem.roles.includes('manager');
			});
			managerUsers = users
				.filter(user => managers.some(manager => manager.memberid.toString() === user._id.toString()))
				.map(user => {
					let isCurrentUser = user._id.toString() === userId.toString();
					return `${user.firstname} ${user.lastname}${isCurrentUser ? ` (you)` : ``}`;
				});
			if (
				applicationStatus === constants.applicationStatuses.SUBMITTED ||
				(applicationStatus === constants.applicationStatuses.INREVIEW && _.isEmpty(workflow))
			) {
				remainingActioners = managerUsers.join(', ');
			}
			if (!_.isEmpty(workflow)) {
				({ workflowName } = workflow);
				workflowCompleted = workflowController.getWorkflowCompleted(workflow);
				let activeStep = workflowController.getActiveWorkflowStep(workflow);
				// Calculate active step status
				if (!_.isEmpty(activeStep)) {
					({
						stepName = '',
						remainingActioners = [],
						deadlinePassed = '',
						reviewStatus = '',
						decisionMade = false,
						decisionStatus = '',
						decisionComments = '',
						decisionApproved,
						decisionDate,
						isReviewer = false,
						reviewPanels = [],
					} = workflowController.getActiveStepStatus(activeStep, users, userId));
					let activeStepIndex = workflow.steps.findIndex(step => {
						return step.active === true;
					});
					workflow.steps[activeStepIndex] = {
						...workflow.steps[activeStepIndex],
						reviewStatus,
					};
				} else if (_.isUndefined(activeStep) && applicationStatus === constants.applicationStatuses.INREVIEW) {
					reviewStatus = 'Final decision required';
					remainingActioners = managerUsers.join(', ');
				}
				// Get decision duration if completed
				let { dateFinalStatus, dateSubmitted } = app;
				if (dateFinalStatus) {
					decisionDuration = parseInt(moment(dateFinalStatus).diff(dateSubmitted, 'days'));
				}
				// Set review section to display format
				let formattedSteps = [...workflow.steps].reduce((arr, item) => {
					let step = {
						...item,
						sections: [...item.sections].map(section => constants.darPanelMapper[section]),
					};
					arr.push(step);
					return arr;
				}, []);
				workflow.steps = [...formattedSteps];
			}
		}

		// Ensure backward compatibility with old single dataset DARs
		if (_.isEmpty(app.datasets) || _.isUndefined(app.datasets)) {
			app.datasets = [app.dataset];
			app.datasetIds = [app.datasetid];
		}
		let {
			datasetfields: { publisher },
			name,
		} = app.datasets[0];
		let { aboutApplication, questionAnswers } = app;

		if (aboutApplication) {
			if (typeof aboutApplication === 'string') {
				aboutApplication = JSON.parse(aboutApplication);
			}
			({ projectName } = aboutApplication);
		}
		if (_.isEmpty(projectName)) {
			projectName = `${publisher} - ${name}`;
		}
		if (questionAnswers) {
			let questionAnswersObj = JSON.parse(questionAnswers);
			applicants = datarequestUtil.extractApplicantNames(questionAnswersObj).join(', ');
		}
		if (_.isEmpty(applicants)) {
			let { firstname, lastname } = app.mainApplicant;
			applicants = `${firstname} ${lastname}`;
		}
		amendmentStatus = amendmentController.calculateAmendmentStatus(app, userType);
		return {
			...app,
			projectName,
			applicants,
			publisher,
			workflowName,
			workflowCompleted,
			decisionDuration,
			decisionMade,
			decisionStatus,
			decisionComments,
			decisionDate,
			decisionApproved,
			remainingActioners,
			stepName,
			deadlinePassed,
			reviewStatus,
			isReviewer,
			reviewPanels,
			amendmentStatus,
		};
	},

	calculateAvgDecisionTime: applications => {
		// Extract dateSubmitted dateFinalStatus
		let decidedApplications = applications.filter(app => {
			let { dateSubmitted = '', dateFinalStatus = '' } = app;
			return !_.isEmpty(dateSubmitted.toString()) && !_.isEmpty(dateFinalStatus.toString());
		});
		// Find difference between dates in milliseconds
		if (!_.isEmpty(decidedApplications)) {
			let totalDecisionTime = decidedApplications.reduce((count, current) => {
				let { dateSubmitted, dateFinalStatus } = current;
				let start = moment(dateSubmitted);
				let end = moment(dateFinalStatus);
				let diff = end.diff(start, 'seconds');
				count += diff;
				return count;
			}, 0);
			// Divide by number of items
			if (totalDecisionTime > 0) return parseInt(totalDecisionTime / decidedApplications.length / 86400);
		}
		return 0;
	},
};
