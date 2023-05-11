import _ from 'lodash';
import constants from '../utilities/constants.util';
import teamController from '../team/team.controller';
import Controller from '../base/controller';
import { logger } from '../utilities/logger';
import teamV3Util from '../utilities/team.v3.util';
import HttpExceptions from '../../exceptions/HttpExceptions';

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

			return res.status(200).json({ success: true, publisher });
		} catch (err) {
			logger.logError(err, logCategory);
			throw new HttpExceptions(`An error occurred fetching the custodian details`, 500);
		}
	}

	async getAllPublishersAndIds(res) {
		let publishers = await this.publisherService.getPublishersAndIds();
		return res.status(200).json({ publishers });
	}

	async getPublisherDatasets(req, res) {
		try {
			const { id } = req.params;
			let datasets = await this.publisherService.getPublisherDatasets(id).catch(err => {
				logger.logError(err, logCategory);
			});

			return res.status(200).json({ success: true, datasets });
		} catch (err) {
			logger.logError(err, logCategory);
			throw new HttpExceptions(`An error occurred searching for custodian datasets`, 500);
		}
	}

	async getPublisherDataAccessRequests(req, res) {
		try {
			const { _id: requestingUserId } = req.user;
			const { id } = req.params;

			const options = { lean: true, populate: [{ path: 'team' }, { path: 'members' }] };
			const publisher = await this.publisherService.getPublisher(id, options).catch(err => {
				logger.logError(err, logCategory);
			});
			if (!publisher) {
				throw new HttpExceptions(`Not Found`, 404);
			}
	
			//Check if current user is a manager
			const isManager = teamV3Util.checkUserRolesByTeam([constants.roleMemberTeam.CUST_DAR_MANAGER, constants.roleMemberTeam.CUST_DAR_REVIEWER], publisher.team, requestingUserId);

			// 4. Find all applications for current team member view
			// if I am custodian.dar.manager, reviewer
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

			return res.status(200).json({ success: true, data: modifiedApplications, avgDecisionTime, canViewSubmitted: isManager });
		} catch (err) {
			logger.logError(err, logCategory);
			throw new HttpExceptions(`An error occurred searching for custodian applications`, 500);
		}
	}

	async getPublisherWorkflows(req, res) {
		try {
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

			let authorised = teamV3Util.checkUserRolesByTeam([constants.roleMemberTeam.CUST_DAR_MANAGER], team, requestingUserId);
			if (!authorised) {
				throw new HttpExceptions(`User not authorized to perform this action`,403);
			}

			return res.status(200).json({ success: true, workflows });
		} catch (err) {
			logger.logError(err, logCategory);
			throw new HttpExceptions(`An error occurred searching for custodian workflows`, 500);
		}
	}

	async updateDataUseWidget(req, res) {
		try {
			await this.publisherService.updateDataUseWidget(req.params.id, req.body).then(() => {
				return res.status(200).json({ success: true });
			});
		} catch (err) {
			throw new HttpExceptions(`An error occurred updating data use widget settings`, 500);
		}
	}

	async updateDataRequestModalContent(req, res) {
		try {
			await this.publisherService.updateDataRequestModalContent(req.params.id, req.user.id, req.body.content).then(() => {
				return res.status(200).json({ success: true });
			});
		} catch (err) {
			throw new HttpExceptions(`An error occurred updating data request modal content`, 500);
		}
	}

	async updateQuestionBank(req, res) {
		try {
			await this.publisherService.updateQuestionBank(req.params.id, req.body).then(() => {
				return res.status(200).json({ success: true });
			});
		} catch (err) {
			throw new HttpExceptions(`An error occurred updating the question bank settings`, 500);
		}
	}
}
