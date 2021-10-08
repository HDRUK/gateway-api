import moment from 'moment';
import { isEmpty } from 'lodash';
import DataUseRegister from './dataUseRegister.entity';
import { getUsersByIds } from '../user/user.repository';
import { datasetService } from '../dataset/dependency';

/**
 * Build Data Use Registers
 *
 * @desc    Accepts a creator user object, the custodian/publisher team identifier to create the data use registers against and an array of data use POJOs to map to data use models.
 * 			The function drops out invalid dates, empty fields and removes white space from all strings before constructing the model instances.
 * @param 	{String} 	creatorUser 	    	User object from the authenticated request who is creating the data use registers
 * @param 	{String} 	teamId 	    			Custodian/publisher team identifier to identify who to create the data uses against
 * @param 	{String} 	dataUses 	    		Array of data use register shaped POJOs to map to data use models
 * @returns {Array<Object>}						Array of data use register models
 */
const buildDataUseRegisters = async (creatorUser, teamId, dataUses = []) => {
	const dataUseRegisters = [];

	for (const obj of dataUses) {
		// Handle dataset linkages
		const { linkedDatasets = [], namedDatasets = [] } = await getLinkedDatasets(
			obj.datasetNames &&
				obj.datasetNames
					.toString()
					.split(',')
					.map(el => {
						if (!isEmpty(el)) return el.trim();
					})
		);
		const datasetTitles = [...linkedDatasets.map(dataset => dataset.name), ...namedDatasets];
		const datasetIds = [...linkedDatasets.map(dataset => dataset.datasetid)];
		const datasetPids = [...linkedDatasets.map(dataset => dataset.pid)];

		// Handle applicant linkages
		const { gatewayApplicants, nonGatewayApplicants } = await getLinkedApplicants(
			obj.applicantNames &&
				obj.applicantNames
					.toString()
					.split(',')
					.map(el => {
						if (!isEmpty(el)) return el.trim();
					})
		);

		// Create related objects
		const relatedObjects = buildRelatedDatasets(creatorUser, linkedDatasets);

		// Handle comma separated fields
		const fundersAndSponsors =
			obj.fundersAndSponsors &&
			obj.fundersAndSponsors
				.toString()
				.split(',')
				.map(el => {
					if (!isEmpty(el)) return el.trim();
				});
		const researchOutputs =
			obj.researchOutputs &&
			obj.researchOutputs
				.toString()
				.split(',')
				.map(el => {
					if (!isEmpty(el)) return el.trim();
				});
		const otherApprovalCommittees =
			obj.otherApprovalCommittees &&
			obj.otherApprovalCommittees
				.toString()
				.split(',')
				.map(el => {
					if (!isEmpty(el)) return el.trim();
				});

		// Handle expected dates
		const projectStartDate = moment(obj.projectStartDate, 'YYYY-MM-DD');
		const projectEndDate = moment(obj.projectEndDate, 'YYYY-MM-DD');
		const latestApprovalDate = moment(obj.latestApprovalDate, 'YYYY-MM-DD');
		const accessDate = moment(obj.accessDate, 'YYYY-MM-DD');

		// Clean and assign to model
		dataUseRegisters.push(
			new DataUseRegister({
				...(obj.projectTitle && { projectTitle: obj.projectTitle.toString().trim() }),
				...(obj.projectIdText && { projectIdText: obj.projectIdText.toString().trim() }),
				...(obj.organisationName && { organisationName: obj.organisationName.toString().trim() }),
				...(obj.organisationSector && { organisationSector: obj.organisationSector.toString().trim() }),
				...(obj.applicantId && { applicantId: obj.applicantId.toString().trim() }),
				...(obj.accreditedResearcherStatus && { accreditedResearcherStatus: obj.accreditedResearcherStatus.toString().trim() }),
				...(obj.sublicenceArrangements && { sublicenceArrangements: obj.sublicenceArrangements.toString().trim() }),
				...(obj.laySummary && { laySummary: obj.laySummary.toString().trim() }),
				...(obj.publicBenefitStatement && { publicBenefitStatement: obj.publicBenefitStatement.toString().trim() }),
				...(obj.requestCategoryType && { requestCategoryType: obj.requestCategoryType.toString().trim() }),
				...(obj.technicalSummary && { technicalSummary: obj.technicalSummary.toString().trim() }),
				...(obj.dataSensitivityLevel && { dataSensitivityLevel: obj.dataSensitivityLevel.toString().trim() }),
				...(obj.legalBasisForDataArticle6 && { legalBasisForDataArticle6: obj.legalBasisForDataArticle6.toString().trim() }),
				...(obj.legalBasisForDataArticle9 && { legalBasisForDataArticle9: obj.legalBasisForDataArticle9.toString().trim() }),
				...(obj.nationalDataOptOut && { nationalDataOptOut: obj.nationalDataOptOut.toString().trim() }),
				...(obj.requestFrequency && { requestFrequency: obj.requestFrequency.toString().trim() }),
				...(obj.datasetLinkageDescription && { datasetLinkageDescription: obj.datasetLinkageDescription.toString().trim() }),
				...(obj.confidentialDataDescription && { confidentialDataDescription: obj.confidentialDataDescription.toString().trim() }),
				...(obj.dataLocation && { dataLocation: obj.dataLocation.toString().trim() }),
				...(obj.privacyEnhancements && { privacyEnhancements: obj.privacyEnhancements.toString().trim() }),
				...(projectStartDate.isValid() && { projectStartDate }),
				...(projectEndDate.isValid() && { projectEndDate }),
				...(latestApprovalDate.isValid() && { latestApprovalDate }),
				...(accessDate.isValid() && { accessDate }),
				...(!isEmpty(datasetTitles) && { datasetTitles }),
				...(!isEmpty(datasetIds) && { datasetIds }),
				...(!isEmpty(datasetPids) && { datasetPids }),
				...(!isEmpty(gatewayApplicants) && { gatewayApplicants }),
				...(!isEmpty(nonGatewayApplicants) && { nonGatewayApplicants }),
				...(!isEmpty(fundersAndSponsors) && { fundersAndSponsors }),
				...(!isEmpty(researchOutputs) && { researchOutputs }),
				...(!isEmpty(otherApprovalCommittees) && { otherApprovalCommittees }),
				...(!isEmpty(relatedObjects) && { relatedObjects }),
				activeflag: 'inReview',
				publisher: teamId,
				user: creatorUser._id,
				updatedon: Date.now(),
				lastActivity: Date.now(),
				manualUpload: true,
			})
		);
	}

	return dataUseRegisters;
};

/**
 * Get Linked Datasets
 *
 * @desc    Accepts a comma separated string containing dataset names which can be in the form of text based names or URLs belonging to the Gateway which resolve to a dataset page, or a mix of both.
 * 			The function separates URLs and uses regex to locate a suspected dataset PID to use in a search against the Gateway database.  If a match is found, the entry is considered a linked dataset.
 * 			Entries which cannot be matched are returned as named datasets.
 * @param 	{String} 	datasetNames 	    	A comma separated string representation of the dataset names to attempt to find and link to existing Gateway datasets
 * @returns {Object}							An object containing linked and named datasets in separate arrays
 */
const getLinkedDatasets = async (datasetNames = []) => {
	const unverifiedDatasetPids = [];
	const namedDatasets = [];
	const validLinkRegexp = new RegExp(`^${process.env.homeURL}\/dataset\/([a-f|\\d|-]+)\/?$`, 'i');

	for (const datasetName of datasetNames) {
		const [, datasetPid] = validLinkRegexp.exec(datasetName) || [];
		if (datasetPid) {
			unverifiedDatasetPids.push(datasetPid);
		} else {
			namedDatasets.push(datasetName);
		}
	}

	const linkedDatasets = isEmpty(unverifiedDatasetPids)
		? []
		: (await datasetService.getDatasetsByPids(unverifiedDatasetPids)).map(dataset => {
				return { datasetid: dataset.datasetid, name: dataset.name, pid: dataset.pid };
		  });

	return { linkedDatasets, namedDatasets };
};

/**
 * Get Linked Applicants
 *
 * @desc    Accepts a comma separated string containing applicant names which can be in the form of text based names or URLs belonging to the Gateway which resolve to a users profile page, or a mix of both.
 * 			The function separates URLs and uses regex to locate a suspected user ID to use in a search against the Gateway database.  If a match is found, the entry is considered a Gateway applicant.
 * 			Entries which cannot be matched are returned as non Gateway applicants.  Failed attempts at adding URLs which do not resolve are excluded.
 * @param 	{String} 	datasetNames 	    	A comma separated string representation of the applicant(s) names to attempt to find and link to existing Gateway users
 * @returns {Object}							An object containing Gateway applicants and non Gateway applicants in separate arrays
 */
const getLinkedApplicants = async (applicantNames = []) => {
	const unverifiedUserIds = [];
	const nonGatewayApplicants = [];
	const validLinkRegexp = new RegExp(`^${process.env.homeURL}\/person\/(\\d+)\/?$`, 'i');

	for (const applicantName of applicantNames) {
		const [, userId] = validLinkRegexp.exec(applicantName) || [];
		if (userId) {
			unverifiedUserIds.push(userId);
		} else {
			nonGatewayApplicants.push(applicantName);
		}
	}

	const gatewayApplicants = isEmpty(unverifiedUserIds) ? [] : (await getUsersByIds(unverifiedUserIds)).map(el => el._id);

	return { gatewayApplicants, nonGatewayApplicants };
};

/**
 * Build Related Datasets
 *
 * @desc    Accepts an array of datasets and outputs an array of related objects which can be assigned to an entity to show the relationship to the datasets.
 * 			Related objects contain the 'objectId' (dataset version identifier), 'pid', 'objectType' (dataset), 'updated' date and 'user' that created the linkage.
 * @param 	{Object} 			creatorUser 	A user object to allow the assignment of their name to the creator of the linkage
 * @param 	{Array<Object>} 	datasets 	    An array of dataset objects containing the necessary properties to assemble a related object record reference
 * @returns {Array<Object>}						An array containing the assembled related objects relative to the datasets provided
 */
const buildRelatedDatasets = (creatorUser, datasets = [], manualUpload = true) => {
	const { firstname, lastname } = creatorUser;
	return datasets.map(dataset => {
		const { datasetid: objectId, pid } = dataset;
		return {
			objectId,
			pid,
			objectType: 'dataset',
			user: `${firstname} ${lastname}`,
			updated: Date.now(),
			isLocked: true,
			reason: manualUpload
				? 'This dataset was added automatically during the manual upload of this data use register'
				: 'This dataset was added automatically from an approved data access request',
		};
	});
};

/**
 * Extract Form Applicants
 *
 * @desc    Accepts an array of authors and object containing answers from a Data Access Request application and extracts the names of non Gateway applicants as provided in the form,
 * and extracts registered Gateway applicants, combining them before de-duplicating where match is found.
 * @param 	{Array<Object>} 			authors 	An array of user documents representing contributors and the main applicant to a Data Access Request application
 * @param 	{Object} 	applicationQuestionAnswers 	    An object of key pairs containing the question identifiers and answers to the questions taken from a Data Access Request application
 * @returns {Object}						An object containing two arrays, the first being representative of registered Gateway users in the form of their identifying _id 
 * and the second array being the names of applicants who were extracted from the question answers object passed in but did not match any of the registered users provided in authors
 */
const extractFormApplicants = (authors = [], applicationQuestionAnswers = {}) => {
	const gatewayApplicants = authors.map(el => el._id);
	const gatewayApplicantsNames = authors.map(el => `${el.firstname.trim()} ${el.lastname.trim()}`);

	const nonGatewayApplicants = Object.keys(applicationQuestionAnswers)
		.filter(
			key =>
				(key.includes('safepeopleprimaryapplicantfullname') || key.includes('safepeopleotherindividualsfullname')) &&
				!gatewayApplicantsNames.includes(applicationQuestionAnswers[key].trim())
		)
		.map(key => applicationQuestionAnswers[key]);

	return { gatewayApplicants, nonGatewayApplicants };
};

/**
 * Extract Funders And Sponsors
 *
 * @desc    Accepts an object containing answers from a Data Access Request application and extracts funders and sponsors names from the specific sections where these questions are asked.
 * @param 	{Object} 	applicationQuestionAnswers 	    An object of key pairs containing the question identifiers and answers to the questions taken from a Data Access Request application
 * @returns {Array<String>}						An array containing the organisation names provided as funders and sponsors
 */
const extractFundersAndSponsors = (applicationQuestionAnswers = {}) => {
	return Object.keys(applicationQuestionAnswers)
		.filter(
			key =>
				key.includes('safeprojectfunderinformationprojecthasfundername') ||
				key.includes('safeprojectsponsorinformationprojecthassponsororganisationname')
		)
		.map(key => applicationQuestionAnswers[key]);
};

export default {
	buildDataUseRegisters,
	getLinkedDatasets,
	getLinkedApplicants,
	buildRelatedDatasets,
	extractFormApplicants,
	extractFundersAndSponsors,
};
