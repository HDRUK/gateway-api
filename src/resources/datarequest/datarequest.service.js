import { isEmpty, has, isNil, orderBy, isNull, isUndefined } from 'lodash';
import moment from 'moment';

import helper from '../utilities/helper.util';
import datarequestUtil from '../datarequest/utils/datarequest.util';
import constants from '../utilities/constants.util';
import { processFile, fileStatus } from '../utilities/cloudStorage.util';
import { amendmentService } from '../datarequest/amendment/dependency';
import { activityLogService } from '../activitylog/dependency';
import mongoose from 'mongoose';

export default class DataRequestService {
	constructor(dataRequestRepository) {
		this.dataRequestRepository = dataRequestRepository;
	}

	async getAccessRequestsByUser(userId, query = {}) {
		return this.dataRequestRepository.getAccessRequestsByUser(userId, query);
	}

	getApplicationById(id) {
		return this.dataRequestRepository.getApplicationById(id);
	}

	getApplicationByDatasets(datasetIds, applicationStatus, userId) {
		return this.dataRequestRepository.getApplicationByDatasets(datasetIds, applicationStatus, userId);
	}

	getApplicationWithTeamById(id, options) {
		return this.dataRequestRepository.getApplicationWithTeamById(id, options);
	}

	getApplicationWithWorkflowById(id, options) {
		return this.dataRequestRepository.getApplicationWithWorkflowById(id, options);
	}

	getApplicationToSubmitById(id) {
		return this.dataRequestRepository.getApplicationToSubmitById(id);
	}

	getApplicationToUpdateById(id) {
		return this.dataRequestRepository.getApplicationToUpdateById(id);
	}

	getApplicationForUpdateRequest(id) {
		return this.dataRequestRepository.getApplicationForUpdateRequest(id);
	}

	getApplicationIsReadOnly(userType, applicationStatus) {
		let readOnly = true;
		if (userType === constants.userTypes.APPLICANT && applicationStatus === constants.applicationStatuses.INPROGRESS) {
			readOnly = false;
		}
		return readOnly;
	}

	getFilesForApplicationById(id, options) {
		return this.dataRequestRepository.getFilesForApplicationById(id, options);
	}

	getDatasetsForApplicationByIds(arrDatasetIds) {
		return this.dataRequestRepository.getDatasetsForApplicationByIds(arrDatasetIds);
	}

	linkRelatedApplicationByMessageContext(topicId, userId, datasetIds, applicationStatus) {
		return this.dataRequestRepository.linkRelatedApplicationByMessageContext(topicId, userId, datasetIds, applicationStatus);
	}

	async deleteApplication(accessRecord) {
		await this.dataRequestRepository.deleteApplicationById(accessRecord._id);

		Object.keys(accessRecord.versionTree).forEach(key => {
			if (accessRecord.versionTree[key].applicationId.toString() === accessRecord._id.toString()) {
				return delete accessRecord.versionTree[key];
			}
		});

		return await this.syncRelatedVersions(accessRecord.versionTree);
	}

	async shareApplication(accessRecord) {
		const { versionTree, _id: applicationId } = accessRecord;
		Object.keys(versionTree).forEach(key => {
			if (versionTree[key].applicationId.toString() === applicationId.toString()) {
				versionTree[key].isShared = true;
			}
		});

		await this.updateApplicationById(applicationId, { isShared: true });

		return await this.syncRelatedVersions(versionTree);
	}

	replaceApplicationById(id, newAcessRecord) {
		return this.dataRequestRepository.replaceApplicationById(id, newAcessRecord);
	}

	async buildApplicationForm(publisher, datasetIds, datasetTitles, userId, userObjectId) {
		// 1. Create new identifier for application
		const _id = mongoose.Types.ObjectId();

		// 2. Get schema to base application form on
		const dataRequestSchema = await this.dataRequestRepository.getApplicationFormSchema(publisher);

		// 3. Build up the accessModel for the user
		const { jsonSchema, _id: schemaId, questionSetStatus, isCloneable = false } = dataRequestSchema;

		// 4. Set form type
		const formType = schemaId.toString === constants.enquiryFormId ? constants.formTypes.Enquiry : constants.formTypes.Extended5Safe;

		// 5. Link any matching presubmission message topics to this application
		const presubmissionTopic = await this.linkRelatedPresubmissionTopic(_id, userObjectId, datasetIds, publisher);

		// 6. Create new DataRequestModel
		return {
			_id,
			userId,
			datasetIds,
			datasetTitles,
			isCloneable,
			jsonSchema,
			schemaId,
			publisher,
			questionAnswers: {},
			aboutApplication: {},
			applicationStatus: constants.applicationStatuses.INPROGRESS,
			formType,
			presubmissionTopic,
			questionSetStatus,
		};
	}

	async linkRelatedPresubmissionTopic(applicationId, userObjectId, datasetIds, publisher) {
		// Find a topic with matching datasets
		let topicId;
		const topic = await this.dataRequestRepository.getRelatedPresubmissionTopic(userObjectId, datasetIds);

		if (topic) {
			// If topic is found, create linkage from topic to application
			topicId = topic._id;
			topic.linkedDataAccessApplication = applicationId;
			topic.save(err => {
				if (!err) {
					// Create activity log entries based on existing messages in topic
					activityLogService.logActivity(constants.activityLogEvents.data_access_request.PRESUBMISSION_MESSAGE, {
						messages: topic.topicMessages,
						applicationId,
						publisher,
					});
				}
			});
		}

		return topicId;
	}

	async createApplication(data, applicationType = constants.submissionTypes.INITIAL, versionTree = {}) {
		let application = await this.dataRequestRepository.createApplication(data);

		if (applicationType === constants.submissionTypes.INITIAL) {
			application.projectId = helper.generateFriendlyId(application._id);
			application.createMajorVersion(1);
		} else {
			application.versionTree = versionTree;
			const versionNumber = application.findNextVersion();
			application.createMajorVersion(versionNumber);
		}

		application = await this.dataRequestRepository.updateApplicationById(application._id, application);

		return application;
	}

	validateRequestedVersion(accessRecord, requestedVersion) {
		let isValidVersion = true;

		// 1. Return base major version for specified access record if no specific version requested
		if (!requestedVersion && accessRecord) {
			return { isValidVersion, requestedMajorVersion: accessRecord.majorVersion, requestedMinorVersion: undefined };
		}

		// 2. Regex to validate and process the requested application version (e.g. 1, 2, 1.0, 1.1, 2.1, 3.11)
		let fullMatch, requestedMajorVersion, requestedMinorVersion;
		const regexMatch = requestedVersion.match(/^(\d+)$|^(\d+)\.?(\d+)$/); // lgtm [js/polynomial-redos]
		if (regexMatch) {
			fullMatch = regexMatch[0];
			requestedMajorVersion = regexMatch[1] || regexMatch[2];
			requestedMinorVersion = regexMatch[3] || regexMatch[2];
		}

		// 3. Catch invalid version requests
		try {
			let { majorVersion, amendmentIterations = [] } = accessRecord;
			majorVersion = parseInt(majorVersion);
			requestedMajorVersion = parseInt(requestedMajorVersion);
			if (requestedMinorVersion) {
				requestedMinorVersion = parseInt(requestedMinorVersion);
			} else if (requestedMajorVersion) {
				requestedMinorVersion = 0;
			}

			if (!fullMatch || majorVersion !== requestedMajorVersion || requestedMinorVersion > amendmentIterations.length) {
				isValidVersion = false;
			}
		} catch {
			isValidVersion = false;
		}

		return { isValidVersion, requestedMajorVersion, requestedMinorVersion };
	}

	buildVersionHistory = (versionTree, applicationId, requestedVersion, userType) => {
		const unsortedVersions = Object.keys(versionTree).reduce((arr, versionKey) => {
			const { applicationId: _id, link, displayTitle, detailedTitle, applicationStatus, isShared = false } = versionTree[versionKey];

			if (userType === constants.userTypes.CUSTODIAN && applicationStatus === constants.applicationStatuses.INPROGRESS && !isShared)
				return arr;

			const isCurrent = applicationId.toString() === _id.toString() && (requestedVersion === versionKey || !requestedVersion);

			const version = {
				number: versionKey,
				versionNumber: parseFloat(versionKey),
				_id,
				link,
				displayTitle,
				detailedTitle,
				isCurrent,
			};

			arr = [...arr, version];

			return arr;
		}, []);

		const orderedVersions = orderBy(unsortedVersions, ['versionNumber'], ['desc']);

		// If a current version is not found, this means an unpublished version is in progress with the Custodian, therefore we must select the previous available version
		if (!orderedVersions.some(v => v.isCurrent)) {
			const previousVersion = parseFloat(requestedVersion) - 0.1;
			const previousVersionIndex = orderedVersions.findIndex(v => parseFloat(v.number).toFixed(1) === previousVersion.toFixed(1));
			if (previousVersionIndex !== -1) {
				orderedVersions[previousVersionIndex].isCurrent = true;
			} else if (orderedVersions.length > 0) {
				orderedVersions[0].isCurrent = true;
			}
		}

		return orderedVersions;
	};

	getProjectName(accessRecord) {
		// Retrieve project name from about application section
		const { aboutApplication: { projectName } = {} } = accessRecord;
		if (projectName) {
			return projectName;
		} else if (accessRecord.datasets.length > 0) {
			// Build default project name from publisher and dataset name
			const {
				datasetfields: { publisher },
				name,
			} = accessRecord.datasets[0];
			return `${publisher} - ${name}`;
		} else {
			return 'No project name';
		}
	}

	getProjectNames(applications = []) {
		return [...applications].map(accessRecord => {
			const projectName = this.getProjectName(accessRecord);
			const { _id } = accessRecord;
			return { projectName, _id };
		});
	}

	getApplicantNames(accessRecord) {
		// Retrieve applicant names from form answers
		const { questionAnswers = {} } = accessRecord;
		let applicants = datarequestUtil.extractApplicantNames(questionAnswers);
		let applicantNames = '';
		// Return only main applicant if no applicants added
		if (isEmpty(applicants)) {
			if (isNull(accessRecord.mainApplicant)) {
				applicantNames = '';
			} else {
				const { firstname, lastname } = accessRecord.mainApplicant;
				applicantNames = `${firstname} ${lastname}`;
			}
		} else {
			applicantNames = applicants.join(', ');
		}
		return applicantNames;
	}

	getDecisionDuration(accessRecord) {
		const { dateFinalStatus, dateSubmitted } = accessRecord;
		if (dateFinalStatus && dateSubmitted) {
			return parseInt(moment(dateFinalStatus).diff(dateSubmitted, 'days'));
		} else {
			return '';
		}
	}

	updateApplicationById(id, data, options = {}) {
		return this.dataRequestRepository.updateApplicationById(id, data, options);
	}

	calculateAvgDecisionTime(accessRecords = []) {
		// Guard for empty array passed
		if (isEmpty(accessRecords)) return 0;
		// Extract dateSubmitted dateFinalStatus
		let decidedApplications = accessRecords.filter(app => {
			let { dateSubmitted = '', dateFinalStatus = '' } = app;
			return !isEmpty(dateSubmitted.toString()) && !isEmpty(dateFinalStatus.toString());
		});
		// Find difference between dates in milliseconds
		if (!isEmpty(decidedApplications)) {
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
	}

	buildUpdateObject(data) {
		let updateObj = {};
		let { aboutApplication, questionAnswers, updatedQuestionId, user, jsonSchema = '' } = data;
		if (aboutApplication) {
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

		if (!isEmpty(jsonSchema)) {
			updateObj = { ...updateObj, jsonSchema };
		}

		return updateObj;
	}

	async createAmendment(accessRecord) {
		const applicationType = constants.submissionTypes.AMENDED;
		const applicationStatus = constants.applicationStatuses.INPROGRESS;

		const {
			userId,
			authorIds,
			datasetIds,
			datasetTitles,
			projectId,
			questionAnswers,
			aboutApplication,
			publisher,
			files,
			versionTree,
			isShared = false,
		} = accessRecord;

		const { jsonSchema, _id: schemaId, isCloneable = false, formType } = await datarequestUtil.getLatestPublisherSchema(publisher);

		let amendedApplication = {
			applicationType,
			applicationStatus,
			userId,
			authorIds,
			datasetIds,
			initialDatasetIds: datasetIds,
			datasetTitles,
			isCloneable,
			projectId,
			schemaId,
			jsonSchema,
			questionAnswers,
			initialQuestionAnswers: questionAnswers,
			aboutApplication,
			publisher,
			formType,
			files,
			isShared,
		};

		if (questionAnswers && Object.keys(questionAnswers).length > 0 && datarequestUtil.containsUserRepeatedSections(questionAnswers)) {
			const updatedSchema = datarequestUtil.copyUserRepeatedSections(accessRecord, jsonSchema);
			amendedApplication.jsonSchema = updatedSchema;
		}

		amendedApplication = await this.createApplication(amendedApplication, applicationType, versionTree);

		await this.syncRelatedVersions(versionTree);

		return amendedApplication;
	}

	async updateApplication(accessRecord, updateObj) {
		// 1. Extract properties
		let { applicationStatus, _id } = accessRecord;
		let { updatedQuestionId = '', user } = updateObj;
		// 2. If application is in progress, update initial question answers
		if (applicationStatus === constants.applicationStatuses.INPROGRESS) {
			await this.dataRequestRepository.updateApplicationById(_id, updateObj, { new: true });
			// 3. Else if application has already been submitted make amendment
		} else if (
			applicationStatus === constants.applicationStatuses.INREVIEW ||
			applicationStatus === constants.applicationStatuses.SUBMITTED
		) {
			if (isNil(updateObj.questionAnswers)) {
				return accessRecord;
			}
			let updatedAnswer = updateObj.questionAnswers[updatedQuestionId];
			accessRecord = amendmentService.handleApplicantAmendment(accessRecord, updatedQuestionId, '', updatedAnswer, user);
			await this.dataRequestRepository.replaceApplicationById(_id, accessRecord);
		}
		return accessRecord;
	}

	async uploadFiles(accessRecord, files = [], descriptions, ids, userId) {
		let fileArr = [];
		// Check and see if descriptions and ids are an array
		const descriptionArray = Array.isArray(descriptions);
		const idArray = Array.isArray(ids);
		const initialApplicationId = accessRecord.getInitialApplicationId();

		// Process the files for scanning
		//lgtm [js/type-confusion-through-parameter-tampering]
		for (let i = 0; i < files.length; i++) {
			// Get description information
			let description = descriptionArray ? descriptions[i] : descriptions;
			// Get uniqueId
			let generatedId = idArray ? ids[i] : ids;
			// Remove - from uuidV4
			let uniqueId = generatedId.replace(/-/gim, '');
			// Send to db
			const response = await processFile(files[i], initialApplicationId, uniqueId);
			// Deconstruct response
			let { status } = response;
			// Setup fileArr for mongoo
			let newFile = {
				status: status.trim(),
				description: description.trim(),
				fileId: uniqueId,
				size: files[i].size,
				name: files[i].originalname,
				owner: userId,
				error: status === fileStatus.ERROR ? 'Could not upload. Unknown error. Please try again.' : '',
			};
			// Update local for post back to FE
			fileArr.push(newFile);
			// mongoo db update files array
			accessRecord.files.push(newFile);
		}
		// Write back into mongo [{userId, fileName, status: enum, size}]
		let updatedRecord = await this.dataRequestRepository.saveFileUploadChanges(accessRecord);

		// Process access record into object
		let record = updatedRecord._doc;
		// Fetch files
		let mediaFiles = record.files.map(f => {
			return f._doc;
		});

		return mediaFiles;
	}

	updateFileStatus(accessRecord, fileId, status) {
		// 1. Get all major version Ids to update file status against
		const versionIds = accessRecord.getRelatedVersionIds();

		// 2. Update all applications with file status
		this.dataRequestRepository.updateFileStatus(versionIds, fileId, status);
	}

	async doInitialSubmission(accessRecord) {
		// 1. Update application type and submitted status
		if (!accessRecord.applicationType) {
			accessRecord.applicationType = constants.submissionTypes.INITIAL;
		}
		accessRecord.applicationStatus = constants.applicationStatuses.SUBMITTED;
		// 2. Check if workflow/5 Safes based application, set final status date if status will never change again
		if (has(accessRecord.toObject(), 'publisherObj')) {
			if (!accessRecord.publisherObj.workflowEnabled) {
				accessRecord.dateFinalStatus = new Date();
				accessRecord.workflowEnabled = false;
			} else {
				accessRecord.workflowEnabled = true;
			}
		}
		const dateSubmitted = new Date();
		accessRecord.dateSubmitted = dateSubmitted;
		// 3. Update any connected version trees
		await this.updateVersionStatus(accessRecord, constants.applicationStatuses.SUBMITTED);
		// 4. Return updated access record for saving
		return accessRecord;
	}

	async doAmendSubmission(accessRecord, description) {
		// 1. Amend submission goes to submitted status with text reason for amendment
		accessRecord.applicationStatus = constants.applicationStatuses.SUBMITTED;
		accessRecord.submissionDescription = description;

		// 2. Set submission date as now
		const dateSubmitted = new Date();
		accessRecord.dateSubmitted = dateSubmitted;
		accessRecord.upadtedAt = dateSubmitted;

		// 3. Update any connected version trees
		await this.updateVersionStatus(accessRecord, constants.applicationStatuses.SUBMITTED);

		// 4. Return updated access record for saving
		return accessRecord;
	}

	async updateVersionStatus(accessRecord, newStatus) {
		Object.keys(accessRecord.versionTree).forEach(key => {
			if (accessRecord.versionTree[key].applicationId.toString() === accessRecord._id.toString()) {
				return (accessRecord.versionTree[key].applicationStatus = newStatus);
			}
		});

		return await this.syncRelatedVersions(accessRecord.versionTree);
	}

	syncRelatedVersions(versionTree) {
		// 1. Extract all major version _ids denoted by an application type on each node in the version tree
		const applicationIds = Object.keys(versionTree).reduce((arr, key) => {
			if (versionTree[key].applicationType) {
				arr.push(versionTree[key].applicationId);
			}
			return arr;
		}, []);
		// 2. Update all related applications
		this.dataRequestRepository.syncRelatedVersions(applicationIds, versionTree);
	}

	async checkUserAuthForVersions(versionIds, requestingUser) {
		const { _id: requestingUserObjectId, id: requestingUserId } = requestingUser;
		let requestingUserType;

		const requestedVersions = await this.dataRequestRepository.getPermittedUsersForVersions(versionIds);

		requestedVersions.forEach(accessRecord => {
			const { authorised, userType } = datarequestUtil.getUserPermissionsForApplication(
				accessRecord.toObject(),
				requestingUserId,
				requestingUserObjectId
			);

			if (!authorised) return { authorised };

			requestingUserType = userType;
		});

		return { authorised: true, userType: requestingUserType, accessRecords: requestedVersions };
	}

	async getDarContributors(darId, userId) {
		let contributors = await this.dataRequestRepository.getDarContributors(darId);
		let darContributors = [contributors[0].userId, ...contributors[0].authorIds];

		let darContributorsInfo = [];
		for (let contributor of darContributors) {
			let additionalInformation = await this.dataRequestRepository.getDarContributorsInfo(contributor, userId);
			darContributorsInfo.push(additionalInformation[0]);
		}

		darContributorsInfo.map(contributorInfo => {
			if (isUndefined(contributorInfo.user)) {
				helper.hidePrivateProfileDetails([contributorInfo]);
			}
		});

		return darContributorsInfo;
	}
}
