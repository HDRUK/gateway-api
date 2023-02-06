import _ from 'lodash';
import Mongoose from 'mongoose';

import { PublisherModel } from '../publisher/publisher.model';
import { DataRequestModel } from '../datarequest/datarequest.model';
import { WorkflowModel } from './workflow.model';
import teamV3Util from '../utilities/team.v3.util';
import helper from '../utilities/helper.util';
import constants from '../utilities/constants.util';
import Controller from '../base/controller';
import HttpExceptions from '../../exceptions/HttpExceptions';

export default class WorkflowController extends Controller {
	constructor(workflowService) {
		super(workflowService);
		this.workflowService = workflowService;
	}

	async getWorkflowById(req, res) {
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
				throw new HttpExceptions(`Workflow not found`, 404);
			}
			// 2. Check the requesting user is a manager of the custodian team
			let { _id: userId } = req.user;
			teamV3Util.checkUserRolesByTeam([constants.roleMemberTeam.CUST_DAR_MANAGER], workflow.publisher.team.toObject(), userId);

			// 4. Build workflow response
			let { active, _id, id, workflowName, version, steps, applications = [] } = workflow.toObject();
			applications = applications.map(app => {
				let { aboutApplication = {}, _id } = app;
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
			throw new HttpExceptions(`An error occurred searching for the specified workflow`, 500);
		}
	}

	async createWorkflow(req, res) {
		try {
			const { _id: userId, firstname, lastname } = req.user;
			// 1. Look at the payload for the publisher passed
			const { workflowName = '', publisher = '', steps = [] } = req.body;
			if (_.isEmpty(workflowName.trim()) || _.isEmpty(publisher.trim()) || _.isEmpty(steps)) {
				throw new HttpExceptions(`You must supply a workflow name, publisher, and at least one step definition to create a workflow`, 400);
			}
			// 2. Look up publisher and team
			const publisherObj = await PublisherModel.findOne({ //lgtm [js/sql-injection]
				_id: publisher,
			}).populate({
				path: 'team members',
				populate: {
					path: 'users',
					select: '_id id email firstname lastname',
				},
			});

			if (!publisherObj) {
				throw new HttpExceptions(`You must supply a valid publisher to create the workflow against`, 400);
			}
			teamV3Util.checkUserRolesByTeam([constants.roleMemberTeam.CUST_DAR_MANAGER], publisherObj.team.toObject(), userId);

			// 5. Create new workflow model
			const id = helper.generatedNumericId();
			// 6. set workflow obj for saving
			let workflow = new WorkflowModel({
				id,
				workflowName,
				publisher,
				steps,
				createdBy: new Mongoose.Types.ObjectId(userId),
			});
			// 7. save new workflow to db
			workflow = await workflow.save().catch(err => {
				if (err) {
					throw new HttpExceptions(`ERROR SAVE WORKFLOW: ${err.message}`, 400);
				}
			});
			// 8. populate the workflow with the needed fields for our new notification and email
			const detailedWorkflow = await WorkflowModel.findById(workflow._id).populate({
				path: 'steps.reviewers',
				select: 'firstname lastname email -_id',
			}).lean();
			// 9. set context
			let context = {
				publisherObj: publisherObj.team.toObject(),
				actioner: `${firstname} ${lastname}`,
				workflow: detailedWorkflow,
			};
			// 10. Generate new notifications / emails for managers of the team only on creation of a workflow
			this.workflowService.createNotifications(context, constants.notificationTypes.WORKFLOWCREATED);
			// 11. full complete return
			return res.status(201).json({
				success: true,
				workflow: detailedWorkflow,
			});
		} catch (err) {
			console.error(err.message);
			throw new HttpExceptions(`An error occurred creating the workflow`, 500);
		}
	}

	async updateWorkflow(req, res) {
		try {
			const { _id: userId, firstname, lastname } = req.user;
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
				throw new HttpExceptions(`Workflow not Found`, 404);
			}
			teamV3Util.checkUserRolesByTeam([constants.roleMemberTeam.CUST_DAR_MANAGER], workflow.publisher.team.toObject(), userId);

			// 4. Ensure there are no in-review DARs with this workflow
			const applications = await DataRequestModel.countDocuments({
				workflowId,
				applicationStatus: 'inReview',
			});
			if (applications > 0) {
				throw new HttpExceptions(`A workflow which is attached to applications currently in review cannot be edited`, 400);
			}
			// 5. Edit workflow
			const { workflowName = '', publisher = '', steps = [] } = req.body;
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
				workflow = await workflow.save().catch(err => {
					if (err) {
						throw new HttpExceptions(`ERROR SAVE WORKFLOW: ${err.message}`, 400);
					}
				});

				const publisherObj = await PublisherModel.findOne({
					_id: publisher,
				}).populate({
					path: 'team members',
					populate: {
						path: 'users',
						select: '_id id email firstname lastname',
					},
				});
				if (!publisherObj) {
					throw new HttpExceptions(`You must supply a valid publisher to create the workflow against`, 400);
				}
				const detailedWorkflow = await WorkflowModel.findById(workflow._id).populate({
					path: 'steps.reviewers',
					select: 'firstname lastname email -_id',
				}).lean();
				let context = {
					publisherObj: publisherObj.team.toObject(),
					actioner: `${firstname} ${lastname}`,
					workflow: detailedWorkflow,
				};
				this.workflowService.createNotifications(context, constants.notificationTypes.WORKFLOWUPDATED);
				
				return res.status(204).json({
					success: true,
					workflow,
				});
			} else {
				return res.status(200).json({
					success: true,
				});
			}
		} catch (err) {
			console.error(err.message);
			throw new HttpExceptions(`An error occurred editing the workflow`, 500);
		}
	}

	async deleteWorkflow(req, res) {
		try {
			const { _id: userId, firstname, lastname } = req.user;
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
			const { workflowName = '', publisher = {}, steps = [] } = workflow;

			if (!workflow) {
				throw new HttpExceptions(`Workflow not Found`, 404);
			}
			teamV3Util.checkUserRolesByTeam([constants.roleMemberTeam.CUST_DAR_MANAGER], workflow.publisher.team.toObject(), userId);

			// 4. Ensure there are no in-review DARs with this workflow
			const applications = await DataRequestModel.countDocuments({
				workflowId,
				applicationStatus: 'inReview',
			});
			if (applications > 0) {
				throw new HttpExceptions(`A workflow which is attached to applications currently in review cannot be deleted`, 400);
			}
			const detailedWorkflow = await WorkflowModel.findById(workflowId).populate({
				path: 'steps.reviewers',
				select: 'firstname lastname email -_id',
			}).lean();

			// 5. Delete workflow
			WorkflowModel.deleteOne({ _id: workflowId }, function (err) {
				if (err) {
					console.error(err.message);
					throw new HttpExceptions(`An error occurred deleting the workflow`, 400);
				}
			});
			const publisherObj = await PublisherModel.findOne({
				_id: publisher._id,
			}).populate({
				path: 'team members',
				populate: {
					path: 'users',
					select: '_id id email firstname lastname',
				},
			});
			if (!publisherObj) {
				throw new HttpExceptions(`You must supply a valid publisher to create the workflow against`, 400);
			}
			let context = {
				publisherObj: publisherObj.team.toObject(),
				actioner: `${firstname} ${lastname}`,
				workflow: detailedWorkflow,
			};
			this.workflowService.createNotifications(context, constants.notificationTypes.WORKFLOWDELETED);

			return res.status(204).json({
				success: true,
			});
		} catch (err) {
			console.error(err.message);
			throw new HttpExceptions(`An error occurred deleting the workflow`, 500);
		}
	}
}
