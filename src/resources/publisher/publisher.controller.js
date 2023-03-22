import _ from 'lodash';
import constants from '../utilities/constants.util';
import teamController from '../team/team.controller';
import Controller from '../base/controller';
import { logger } from '../utilities/logger';

const logCategory = 'Publisher';

export default class PublisherController extends Controller {
	constructor(publisherService, workflowService, dataRequestService, amendmentService) {
		super(publisherService);
		this.publisherService = publisherService;
		this.workflowService = workflowService;
		this.dataRequestService = dataRequestService;
		this.amendmentService = amendmentService;
	}

	async getPublisher(req, res) {
		try {
			// 1. Get the publisher from the database
			const { id } = req.params;
			const publisher = await this.publisherService.getPublisher(id).catch(err => {
				logger.logError(err, logCategory);
			});
			if (!publisher) {
				return res.status(200).json({
					success: true,
					publisher: { dataRequestModalContent: {}, allowsMessaging: false },
				});
			}
			// 2. Return publisher
			return res.status(200).json({ success: true, publisher });
		} catch (err) {
			// Return error response if something goes wrong
			logger.logError(err, logCategory);
			return res.status(500).json({
				success: false,
				message: 'An error occurred fetching the custodian details',
			});
		}
	}

	async getAllPublishersAndIds(res) {
		let publishers = await this.publisherService.getPublishersAndIds();
		return res.status(200).json({ publishers });
	}

	async getPublisherDatasets(req, res) {
		try {
			// 1. Get the datasets for the publisher from the database
			const { id } = req.params;
			let datasets = await this.publisherService.getPublisherDatasets(id).catch(err => {
				logger.logError(err, logCategory);
			});
			// 2. Return publisher datasets
			return res.status(200).json({ success: true, datasets });
		} catch (err) {
			// Return error response if something goes wrong
			logger.logError(err, logCategory);
			return res.status(500).json({
				success: false,
				message: 'An error occurred searching for custodian datasets',
			});
		}
	}

	async getPublisherDataAccessRequests(req, res) {
		try {
			// 1. Deconstruct the request
			const { _id: requestingUserId } = req.user;
			const { id } = req.params;

			// 2. Lookup publisher team
			const options = { lean: true, populate: [{ path: 'team' }, { path: 'members' }] };
			const publisher = await this.publisherService.getPublisher(id, options).catch(err => {
				logger.logError(err, logCategory);
			});
			if (!publisher) {
				return res.status(404).json({ success: false });
			}
			// 3. Check the requesting user is a member of the custodian team
			const isAuthenticated = teamController.checkTeamPermissions('', publisher.team, requestingUserId);
			if (!isAuthenticated) return res.status(401).json({ status: 'failure', message: 'Unauthorised' });

			//Check if current user is a manager
			const isManager = teamController.checkTeamPermissions(constants.roleTypes.MANAGER, publisher.team, requestingUserId);

			// 4. Find all applications for current team member view
			const applications = await this.publisherService.getPublisherDataAccessRequests(id, requestingUserId, isManager).catch(err => {
				logger.logError(err, logCategory);
			});

			// 5. Append projectName and applicants
			const modifiedApplications = [...applications]
				.map(accessRecord => {
					accessRecord = this.workflowService.getWorkflowDetails(accessRecord, requestingUserId);
					accessRecord.projectName = this.dataRequestService.getProjectName(accessRecord);
					accessRecord.applicants = this.dataRequestService.getApplicantNames(accessRecord);
					accessRecord.decisionDuration = this.dataRequestService.getDecisionDuration(accessRecord);
					accessRecord.versions = this.dataRequestService.buildVersionHistory(
						accessRecord.versionTree,
						accessRecord._id,
						null,
						constants.userTypes.CUSTODIAN
					);
					accessRecord.amendmentStatus = this.amendmentService.calculateAmendmentStatus(accessRecord, constants.userTypes.CUSTODIAN);
					return accessRecord;
				})
				.sort((a, b) => b.updatedAt - a.updatedAt);

			const avgDecisionTime = this.dataRequestService.calculateAvgDecisionTime(applications);
			// 6. Return all applications
			return res.status(200).json({ success: true, data: modifiedApplications, avgDecisionTime, canViewSubmitted: isManager });
		} catch (err) {
			// Return error response if something goes wrong
			logger.logError(err, logCategory);
			return res.status(500).json({
				success: false,
				message: 'An error occurred searching for custodian applications',
			});
		}
	}

	async getPublisherWorkflows(req, res) {
		try {
			// 1. Get the workflow from the database including the team members to check authorisation
			const { id } = req.params;
			let workflows = await this.workflowService.getWorkflowsByPublisher(id).catch(err => {
				logger.logError(err, logCategory);
			});
			if (_.isEmpty(workflows)) {
				return res.status(200).json({ success: true, workflows: [] });
			}
			// 2. Get attached data access request application project names
			workflows = workflows.map(workflow => {
				let { applications = [] } = workflow;
				return {
					...workflow,
					applications: this.dataRequestService.getProjectNames(applications),
				};
			});
			// 3. Check the requesting user is a member of the team
			const { _id: requestingUserId } = req.user;
			const {
				publisher: { team },
			} = workflows[0];
			const authorised = teamController.checkTeamPermissions(constants.roleTypes.MANAGER, team, requestingUserId);
			// 4. If not return unauthorised
			if (!authorised) {
				return res.status(401).json({ success: false });
			}
			// 5. Return payload
			return res.status(200).json({ success: true, workflows });
		} catch (err) {
			// Return error response if something goes wrong
			logger.logError(err, logCategory);
			return res.status(500).json({
				success: false,
				message: 'An error occurred searching for custodian workflows',
			});
		}
	}

	async updateDataUseWidget(req, res) {
		try {
			await this.publisherService.updateDataUseWidget(req.params.id, req.body).then(() => {
				return res.status(200).json({ success: true });
			});
		} catch (err) {
			return res.status(500).json({
				success: false,
				message: 'An error occurred updating data use widget settings',
			});
		}
	}

	async updateDataRequestModalContent(req, res) {
		try {
			await this.publisherService.updateDataRequestModalContent(req.params.id, req.user.id, req.body.content).then(() => {
				return res.status(200).json({ success: true });
			});
		} catch (err) {
			return res.status(500).json({
				success: false,
				message: 'An error occurred updating data request modal content',
			});
		}
	}

	async updateQuestionBank(req, res) {
		try {
			await this.publisherService.updateQuestionBank(req.params.id, req.body).then(() => {
				return res.status(200).json({ success: true });
			});
		} catch (err) {
			return res.status(500).json({
				success: false,
				message: 'An error occurred updating the question bank settings',
			});
		}
	}
}
