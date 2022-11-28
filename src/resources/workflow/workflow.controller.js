import _ from 'lodash';
import Mongoose from 'mongoose';

import { PublisherModel } from '../publisher/publisher.model';
import { DataRequestModel } from '../datarequest/datarequest.model';
import { WorkflowModel } from './workflow.model';
import teamController from '../team/team.controller';
import helper from '../utilities/helper.util';
import constants from '../utilities/constants.util';
import Controller from '../base/controller';

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
				return res.status(404).json({ success: false });
			}
			// 2. Check the requesting user is a manager of the custodian team
			let { _id: userId } = req.user;
			let authorised = teamController.checkTeamPermissions(constants.roleTypes.MANAGER, workflow.publisher.team.toObject(), userId);
			// 3. If not return unauthorised
			if (!authorised) {
				return res.status(401).json({ success: false });
			}
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
			process.stdout.write(`WORKFLOW - getWorkflowById : ${err.message}\n`);
			return res.status(500).json({
				success: false,
				message: 'An error occurred searching for the specified workflow',
			});
		}
	}

	async createWorkflow(req, res) {
		try {
			const { _id: userId, firstname, lastname } = req.user;
			// 1. Look at the payload for the publisher passed
			const { workflowName = '', publisher = '', steps = [] } = req.body;
			if (_.isEmpty(workflowName.trim()) || _.isEmpty(publisher.trim()) || _.isEmpty(steps)) {
				return res.status(400).json({
					success: false,
					message: 'You must supply a workflow name, publisher, and at least one step definition to create a workflow',
				});
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
				return res.status(400).json({
					success: false,
					message: 'You must supply a valid publisher to create the workflow against',
				});
			}
			// 3. Check the requesting user is a manager of the custodian team
			let authorised = teamController.checkTeamPermissions(constants.roleTypes.MANAGER, publisherObj.team.toObject(), userId);

			// 4. Refuse access if not authorised
			if (!authorised) {
				return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
			}
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
					return res.status(400).json({
						success: false,
						message: err.message,
					});
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
			process.stdout.write(`WORKFLOW - createWorkflow : ${err.message}\n`);
			return res.status(500).json({
				success: false,
				message: 'An error occurred creating the workflow',
			});
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
				return res.status(404).json({ success: false });
			}
			// 2. Check the requesting user is a manager of the custodian team
			let authorised = teamController.checkTeamPermissions(constants.roleTypes.MANAGER, workflow.publisher.team.toObject(), userId);
			// 3. Refuse access if not authorised
			if (!authorised) {
				return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
			}
			// 4. Ensure there are no in-review DARs with this workflow
			const applications = await DataRequestModel.countDocuments({
				workflowId,
				applicationStatus: 'inReview',
			});
			if (applications > 0) {
				return res.status(400).json({
					success: false,
					message: 'A workflow which is attached to applications currently in review cannot be edited',
				});
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
						return res.status(400).json({
							success: false,
							message: err.message,
						});
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
					return res.status(400).json({
						success: false,
						message: 'You must supply a valid publisher to create the workflow against',
					});
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
			process.stdout.write(`WORKFLOW - updateWorkflow : ${err.message}\n`);
			return res.status(500).json({
				success: false,
				message: 'An error occurred editing the workflow',
			});
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
				return res.status(404).json({ success: false });
			}
			// 2. Check the requesting user is a manager of the custodian team
			let authorised = teamController.checkTeamPermissions(constants.roleTypes.MANAGER, workflow.publisher.team.toObject(), userId);
			// 3. Refuse access if not authorised
			if (!authorised) {
				return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
			}
			// 4. Ensure there are no in-review DARs with this workflow
			const applications = await DataRequestModel.countDocuments({
				workflowId,
				applicationStatus: 'inReview',
			});
			if (applications > 0) {
				return res.status(400).json({
					success: false,
					message: 'A workflow which is attached to applications currently in review cannot be deleted',
				});
			}
			const detailedWorkflow = await WorkflowModel.findById(workflowId).populate({
				path: 'steps.reviewers',
				select: 'firstname lastname email -_id',
			}).lean();

			// 5. Delete workflow
			WorkflowModel.deleteOne({ _id: workflowId }, function (err) {
				if (err) {
					process.stdout.write(`WORKFLOW - deleteOne : ${err.message}\n`);
					return res.status(400).json({
						success: false,
						message: 'An error occurred deleting the workflow',
					});
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
				return res.status(400).json({
					success: false,
					message: 'You must supply a valid publisher to create the workflow against',
				});
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
			process.stdout.write(`WORKFLOW - deleteWorkflow : ${err.message}\n`);
			return res.status(500).json({
				success: false,
				message: 'An error occurred deleting the workflow',
			});
		}
	}
}
