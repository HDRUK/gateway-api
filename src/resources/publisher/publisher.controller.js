import _ from 'lodash';
import { PublisherModel } from './publisher.model';
import { Data } from '../tool/data.model';
import { DataRequestModel } from '../datarequest/datarequest.model';
import { WorkflowModel } from '../workflow/workflow.model';
import constants from '../utilities/constants.util';
import teamController from '../team/team.controller';

const datarequestController = require('../datarequest/datarequest.controller');

module.exports = {
	// GET api/v1/publishers/:id
	getPublisherById: async (req, res) => {
		try {
			// 1. Get the publisher from the database
			const publisher = await PublisherModel.findOne({ name: req.params.id });
			if (!publisher) {
				return res.status(200).json({
					success: true,
					publisher: { dataRequestModalContent: {}, allowsMessaging: false },
				});
			}
			// 2. Return publisher
			return res.status(200).json({ success: true, publisher });
		} catch (err) {
			console.error(err.message);
			return res.status(500).json(err.message);
		}
	},

	// GET api/v1/publishers/:id/datasets
	getPublisherDatasets: async (req, res) => {
		try {
			// 1. Get the datasets for the publisher from the database
			let datasets = await Data.find({
				type: 'dataset',
				activeflag: 'active',
				'datasetfields.publisher': req.params.id,
			})
				.populate('publisher')
				.select('datasetid name description datasetfields.abstract _id datasetfields.publisher datasetfields.contactPoint publisher');
			if (!datasets) {
				return res.status(404).json({ success: false });
			}
			// 2. Map datasets to flatten datasetfields nested object
			datasets = datasets.map(dataset => {
				let {
					_id,
					datasetid: datasetId,
					name,
					description,
					publisher: publisherObj,
					datasetfields: { abstract, publisher, contactPoint },
				} = dataset;
				return {
					_id,
					datasetId,
					name,
					description,
					abstract,
					publisher,
					publisherObj,
					contactPoint,
				};
			});
			// 3. Return publisher datasets
			return res.status(200).json({ success: true, datasets });
		} catch (err) {
			console.error(err.message);
			return res.status(500).json({
				success: false,
				message: 'An error occurred searching for custodian datasets',
			});
		}
	},

	// GET api/v1/publishers/:id/dataaccessrequests
	getPublisherDataAccessRequests: async (req, res) => {
		try {
			// 1. Deconstruct the request
			let { _id } = req.user;

			// 2. Lookup publisher team
			const publisher = await PublisherModel.findOne({ name: req.params.id }).populate('team', 'members').lean();
			if (!publisher) {
				return res.status(404).json({ success: false });
			}
			// 3. Check the requesting user is a member of the custodian team
			let found = false;
			if (_.has(publisher, 'team.members')) {
				let { members } = publisher.team;
				found = members.some(el => el.memberid.toString() === _id.toString());
			}

			if (!found) return res.status(401).json({ status: 'failure', message: 'Unauthorised' });

			//Check if current use is a manager
			let isManager = teamController.checkTeamPermissions(constants.roleTypes.MANAGER, publisher.team, _id);

			let applicationStatus = ['inProgress'];
			//If the current user is not a manager then push 'Submitted' into the applicationStatus array
			if (!isManager) {
				applicationStatus.push('submitted');
			}
			// 4. Find all datasets owned by the publisher (no linkage between DAR and publisher in historic data)
			let datasetIds = await Data.find({
				type: 'dataset',
				'datasetfields.publisher': req.params.id,
			}).distinct('datasetid');
			// 5. Find all applications where any datasetId exists
			let applications = await DataRequestModel.find({
				$and: [
					{
						$or: [{ dataSetId: { $in: datasetIds } }, { datasetIds: { $elemMatch: { $in: datasetIds } } }],
					},
					{ applicationStatus: { $nin: applicationStatus } },
				],
			})
				.select('-jsonSchema -questionAnswers -files')
				.sort({ updatedAt: -1 })
				.populate([
					{
						path: 'datasets dataset mainApplicant',
					},
					{
						path: 'publisherObj',
						populate: {
							path: 'team',
							populate: {
								path: 'users',
								select: 'firstname lastname',
							},
						},
					},
					{
						path: 'workflow.steps.reviewers',
						select: 'firstname lastname',
					},
				])
				.lean();

			if (!isManager) {
				applications = applications.filter(app => {
					let { workflow = {} } = app;
					if (_.isEmpty(workflow)) {
						return app;
					}

					let { steps = [] } = workflow;
					if (_.isEmpty(steps)) {
						return app;
					}

					let activeStepIndex = _.findIndex(steps, function (step) {
						return step.active === true;
					});

					let elapsedSteps = [...steps].slice(0, activeStepIndex + 1);
					let found = elapsedSteps.some(step => step.reviewers.some(reviewer => reviewer._id.equals(_id)));

					if (found) {
						return app;
					}
				});
			}

			// 6. Append projectName and applicants
			let modifiedApplications = [...applications]
				.map(app => {
					return datarequestController.createApplicationDTO(app, constants.userTypes.CUSTODIAN, _id.toString());
				})
				.sort((a, b) => b.updatedAt - a.updatedAt);

			let avgDecisionTime = datarequestController.calculateAvgDecisionTime(applications);
			// 7. Return all applications
			return res.status(200).json({ success: true, data: modifiedApplications, avgDecisionTime, canViewSubmitted: isManager });
		} catch (err) {
			console.error(err.message);
			return res.status(500).json({
				success: false,
				message: 'An error occurred searching for custodian applications',
			});
		}
	},

	// GET api/v1/publishers/:id/workflows
	getPublisherWorkflows: async (req, res) => {
		try {
			// 1. Get the workflow from the database including the team members to check authorisation
			let workflows = await WorkflowModel.find({
				publisher: req.params.id,
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
			if (_.isEmpty(workflows)) {
				return res.status(200).json({ success: true, workflows: [] });
			}
			// 2. Check the requesting user is a member of the team
			let { _id: userId } = req.user;
			let authorised = teamController.checkTeamPermissions(constants.roleTypes.MANAGER, workflows[0].publisher.team.toObject(), userId);
			// 3. If not return unauthorised
			if (!authorised) {
				return res.status(401).json({ success: false });
			}
			// 4. Build workflows
			workflows = workflows.map(workflow => {
				let { active, _id, id, workflowName, version, steps, applications = [] } = workflow.toObject();

				let formattedSteps = [...steps].reduce((arr, item) => {
					let step = {
						...item,
						displaySections: [...item.sections].map(section => constants.darPanelMapper[section]),
					};
					arr.push(step);
					return arr;
				}, []);

				applications = applications.map(app => {
					let { aboutApplication = {}, _id } = app;
					if (typeof aboutApplication === 'string') {
						aboutApplication = JSON.parse(aboutApplication) || {};
					}
					let { projectName = 'No project name' } = aboutApplication;
					return { projectName, _id };
				});
				let canDelete = applications.length === 0,
					canEdit = applications.length === 0;
				return {
					active,
					_id,
					id,
					workflowName,
					version,
					steps: formattedSteps,
					applications,
					appCount: applications.length,
					canDelete,
					canEdit,
				};
			});
			// 5. Return payload
			return res.status(200).json({ success: true, workflows });
		} catch (err) {
			console.error(err.message);
			return res.status(500).json({
				success: false,
				message: 'An error occurred searching for custodian workflows',
			});
		}
	},
};
