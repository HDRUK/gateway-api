/* eslint-disable class-methods-use-this */
import dataUseRegisterUtil from './dataUseRegister.util';
import DataUseRegister from './dataUseRegister.entity';
import constants from '../utilities/constants.util';
import { isEmpty, isNil, isEqual, isUndefined } from 'lodash';
import moment from 'moment';

export default class DataUseRegisterService {
	constructor(dataUseRegisterRepository) {
		this.dataUseRegisterRepository = dataUseRegisterRepository;
	}

	getDataUseRegister(id, query = {}, options = {}) {
		// Protect for no id passed
		if (!id) return;

		query = { ...query, id };
		return this.dataUseRegisterRepository.getDataUseRegister(query, options);
	}

	getDataUseRegisters(query = {}, options = {}) {
		return this.dataUseRegisterRepository.getDataUseRegisters(query, options);
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
						el.organisationName === dataUse.organisationName &&
						el.datasetTitles === dataUse.datasetTitles)
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
			const { linkedDatasets = [], namedDatasets = [] } = await dataUseRegisterUtil.getLinkedDatasets(
				dataUse.datasetNames &&
					dataUse.datasetNames
						.toString()
						.split(',')
						.map(el => {
							if (!isEmpty(el)) return el.trim();
						})
			);

			const datasetTitles = [...linkedDatasets.map(dataset => dataset.name), ...namedDatasets];

			const { projectIdText, projectTitle, organisationName } = dataUse;

			const exists = await this.dataUseRegisterRepository.checkDataUseRegisterExists(
				projectIdText,
				projectTitle,
				organisationName,
				datasetTitles
			);
			if (exists === false) newDataUses.push(dataUse);
		}

		return newDataUses;
	}

	/**
	 * Filter Existing Data Uses
	 *
	 * @desc    Accepts multiple data uses, verifying each in turn is considered 'new' to the database, then outputs the list of data uses.
	 * 			A duplicate project id is automatically indicates a duplicate entry as the id must be unique.
	 * 			Alternatively, a combination of matching title, summary, organisation name and dataset titles indicates a duplicate entry.
	 * @param 	{Array<Object>} 	dataUses 	    	Array of data use objects to iterate through and check for existence in database
	 * @returns {Array<Object>}		Filtered array of data uses linked entites and flat to indicates a duplicate entry
	 */
	async checkDataUseRegisters(dataUses = []) {
		const dataUsesChecks = [];

		for (const obj of dataUses) {
			const { linkedDatasets = [], namedDatasets = [] } = await dataUseRegisterUtil.getLinkedDatasets(
				obj.datasetNames &&
					obj.datasetNames
						.toString()
						.split(',')
						.map(el => {
							if (!isEmpty(el)) return el.trim();
						})
			);

			const { gatewayApplicants, nonGatewayApplicants } = await dataUseRegisterUtil.getLinkedApplicants(
				obj.applicantNames &&
					obj.applicantNames
						.toString()
						.split(',')
						.map(el => {
							if (!isEmpty(el)) return el.trim();
						})
			);

			const { gatewayOutputsTools, gatewayOutputsPapers, nonGatewayOutputs } = await dataUseRegisterUtil.getLinkedOutputs(
				obj.researchOutputs &&
					obj.researchOutputs
						.toString()
						.split(',')
						.map(el => {
							if (!isEmpty(el)) return el.trim();
						})
			);

			const { projectIdText, projectTitle, organisationName } = obj;
			const datasetTitles = [...linkedDatasets.map(dataset => dataset.name), ...namedDatasets];

			const exists = await this.dataUseRegisterRepository.checkDataUseRegisterExists(
				projectIdText,
				projectTitle,
				organisationName,
				datasetTitles
			);

			//Add new data use with linked entities
			dataUsesChecks.push({
				projectIdText: obj.projectIdText,
				projectTitle: obj.projectTitle,
				laySummary: obj.laySummary,
				organisationName: obj.organisationName,
				datasetTitles: obj.datasetTitles,
				latestApprovalDate: obj.latestApprovalDate,
				linkedDatasets,
				namedDatasets,
				gatewayApplicants,
				nonGatewayApplicants,
				gatewayOutputsTools,
				gatewayOutputsPapers,
				nonGatewayOutputs,
				isDuplicated: exists,
			});
		}

		return dataUsesChecks;
	}

	/* Create Data Use Register
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
				safedatastorageandprocessingaccessmethod: safeDataAccessOptions = [],
				safedataconfidentialityavenuelegalbasisconfidentialinformation: dutyOfConfidentiality,
				safedataotherdatasetslinkadditionaldatasetslinkagedetails: datasetLinkageDetails = '',
				safedataotherdatasetsrisksmitigations: datasetLinkageRiskMitigation = '',
				safedatalawfulbasisgdprarticle6basis: legalBasisForDataArticle6,
				safedatalawfulbasisgdprarticle9conditions: legalBasisForDataArticle9,
				safedatadatafieldsdatarefreshrequired: dataRefreshRequired = '',
				safeoutputsoutputsdisseminationplansdisclosurecontrolpolicy: privacyEnhancements,
			},
		} = accessRecord;

		let accessType = '';

		if (safeDataAccessOptions.length === 1) {
			if (safeDataAccessOptions[0] === 'Via a Trusted Research Environment') {
				accessType = 'Trusted Research Environment';
			} else {
				accessType = 'Release';
			}
		} else if (safeDataAccessOptions.length === 2) {
			accessType = 'Trusted Research Environment | Release';
		}

		const fundersAndSponsors = dataUseRegisterUtil.extractFundersAndSponsors(questionAnswers);
		const { gatewayApplicants = [], nonGatewayApplicants = [] } = dataUseRegisterUtil.extractFormApplicants(
			[...authors, mainApplicant],
			questionAnswers
		);
		const { linkedDatasets = [], namedDatasets = [] } = await dataUseRegisterUtil.getLinkedDatasets([
			...datasets.map(dataset => dataset.name),
		]);
		const datasetTitles = [...linkedDatasets.map(dataset => dataset.name), ...namedDatasets];
		const relatedDatasets = dataUseRegisterUtil.buildRelatedObjects(creatorUser, 'dataset', datasets, false);
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
			applicantId: applicantId ? applicantId.trim() : '',
			accreditedResearcherStatus: isNil(accreditedResearcherStatus) ? 'Unknown' : accreditedResearcherStatus.toString().trim(),
			...(projectTitle && { projectTitle: projectTitle.toString().trim() }),
			...(organisationName && { organisationName: organisationName.toString().trim() }),
			...(laySummary && { laySummary: laySummary.toString().trim() }),
			...(publicBenefitStatement && { publicBenefitStatement: publicBenefitStatement.toString().trim() }),
			...(accessType && { accessType: accessType.toString().trim() }),
			...(dutyOfConfidentiality && { dutyOfConfidentiality: dutyOfConfidentiality.toString().trim() }),
			...(!isEmpty(datasetLinkageDescription) && { datasetLinkageDescription: datasetLinkageDescription.trim() }),
			...(!isEmpty(requestFrequency) && { requestFrequency }),
			...(legalBasisForDataArticle6 && { legalBasisForDataArticle6: legalBasisForDataArticle6.toString().trim() }),
			...(legalBasisForDataArticle9 && { legalBasisForDataArticle9: legalBasisForDataArticle9.toString().trim() }),
			...(privacyEnhancements && { privacyEnhancements: privacyEnhancements.toString().trim() }),
			...(projectStartDate.isValid() && { projectStartDate }),
			...(projectEndDate.isValid() && { projectEndDate }),
			...(latestApprovalDate.isValid() && { latestApprovalDate }),
			...(!isEmpty(datasetTitles) && { datasetTitles }),
			...(!isEmpty(linkedDatasets) && { gatewayDatasets: linkedDatasets.map(dataset => dataset.pid) }),
			...(!isEmpty(namedDatasets) && { nonGatewayDatasets: namedDatasets }),
			keywords: isNil(keywords) || isEmpty(keywords) ? [] : keywords.split(' ').slice(0, 6),
			fundersAndSponsors,
			gatewayApplicants,
			nonGatewayApplicants,
			relatedObjects: [...relatedDatasets, ...relatedApplications],
			activeflag: 'active',
			user: creatorUser._id,
			userName: `${creatorUser.firstname} ${creatorUser.lastname}`,
			updatedon: Date.now(),
			lastActivity: Date.now(),
			manualUpload: false,
		});

		this.dataUseRegisterRepository.createDataUseRegister(dataUseRegister);

		return dataUseRegister;
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

	async buildUpdateObject(dataUseRegister, dataUseRegisterPayload, user) {
		let updateObj = {};

		const {
			activeflag,
			rejectionReason,
			discourseTopicId,
			relatedObjects,
			keywords,
			projectTitle,
			projectId,
			projectIdText,
			datasetTitles,
			gatewayDatasets,
			nonGatewayDatasets,
			organisationName,
			organisationId,
			organisationSector,
			gatewayApplicants,
			nonGatewayApplicants,
			applicantId,
			fundersAndSponsors,
			accreditedResearcherStatus,
			sublicenceArrangements,
			laySummary,
			publicBenefitStatement,
			requestCategoryType,
			technicalSummary,
			otherApprovalCommittees,
			projectStartDate,
			projectEndDate,
			latestApprovalDate,
			dataSensitivityLevel,
			legalBasisForDataArticle6,
			legalBasisForDataArticle9,
			dutyOfConfidentiality,
			nationalDataOptOut,
			requestFrequency,
			datasetLinkageDescription,
			confidentialDataDescription,
			accessDate,
			accessType,
			privacyEnhancements,
			gatewayOutputs,
			nonGatewayOutputs,
		} = dataUseRegisterPayload;

		const gatewayDatasetPids = gatewayDatasets ? await dataUseRegisterUtil.getDatasetsByPids(gatewayDatasets) : [];
		const gatewayApplicantIDs = gatewayApplicants ? await dataUseRegisterUtil.getAppplicantByIds(gatewayApplicants) : [];
		const { gatewayToolIDs, gatewayPaperIDs } = gatewayOutputs ? await dataUseRegisterUtil.getSafeOutputsByIds(gatewayOutputs) : [];

		let gatewayApplicantIDsList = [];
		gatewayApplicantIDs &&
			gatewayApplicantIDs.forEach(applicant => {
				gatewayApplicantIDsList.push(applicant._id);
			});
		if (!isUndefined(gatewayApplicants) && !isEqual(gatewayApplicantIDsList, dataUseRegister.gatewayApplicants))
			updateObj.gatewayApplicants = gatewayApplicantIDsList;

		let gatewayOutputsToolIDsList = [],
			gatewayOutputsToolIDsListRelatedResource = [];
		gatewayToolIDs &&
			gatewayToolIDs.forEach(tool => {
				gatewayOutputsToolIDsList.push(tool.id);
				gatewayOutputsToolIDsListRelatedResource.push({ id: tool.id.toString() });
			});
		if (!isUndefined(gatewayOutputs) && !isEqual(gatewayOutputsToolIDsList, dataUseRegister.gatewayOutputsTools))
			updateObj.gatewayOutputsTools = gatewayOutputsToolIDsList;

		let gatewayOutputsPaperIDsList = [],
			gatewayOutputsPaperIDsListRelatedResource = [];
		gatewayPaperIDs &&
			gatewayPaperIDs.forEach(paper => {
				gatewayOutputsPaperIDsList.push(paper.id);
				gatewayOutputsPaperIDsListRelatedResource.push({ id: paper.id.toString() });
			});
		if (!isUndefined(gatewayOutputs) && !isEqual(gatewayOutputsPaperIDsList, dataUseRegister.gatewayOutputsPapers))
			updateObj.gatewayOutputsPapers = gatewayOutputsPaperIDsList;

		let gatewayDatasetPidsListRelatedResource = [];

		gatewayDatasetPids &&
			gatewayDatasetPids.forEach(dataset => {
				gatewayDatasetPidsListRelatedResource.push({ id: dataset.datasetid, pid: dataset.pid });
			});

		let automaticRelatedResources = [
			...dataUseRegisterUtil.buildRelatedObjects(user, 'dataset', gatewayDatasetPidsListRelatedResource, false, true),
			...dataUseRegisterUtil.buildRelatedObjects(user, 'tool', gatewayOutputsToolIDsListRelatedResource, false, true),
			...dataUseRegisterUtil.buildRelatedObjects(user, 'paper', gatewayOutputsPaperIDsListRelatedResource, false, true),
		];

		//dataUseRegister.relatedObjects

		//Loop through automaticRelatedResources to see if it exists, if not add to another array

		let newAutomaticRelatedResources = [];
		automaticRelatedResources.forEach(automaticResource => {
			if (!dataUseRegister.relatedObjects.find(resource => resource.objectId === automaticResource.objectId)) {
				newAutomaticRelatedResources.push(automaticResource);
			}
		});

		let relatedResourcesWithRemovedOldAutomaticEntries = [];
		!isUndefined(relatedObjects) &&
			relatedObjects.forEach(resource => {
				if (resource.isLocked && automaticRelatedResources.find(automaticResource => automaticResource.objectId === resource.objectId)) {
					relatedResourcesWithRemovedOldAutomaticEntries.push(resource);
				} else if (!resource.isLocked) {
					relatedResourcesWithRemovedOldAutomaticEntries.push(resource);
				}
			});

		//relatedObjects

		updateObj.relatedObjects = [...relatedResourcesWithRemovedOldAutomaticEntries, ...newAutomaticRelatedResources];

		const fundersAndSponsorsList =
			fundersAndSponsors &&
			fundersAndSponsors
				.toString()
				.split(',')
				.map(el => {
					if (!isEmpty(el)) return el.trim();
				});
		if (!isEmpty(fundersAndSponsorsList) && !isEqual(fundersAndSponsorsList, dataUseRegister.fundersAndSponsors))
			updateObj.fundersAndSponsors = fundersAndSponsorsList;

		const otherApprovalCommitteesList =
			otherApprovalCommittees &&
			otherApprovalCommittees
				.toString()
				.split(',')
				.map(el => {
					if (!isEmpty(el)) return el.trim();
				});
		if (!isEmpty(otherApprovalCommitteesList) && !isEqual(otherApprovalCommitteesList, dataUseRegister.otherApprovalCommittees))
			updateObj.otherApprovalCommittees = otherApprovalCommitteesList;

		if (!isUndefined(activeflag) && !isEqual(activeflag, dataUseRegister.activeflag)) updateObj.activeflag = activeflag;
		if (!isUndefined(rejectionReason) && !isEqual(rejectionReason, dataUseRegister.rejectionReason))
			updateObj.rejectionReason = rejectionReason;
		if (!isUndefined(discourseTopicId) && !isEqual(discourseTopicId, dataUseRegister.discourseTopicId))
			updateObj.discourseTopicId = discourseTopicId;
		if (!isUndefined(keywords) && !isEqual(keywords, dataUseRegister.keywords)) updateObj.keywords = keywords;
		if (!isUndefined(projectTitle) && !isEqual(projectTitle, dataUseRegister.projectTitle)) updateObj.projectTitle = projectTitle;
		if (!isUndefined(projectId) && !isEqual(projectId, dataUseRegister.projectId)) updateObj.projectId = projectId;
		if (!isUndefined(projectIdText) && !isEqual(projectIdText, dataUseRegister.projectIdText)) updateObj.projectIdText = projectIdText;
		if (!isUndefined(datasetTitles) && !isEqual(datasetTitles, dataUseRegister.datasetTitles)) updateObj.datasetTitles = datasetTitles;
		if (!isUndefined(gatewayDatasets) && !isEqual(gatewayDatasets, dataUseRegister.gatewayDatasets))
			updateObj.gatewayDatasets = gatewayDatasets;
		if (!isUndefined(nonGatewayDatasets) && !isEqual(nonGatewayDatasets, dataUseRegister.nonGatewayDatasets))
			updateObj.nonGatewayDatasets = nonGatewayDatasets;
		if (!isUndefined(projectTitle) && !isEqual(projectTitle, dataUseRegister.projectTitle)) updateObj.projectTitle = projectTitle;
		if (!isUndefined(organisationName) && !isEqual(organisationName, dataUseRegister.organisationName))
			updateObj.organisationName = organisationName;
		if (!isUndefined(organisationId) && !isEqual(organisationId, dataUseRegister.organisationId)) updateObj.organisationId = organisationId;
		if (!isUndefined(organisationSector) && !isEqual(organisationSector, dataUseRegister.organisationSector))
			updateObj.organisationSector = organisationSector;
		if (!isUndefined(nonGatewayApplicants) && !isEqual(nonGatewayApplicants, dataUseRegister.nonGatewayApplicants))
			updateObj.nonGatewayApplicants = nonGatewayApplicants;
		if (!isUndefined(applicantId) && !isEqual(applicantId, dataUseRegister.applicantId)) updateObj.applicantId = applicantId;
		if (!isUndefined(accreditedResearcherStatus) && !isEqual(accreditedResearcherStatus, dataUseRegister.accreditedResearcherStatus))
			updateObj.accreditedResearcherStatus = accreditedResearcherStatus;
		if (!isUndefined(sublicenceArrangements) && !isEqual(sublicenceArrangements, dataUseRegister.sublicenceArrangements))
			updateObj.sublicenceArrangements = sublicenceArrangements;
		if (!isUndefined(laySummary) && !isEqual(laySummary, dataUseRegister.laySummary)) updateObj.laySummary = laySummary;
		if (!isUndefined(publicBenefitStatement) && !isEqual(publicBenefitStatement, dataUseRegister.publicBenefitStatement))
			updateObj.publicBenefitStatement = publicBenefitStatement;
		if (!isUndefined(requestCategoryType) && !isEqual(requestCategoryType, dataUseRegister.requestCategoryType))
			updateObj.requestCategoryType = requestCategoryType;
		if (!isUndefined(technicalSummary) && !isEqual(technicalSummary, dataUseRegister.technicalSummary))
			updateObj.technicalSummary = technicalSummary;
		if (
			!isEmpty(projectStartDate) &&
			!isEqual(moment(projectStartDate).format('YYYY-MM-DD'), moment(dataUseRegister.projectStartDate).format('YYYY-MM-DD'))
		)
			updateObj.projectStartDate = moment(projectStartDate, 'YYYY-MM-DD');
		if (
			!isEmpty(projectEndDate) &&
			!isEqual(moment(projectEndDate).format('YYYY-MM-DD'), moment(dataUseRegister.projectEndDate).format('YYYY-MM-DD'))
		)
			updateObj.projectEndDate = moment(projectEndDate, 'YYYY-MM-DD');
		if (
			!isEmpty(latestApprovalDate) &&
			!isEqual(moment(latestApprovalDate).format('YYYY-MM-DD'), moment(dataUseRegister.latestApprovalDate).format('YYYY-MM-DD'))
		)
			updateObj.projectStartDate = moment(latestApprovalDate, 'YYYY-MM-DD');
		if (!isUndefined(dataSensitivityLevel) && !isEqual(dataSensitivityLevel, dataUseRegister.dataSensitivityLevel))
			updateObj.dataSensitivityLevel = dataSensitivityLevel;
		if (!isUndefined(legalBasisForDataArticle6) && !isEqual(legalBasisForDataArticle6, dataUseRegister.legalBasisForDataArticle6))
			updateObj.legalBasisForDataArticle6 = legalBasisForDataArticle6;
		if (!isUndefined(legalBasisForDataArticle9) && !isEqual(legalBasisForDataArticle9, dataUseRegister.legalBasisForDataArticle9))
			updateObj.legalBasisForDataArticle9 = legalBasisForDataArticle9;
		if (!isUndefined(dutyOfConfidentiality) && !isEqual(dutyOfConfidentiality, dataUseRegister.dutyOfConfidentiality))
			updateObj.dutyOfConfidentiality = dutyOfConfidentiality;
		if (!isUndefined(nationalDataOptOut) && !isEqual(nationalDataOptOut, dataUseRegister.nationalDataOptOut))
			updateObj.nationalDataOptOut = nationalDataOptOut;
		if (!isUndefined(requestFrequency) && !isEqual(requestFrequency, dataUseRegister.requestFrequency))
			updateObj.requestFrequency = requestFrequency;
		if (!isUndefined(datasetLinkageDescription) && !isEqual(datasetLinkageDescription, dataUseRegister.datasetLinkageDescription))
			updateObj.datasetLinkageDescription = datasetLinkageDescription;
		if (!isUndefined(confidentialDataDescription) && !isEqual(confidentialDataDescription, dataUseRegister.confidentialDataDescription))
			updateObj.confidentialDataDescription = confidentialDataDescription;
		if (!isEmpty(accessDate) && !isEqual(moment(accessDate).format('YYYY-MM-DD'), moment(dataUseRegister.accessDate).format('YYYY-MM-DD')))
			updateObj.accessDate = moment(accessDate, 'YYYY-MM-DD');
		if (!isUndefined(accessType) && !isEqual(accessType, dataUseRegister.accessType)) updateObj.accessType = accessType;
		if (!isUndefined(privacyEnhancements) && !isEqual(privacyEnhancements, dataUseRegister.privacyEnhancements))
			updateObj.privacyEnhancements = privacyEnhancements;
		if (!isUndefined(nonGatewayOutputs) && !isEqual(nonGatewayOutputs, dataUseRegister.nonGatewayOutputs))
			updateObj.nonGatewayOutputs = nonGatewayOutputs;

		return updateObj;
	}
}
