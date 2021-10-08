import dataUseRegisterUtil from './dataUseRegister.util';
import DataUseRegister from './dataUseRegister.entity';
import constants from '../utilities/constants.util';
import { isEmpty, isNil } from 'lodash';
import moment from 'moment';

export default class DataUseRegisterService {
	constructor(dataUseRegisterRepository) {
		this.dataUseRegisterRepository = dataUseRegisterRepository;
	}

	getDataUseRegister(id, query = {}, options = {}) {
		// Protect for no id passed
		if (!id) return;

		query = { ...query, _id: id };
		return this.dataUseRegisterRepository.getDataUseRegister(query, options);
	}

	getDataUseRegisters(query = {}) {
		return this.dataUseRegisterRepository.getDataUseRegisters(query);
	}

	updateDataUseRegister(id, body = {}) {
		// Protect for no id passed
		if (!id) return;

		return this.dataUseRegisterRepository.updateDataUseRegister({ _id: id }, body);
	}

	/**
	 * Upload Data Use Registers
	 *
	 * @desc    Accepts multiple data uses to upload and a team identifier indicating which Custodian team to add the data uses to.
	 *
	 * @param 	{String} 			teamId 	    	Array of data use objects to filter until uniqueness exists
	 * @param 	{Array<Object>} 	dataUseUploads 	 Array of data use objects to filter until uniqueness exists
	 * @returns {Object}		Object containing the details of the upload operation including number of duplicates found in payload, database and number successfully added
	 */
	async uploadDataUseRegisters(creatorUser, teamId, dataUseRegisterUploads = []) {
		const dedupedDataUseRegisters = this.filterDuplicateDataUseRegisters(dataUseRegisterUploads);

		const dataUseRegisters = await dataUseRegisterUtil.buildDataUseRegisters(creatorUser, teamId, dedupedDataUseRegisters);

		const newDataUseRegisters = await this.filterExistingDataUseRegisters(dataUseRegisters);

		const uploadedDataUseRegisters = await this.dataUseRegisterRepository.uploadDataUseRegisters(newDataUseRegisters);

		return {
			uploadedCount: uploadedDataUseRegisters.length,
			duplicateCount: dataUseRegisterUploads.length - newDataUseRegisters.length,
			uploaded: uploadedDataUseRegisters,
		};
	}

	/**
	 * Filter Duplicate Data Uses
	 *
	 * @desc    Accepts multiple data uses and outputs a unique list of data uses based on each entities properties.
	 * 			A duplicate project id is automatically indicates a duplicate entry as the id must be unique.
	 * 			Alternatively, a combination of matching title, summary, organisation name, dataset titles and latest approval date indicates a duplicate entry.
	 * @param 	{Array<Object>} 	dataUses 	    	Array of data use objects to filter until uniqueness exists
	 * @returns {Array<Object>}		Filtered array of data uses assumed unique based on filter criteria
	 */
	filterDuplicateDataUseRegisters(dataUses) {
		return dataUses.reduce((arr, dataUse) => {
			const isDuplicate = arr.some(
				el =>
					el.projectIdText === dataUse.projectIdText ||
					(el.projectTitle === dataUse.projectTitle &&
						el.laySummary === dataUse.laySummary &&
						el.organisationName === dataUse.organisationName &&
						el.datasetTitles === dataUse.datasetTitles &&
						el.latestApprovalDate === dataUse.latestApprovalDate)
			);
			if (!isDuplicate) arr = [...arr, dataUse];
			return arr;
		}, []);
	}

	/**
	 * Filter Existing Data Uses
	 *
	 * @desc    Accepts multiple data uses, verifying each in turn is considered 'new' to the database, then outputs the list of data uses.
	 * 			A duplicate project id is automatically indicates a duplicate entry as the id must be unique.
	 * 			Alternatively, a combination of matching title, summary, organisation name and dataset titles indicates a duplicate entry.
	 * @param 	{Array<Object>} 	dataUses 	    	Array of data use objects to iterate through and check for existence in database
	 * @returns {Array<Object>}		Filtered array of data uses assumed to be 'new' to the database based on filter criteria
	 */
	async filterExistingDataUseRegisters(dataUses) {
		const newDataUses = [];

		for (const dataUse of dataUses) {
			const exists = await this.dataUseRegisterRepository.checkDataUseRegisterExists(dataUse);
			if (exists === false) newDataUses.push(dataUse);
		}

		return newDataUses;
	}

	/**
	 * Create Data Use Register
	 *
	 * @desc    Accepts a single data access request record and automatically generates a data use register record in the 'inReview' state.
	 * Related resources, project Id, origin, uploader, applicant names, and answers are determined from the application provided.
	 * @param 	{Object} 	accessRecord 	    	Data access request model used to create the data use register
	 * @returns {Object}	Returns the saved data use register
	 */
	async createDataUseRegister(creatorUser, accessRecord) {
		const {
			_id: applicationId,
			projectId,
			publisherObj: { _id: publisher },
			datasets,
			authors,
			mainApplicant,
			dateFinalStatus,
			questionAnswers,
			versionTree,
			questionAnswers: {
				safepeopleprimaryapplicantorganisationname: organisationName,
				safepeopleprimaryapplicantorcid: applicantId,
				safeprojectprojectdetailstitle: projectTitle,
				safepeopleprimaryapplicantaccreditedresearcher: accreditedResearcherStatus,
				safeprojectprojectdetailslaysummary: laySummary,
				safeprojectprojectdetailspublicbenefitimpact: publicBenefitStatement,
				safeprojectprojectdetailsresearchprojectsummarykeywords: keywords,
				['safeproject-projectdetails-startdate']: startDate,
				['safeproject-projectdetails-enddate']: endDate,
				safedatastorageandprocessingaccessmethodtrustedresearchenvironment: dataLocation,
				safedataconfidentialityavenuelegalbasisconfidentialinformation: dutyOfConfidentiality,
				safedataotherdatasetslinkadditionaldatasetslinkagedetails: datasetLinkageDetails = '',
				safedataotherdatasetsrisksmitigations: datasetLinkageRiskMitigation = '',
				safedatalawfulbasisgdprarticle6basis: legalBasisForDataArticle6,
				safedatalawfulbasisgdprarticle9conditions: legalBasisForDataArticle9,
				safedatadatafieldsdatarefreshrequired: dataRefreshRequired = '',
				safeoutputsoutputsdisseminationplansdisclosurecontrolpolicy: privacyEnhancements,
			},
		} = accessRecord;

		const fundersAndSponsors = dataUseRegisterUtil.extractFundersAndSponsors(questionAnswers);
		const { gatewayApplicants = [], nonGatewayApplicants = [] } = dataUseRegisterUtil.extractFormApplicants(
			[...authors, mainApplicant],
			questionAnswers
		);
		const relatedDatasets = dataUseRegisterUtil.buildRelatedDatasets(creatorUser, datasets, false);
		const relatedApplications = await this.buildRelatedDataUseRegisters(creatorUser, versionTree, applicationId);
		const datasetLinkageDescription = `${datasetLinkageDetails.toString().trim()} ${datasetLinkageRiskMitigation.toString().trim()}`;
		const requestFrequency = dataRefreshRequired === 'Yes' ? 'Recurring' : dataRefreshRequired === 'No' ? 'One-off' : '';

		const projectStartDate = moment(startDate, 'DD/MM/YYYY');
		const projectEndDate = moment(endDate, 'DD/MM/YYYY');
		const latestApprovalDate = moment(dateFinalStatus);

		const dataUseRegister = new DataUseRegister({
			publisher,
			projectIdText: projectId,
			projectId: applicationId,
			applicantId: applicantId.trim(),
			accreditedResearcherStatus: isNil(accreditedResearcherStatus) ? 'Unknown' : accreditedResearcherStatus.toString().trim(),
			...(projectTitle && { projectTitle: projectTitle.toString().trim() }),
			...(organisationName && { organisationName: organisationName.toString().trim() }),
			...(laySummary && { laySummary: laySummary.toString().trim() }),
			...(publicBenefitStatement && { publicBenefitStatement: publicBenefitStatement.toString().trim() }),
			...(dataLocation && { dataLocation: dataLocation.toString().trim() }),
			...(dutyOfConfidentiality && { dutyOfConfidentiality: dutyOfConfidentiality.toString().trim() }),
			...(!isEmpty(datasetLinkageDescription) && { datasetLinkageDescription: datasetLinkageDescription.trim() }),
			...(!isEmpty(requestFrequency) && { requestFrequency }),
			...(legalBasisForDataArticle6 && { legalBasisForDataArticle6: legalBasisForDataArticle6.toString().trim() }),
			...(legalBasisForDataArticle9 && { legalBasisForDataArticle9: legalBasisForDataArticle9.toString().trim() }),
			...(privacyEnhancements && { privacyEnhancements: privacyEnhancements.toString().trim() }),
			...(projectStartDate.isValid() && { projectStartDate }),
			...(projectEndDate.isValid() && { projectEndDate }),
			...(latestApprovalDate.isValid() && { latestApprovalDate }),
			datasetTitles: [...datasets.map(dataset => dataset.name)],
			datasetIds: [...datasets.map(dataset => dataset.datasetid)],
			datasetPids: [...datasets.map(dataset => dataset.pid)],
			keywords: isNil(keywords) || isEmpty(keywords) ? [] : keywords.split(' ').slice(0, 6),
			fundersAndSponsors,
			gatewayApplicants,
			nonGatewayApplicants,
			relatedObjects: [...relatedDatasets, ...relatedApplications],
			activeflag: 'inReview',
			user: creatorUser._id,
			userName: `${creatorUser.firstname} ${creatorUser.lastname}`,
			updatedon: Date.now(),
			lastActivity: Date.now(),
			manualUpload: false,
		});

		this.dataUseRegisterRepository.createDataUseRegister(dataUseRegister);
	}

	/**
	 * Build Related Data Use Registers
	 *
	 * @desc    Accepts the requesting user, an application identifier and the same application's version tree.
	 * The function uses this information to extract related applications versions which will have data use registers already in existence.
	 * Upon finding related data use registers, related objects are created and returned.
	 * @param 	{Object} 	creatorUser 	    	The requesting user calling this function
	 * @param 	{Object} 	versionTree 	    	An object data structure containing the linkages from this application to other versions of the application
	 * @returns {Array<Object>}	Returns an array of related objects which are of the data use register type
	 */
	async buildRelatedDataUseRegisters(creatorUser, versionTree, applicationId) {
		const relatedDataUseRegisters = [];
		const { firstname, lastname } = creatorUser;
		const ignoredApplicationTypes = [constants.submissionTypes.INPROGRESS, constants.submissionTypes.RESUBMISSION];

		for (const key of Object.keys(versionTree)) {
			if (
				versionTree[key].applicationType &&
				!ignoredApplicationTypes.includes(versionTree[key].applicationType) &&
				versionTree[key].toString() !== applicationId.toString()
			) {
				const { applicationId } = versionTree[key];
				const dataUseRegister = await this.dataUseRegisterRepository.getDataUseRegisterByApplicationId(applicationId);

				if (dataUseRegister) {
					relatedDataUseRegisters.push({
						objectId: dataUseRegister.id,
						objectType: 'dataUseRegister',
						user: `${firstname} ${lastname}`,
						updated: Date.now(),
						isLocked: true,
						reason: `This data use register was added automatically as it was derived from a previously approved version of the same data access request`,
					});
				}
			}
		}

		return relatedDataUseRegisters;
	}
}
