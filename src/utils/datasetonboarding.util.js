import Ajv from 'ajv';
import moment from 'moment';
import { flatten } from 'flat';
import addFormats from 'ajv-formats';
import randomstring from 'randomstring';
import constants from '../resources/utilities/constants.util';
import _, { isEmpty, isNil, cloneDeep, isString, map, groupBy, orderBy } from 'lodash';

import { Data } from '../resources/tool/data.model';
import { TeamModel } from '../resources/team/team.model';
import { UserModel } from '../resources/user/user.model';
import { PublisherModel } from '../resources/publisher/publisher.model';
import emailGenerator from '../resources/utilities/emailGenerator.util';
import notificationBuilder from '../resources/utilities/notificationBuilder';

var fs = require('fs');

/**
 * Checks to see if the user has the correct permissions to access the dataset
 *
 * @param   {Object}  id  	[dataset id]
 * @param   {Object}  userId       	[user object]
 *
 * @return  {Object} authorised, userType	[return object containing the authorised and userType fields]
 */
const getUserPermissionsForDataset = async (id, user, publisherId) => {
	try {
		let authorised = false,
			userType = '';

		// Return default unauthorised with no user type if incorrect params passed
		if (!user || (!id && !publisherId)) {
			return { authorised, userType };
		}

		let { teams } = user.toObject();

		if (teams) {
			teams = teams.map(team => {
				let { publisher, type, members } = team;
				let member = members.find(member => {
					return member.memberid.toString() === user._id.toString();
				});
				let { roles } = member;
				return { ...publisher, type, roles };
			});
		}

		let isMetadataAdmin = {};
		if (!isEmpty(teams.filter(team => team.type === constants.teamTypes.ADMIN))) {
			isMetadataAdmin = teams
				.filter(team => team.type === constants.teamTypes.ADMIN)
				.find(team => team.roles.includes(constants.roleTypes.ADMIN_DATASET));
		}

		if (!isEmpty(isMetadataAdmin)) {
			return { authorised: true, userType: constants.userTypes.ADMIN };
		}

		if (isEmpty(publisherId)) {
			const publisher = await Data.findOne({ _id: id }, { 'datasetv2.summary.publisher.identifier': 1 }).lean();
			publisherId = publisher.datasetv2.summary.publisher.identifier;
		}

		let publisherTeam = {};
		if (!isEmpty(teams.find(team => team._id.toString() === publisherId))) {
			publisherTeam = teams.find(team => team._id.toString() === publisherId);
		}

		if (!isEmpty(publisherTeam)) {
			if (publisherTeam.roles.find(role => role.includes(constants.roleTypes.METADATA_EDITOR))) {
				return { authorised: true, userType: constants.roleTypes.METADATA_EDITOR };
			} else if (publisherTeam.roles.find(role => role.includes(constants.roleTypes.MANAGER))) {
				return { authorised: true, userType: constants.roleTypes.MANAGER };
			}
		}

		return { authorised, userType };
	} catch (error) {
		process.stdout.write(`DATASETONBOARDING - getUserPermissionsForDataset : ${error.message}\n`);
		return { authorised: false, userType: '' };
	}
};

/**
 * Takes the dataset and populates the questionAnswers object with them
 *
 * @var {Object} dataset 			[dataset object]
 *
 * @returns {Object} [questionAnswers object]
 */
const populateQuestionAnswers = dataset => {
	let questionAnswers = {};

	//Summary
	if (!isNil(dataset.summary.title) && !isEmpty(dataset.summary.title)) questionAnswers['properties/summary/title'] = dataset.summary.title;
	if (isNil(questionAnswers['properties/summary/title'])) questionAnswers['properties/summary/title'] = dataset.name;
	if (!isNil(dataset.summary.abstract) && !isEmpty(dataset.summary.abstract))
		questionAnswers['properties/summary/abstract'] = dataset.summary.abstract;
	if (!isNil(dataset.summary.contactPoint) && !isEmpty(dataset.summary.contactPoint))
		questionAnswers['properties/summary/contactPoint'] = dataset.summary.contactPoint;
	if (!isNil(dataset.summary.keywords) && !isEmpty(dataset.summary.keywords))
		questionAnswers['properties/summary/keywords'] = returnAsArray(dataset.summary.keywords);
	if (!isNil(dataset.summary.alternateIdentifiers) && !isEmpty(dataset.summary.alternateIdentifiers))
		questionAnswers['properties/summary/alternateIdentifiers'] = dataset.summary.alternateIdentifiers;
	if (!isNil(dataset.summary.doiName) && !isEmpty(dataset.summary.doiName))
		questionAnswers['properties/summary/doiName'] = dataset.summary.doiName;
	//Documentation
	if (!isNil(dataset.documentation.description) && !isEmpty(dataset.documentation.description))
		questionAnswers['properties/documentation/description'] = dataset.documentation.description;
	if (!isNil(dataset.documentation.associatedMedia) && !isEmpty(dataset.documentation.associatedMedia))
		questionAnswers['properties/documentation/associatedMedia'] = returnAsArray(dataset.documentation.associatedMedia);
	if (!isNil(dataset.documentation.isPartOf) && !isEmpty(dataset.documentation.isPartOf))
		questionAnswers['properties/documentation/isPartOf'] = dataset.documentation.isPartOf;
	//Coverage
	if (!isNil(dataset.coverage.spatial) && !isEmpty(dataset.coverage.spatial))
		questionAnswers['properties/coverage/spatial'] = returnAsArray(dataset.coverage.spatial);
	if (!isNil(dataset.coverage.typicalAgeRange) && !isEmpty(dataset.coverage.typicalAgeRange))
		questionAnswers['properties/coverage/typicalAgeRange'] = dataset.coverage.typicalAgeRange;
	if (!isNil(dataset.coverage.physicalSampleAvailability) && !isEmpty(dataset.coverage.physicalSampleAvailability))
		questionAnswers['properties/coverage/physicalSampleAvailability'] = returnAsArray(dataset.coverage.physicalSampleAvailability);
	if (!isNil(dataset.coverage.followup) && !isEmpty(dataset.coverage.followup))
		questionAnswers['properties/coverage/followup'] = dataset.coverage.followup;
	if (!isNil(dataset.coverage.pathway) && !isEmpty(dataset.coverage.pathway))
		questionAnswers['properties/coverage/pathway'] = dataset.coverage.pathway;
	//Provenance - Origin
	if (!isNil(dataset.provenance.origin.purpose) && !isEmpty(dataset.provenance.origin.purpose))
		questionAnswers['properties/provenance/origin/purpose'] = returnAsArray(dataset.provenance.origin.purpose);
	if (!isNil(dataset.provenance.origin.source) && !isEmpty(dataset.provenance.origin.source))
		questionAnswers['properties/provenance/origin/source'] = returnAsArray(dataset.provenance.origin.source);
	if (!isNil(dataset.provenance.origin.collectionSituation) && !isEmpty(dataset.provenance.origin.collectionSituation))
		questionAnswers['properties/provenance/origin/collectionSituation'] = returnAsArray(dataset.provenance.origin.collectionSituation);
	//Provenance - Temporal
	if (!isNil(dataset.provenance.temporal.accrualPeriodicity) && !isEmpty(dataset.provenance.temporal.accrualPeriodicity))
		questionAnswers['properties/provenance/temporal/accrualPeriodicity'] = dataset.provenance.temporal.accrualPeriodicity;
	if (!isNil(dataset.provenance.temporal.distributionReleaseDate) && !isEmpty(dataset.provenance.temporal.distributionReleaseDate))
		questionAnswers['properties/provenance/temporal/distributionReleaseDate'] = returnAsDate(
			dataset.provenance.temporal.distributionReleaseDate
		);
	if (!isNil(dataset.provenance.temporal.startDate) && !isEmpty(dataset.provenance.temporal.startDate))
		questionAnswers['properties/provenance/temporal/startDate'] = returnAsDate(dataset.provenance.temporal.startDate);
	if (!isNil(dataset.provenance.temporal.endDate) && !isEmpty(dataset.provenance.temporal.endDate))
		questionAnswers['properties/provenance/temporal/endDate'] = returnAsDate(dataset.provenance.temporal.endDate);
	if (!isNil(dataset.provenance.temporal.timeLag) && !isEmpty(dataset.provenance.temporal.timeLag))
		questionAnswers['properties/provenance/temporal/timeLag'] = dataset.provenance.temporal.timeLag;
	//Accessibility - Usage
	if (!isNil(dataset.accessibility.usage.dataUseLimitation) && !isEmpty(dataset.accessibility.usage.dataUseLimitation))
		questionAnswers['properties/accessibility/usage/dataUseLimitation'] = returnAsArray(dataset.accessibility.usage.dataUseLimitation);
	if (!isNil(dataset.accessibility.usage.dataUseRequirements) && !isEmpty(dataset.accessibility.usage.dataUseRequirements))
		questionAnswers['properties/accessibility/usage/dataUseRequirements'] = returnAsArray(dataset.accessibility.usage.dataUseRequirements);
	if (!isNil(dataset.accessibility.usage.resourceCreator) && !isEmpty(dataset.accessibility.usage.resourceCreator))
		questionAnswers['properties/accessibility/usage/resourceCreator'] = returnAsArray(dataset.accessibility.usage.resourceCreator);
	if (!isNil(dataset.accessibility.usage.investigations) && !isEmpty(dataset.accessibility.usage.investigations))
		questionAnswers['properties/accessibility/usage/investigations'] = returnAsArray(dataset.accessibility.usage.investigations);
	if (!isNil(dataset.accessibility.usage.isReferencedBy) && !isEmpty(dataset.accessibility.usage.isReferencedBy))
		questionAnswers['properties/accessibility/usage/isReferencedBy'] = returnAsArray(dataset.accessibility.usage.isReferencedBy);
	//Accessibility - Access
	if (!isNil(dataset.accessibility.access.accessRights) && !isEmpty(dataset.accessibility.access.accessRights))
		questionAnswers['properties/accessibility/access/accessRights'] = returnAsArray(dataset.accessibility.access.accessRights);
	if (!isNil(dataset.accessibility.access.accessService) && !isEmpty(dataset.accessibility.access.accessService))
		questionAnswers['properties/accessibility/access/accessService'] = dataset.accessibility.access.accessService;
	if (!isNil(dataset.accessibility.access.accessRequestCost) && !isEmpty(dataset.accessibility.access.accessRequestCost))
		questionAnswers['properties/accessibility/access/accessRequestCost'] = returnAsArray(dataset.accessibility.access.accessRequestCost);
	if (!isNil(dataset.accessibility.access.deliveryLeadTime) && !isEmpty(dataset.accessibility.access.deliveryLeadTime))
		questionAnswers['properties/accessibility/access/deliveryLeadTime'] = dataset.accessibility.access.deliveryLeadTime;
	if (!isNil(dataset.accessibility.access.jurisdiction) && !isEmpty(dataset.accessibility.access.jurisdiction))
		questionAnswers['properties/accessibility/access/jurisdiction'] = returnAsArray(dataset.accessibility.access.jurisdiction);
	if (!isNil(dataset.accessibility.access.dataProcessor) && !isEmpty(dataset.accessibility.access.dataProcessor))
		questionAnswers['properties/accessibility/access/dataProcessor'] = dataset.accessibility.access.dataProcessor;
	if (!isNil(dataset.accessibility.access.dataController) && !isEmpty(dataset.accessibility.access.dataController))
		questionAnswers['properties/accessibility/access/dataController'] = dataset.accessibility.access.dataController;
	//Accessibility - FormatAndStandards
	if (
		!isNil(dataset.accessibility.formatAndStandards.vocabularyEncodingScheme) &&
		!isEmpty(dataset.accessibility.formatAndStandards.vocabularyEncodingScheme)
	)
		questionAnswers['properties/accessibility/formatAndStandards/vocabularyEncodingScheme'] = returnAsArray(
			dataset.accessibility.formatAndStandards.vocabularyEncodingScheme
		);
	if (!isNil(dataset.accessibility.formatAndStandards.conformsTo) && !isEmpty(dataset.accessibility.formatAndStandards.conformsTo))
		questionAnswers['properties/accessibility/formatAndStandards/conformsTo'] = returnAsArray(
			dataset.accessibility.formatAndStandards.conformsTo
		);
	if (!isNil(dataset.accessibility.formatAndStandards.language) && !isEmpty(dataset.accessibility.formatAndStandards.language))
		questionAnswers['properties/accessibility/formatAndStandards/language'] = returnAsArray(
			dataset.accessibility.formatAndStandards.language
		);
	if (!isNil(dataset.accessibility.formatAndStandards.format) && !isEmpty(dataset.accessibility.formatAndStandards.format))
		questionAnswers['properties/accessibility/formatAndStandards/format'] = returnAsArray(dataset.accessibility.formatAndStandards.format);
	//EnrichmentAndLinkage
	if (!isNil(dataset.enrichmentAndLinkage.qualifiedRelation) && !isEmpty(dataset.enrichmentAndLinkage.qualifiedRelation))
		questionAnswers['properties/enrichmentAndLinkage/qualifiedRelation'] = returnAsArray(dataset.enrichmentAndLinkage.qualifiedRelation);
	if (!isNil(dataset.enrichmentAndLinkage.derivation) && !isEmpty(dataset.enrichmentAndLinkage.derivation))
		questionAnswers['properties/enrichmentAndLinkage/derivation'] = returnAsArray(dataset.enrichmentAndLinkage.derivation);
	if (!isNil(dataset.enrichmentAndLinkage.tools) && !isEmpty(dataset.enrichmentAndLinkage.tools))
		questionAnswers['properties/enrichmentAndLinkage/tools'] = returnAsArray(dataset.enrichmentAndLinkage.tools);
	//Observations
	if (!isNil(dataset.observations) && !isEmpty(dataset.observations)) {
		let observations = returnAsArray(dataset.observations);
		let uniqueId = '';
		for (let observation of observations) {
			questionAnswers[`properties/observation/observedNode${uniqueId}`] = observation.observedNode.toUpperCase();
			questionAnswers[`properties/observation/measuredValue${uniqueId}`] = observation.measuredValue;
			questionAnswers[`properties/observation/disambiguatingDescription${uniqueId}`] = observation.disambiguatingDescription;
			questionAnswers[`properties/observation/observationDate${uniqueId}`] = returnAsDate(observation.observationDate);
			questionAnswers[`properties/observation/measuredProperty${uniqueId}`] = observation.measuredProperty;
			uniqueId = `_${randomstring.generate(5)}`;
		}
	}

	return questionAnswers;
};

/**
 * Takes a value and returns it as an array
 *
 * @var {Object} value		[value is either a string or an array
 *
 * @returns {Array} [value as an array]
 */
const returnAsArray = value => {
	if (typeof value === 'string') return [value];
	return value;
};

/**
 * Takes a value and returns it as correct date format
 *
 * @var {String} value	[value is either a string or an array]
 *
 * @returns {String} [value as date format]
 */
const returnAsDate = value => {
	return moment(new Date(value)).format('DD/MM/YYYY');
};

/**
 * Takes the dataset object and builds the structural metadata object from it
 *
 * @var {Object]} dataset [dataset object]
 *
 * @returns {Object} [returns structuralMetadata object]
 */
const populateStructuralMetadata = technicaldetails => {
	let structuralMetadata = [];

	for (const dataClass of technicaldetails) {
		for (const dataElement of dataClass.elements) {
			structuralMetadata.push({
				tableName: dataClass.label,
				tableDescription: dataClass.description,
				columnName: dataElement.label,
				columnDescription: dataElement.description,
				dataType: dataElement.dataType.label,
				sensitive: !!dataElement.sensitive,
			});
		}
	}

	return structuralMetadata;
};

/**
 * Takes a version number and increases it by one
 *
 * @param   {Array}  masks    [mask contains which version that is update i.e. major, minor or patch]
 * @param   {String}  version  [version contains the current version that is to be incremented]
 *
 * @return  {String}           [returns a String with the new version number]
 */
const incrementVersion = (masks, version) => {
	if (typeof masks === 'string') {
		version = masks;
		masks = [0, 0, 0];
	}

	let bitMap = ['major', 'minor', 'patch'];
	let bumpAt = 'patch';
	let oldVer = version.match(/\d+/g);

	for (let i = 0; i < masks.length; ++i) {
		if (masks[i] === 1) {
			bumpAt = bitMap[i];
			break;
		}
	}

	let bumpIdx = bitMap.indexOf(bumpAt);
	let newVersion = [];
	for (let i = 0; i < oldVer.length; ++i) {
		if (i < bumpIdx) {
			newVersion[i] = +oldVer[i];
		} else if (i === bumpIdx) {
			newVersion[i] = +oldVer[i] + 1;
		} else {
			newVersion[i] = 0;
		}
	}

	return newVersion.join('.');
};

/**
 * Takes the data and converts it into an object that can be passed into the database to update the field
 *
 * @var {Object} data 	[data contains the fields that are to be updated]
 *
 * @return  {Object}           [returns the update object]
 */
const buildUpdateObject = data => {
	let updateObj = {};
	let { questionAnswers, updatedQuestionId, user, jsonSchema = '', percentageCompleted } = data;
	if (questionAnswers) {
		updateObj = { ...updateObj, questionAnswers, updatedQuestionId, user, percentageCompleted, 'timestamps.updated': Date.now() };
	}

	if (!isEmpty(jsonSchema)) {
		updateObj = { ...updateObj, jsonSchema, 'timestamps.updated': Date.now() };
	}

	return updateObj;
};

/**
 * Update the dataset using the updated object
 *
 * @param   {Object}  dataset  		[dataset object]
 * @param   {Object}  updateObj     [updateObj object]
 *
 * @return  {Object}                [return new copy of dataset]
 */
const updateDataset = async (dataset, updateObj) => {
	// 1. Extract properties
	let { activeflag, _id } = dataset;
	// 2. If application is in progress, update initial question answers
	if (activeflag === constants.datasetStatuses.DRAFT || activeflag === constants.applicationStatuses.INREVIEW) {
		await Data.findByIdAndUpdate(_id, updateObj, { new: true }).catch(err => {
			process.stdout.write(`DATASETONBOARDING - Error updateDataset\n`);
			throw err;
		});
		return dataset;
	}
};

/**
 * Takes the observation object and converts into the format to be sent to MDC
 *
 * @var {Object} observationsData	[The observations object that is pulled from the questionAnswers]
 *
 * @return  {Object}                [return observations in the format that is required for the MDC]
 */
const buildObservations = async observationsData => {
	let observationsArray = [];
	let regex = new RegExp('properties/observation/', 'g');

	let observationQuestions = [];
	Object.keys(observationsData).forEach(item => {
		if (item.match(regex)) {
			observationQuestions.push({ key: item, value: observationsData[item] });
		}
	});

	let observationUniqueIds = [''];
	observationQuestions.forEach(item => {
		let [, uniqueId] = item.key.split('_');
		if (!isEmpty(uniqueId) && !observationUniqueIds.find(x => x === uniqueId)) {
			observationUniqueIds.push(uniqueId);
		}
	});

	observationUniqueIds.forEach(uniqueId => {
		let entry = {};
		if (uniqueId === '') {
			observationQuestions.forEach(question => {
				if (!question.key.includes('_')) {
					let [, key] = question.key.split('properties/observation/');
					let newEntry = { [key]: question.value };
					entry = { ...entry, ...newEntry };
				}
			});
		} else {
			observationQuestions.forEach(question => {
				if (question.key.includes(uniqueId)) {
					let [keyLong] = question.key.split('_');
					let [, key] = keyLong.split('properties/observation/');
					let newEntry = { [key]: question.value };
					entry = { ...entry, ...newEntry };
				}
			});
		}
		observationsArray.push(entry);
	});

	return observationsArray;
};

/**
 * Takes the technical details object and converts into the format to be sent to MDC
 *
 * @var {Object} observationsData	[The technical details object that is pulled from the questionAnswers]
 *
 * @return  {Object}                [return technical details in the format that is required for the MDC]
 */
const buildTechnicalDetails = async structuralMetadata => {
	let technicalDetailsClasses = [];

	const orderedMetadata = map(groupBy(orderBy(structuralMetadata, ['tableName'], ['asc']), 'tableName'), (children, tableName) => ({
		tableName,
		children,
	}));

	orderedMetadata.forEach(item => {
		let technicalDetailsElements = [];
		item.children.forEach(child => {
			technicalDetailsElements.push({
				label: child.columnName,
				description: child.columnDescription,
				domainType: 'DataElement',
				dataType: {
					label: child.dataType,
					domainType: 'PrimitiveType',
				},
			});
		});

		technicalDetailsClasses.push({
			label: item.children[0].tableName,
			description: item.children[0].tableDescription,
			domainType: 'DataClass',
			elements: technicalDetailsElements,
		});
	});

	return technicalDetailsClasses;
};

/**
 * Takes the dataset object and builds the json that will be sent to the MDC
 *
 * @param   {object}  dataset  [dataset object]
 *
 * @return  {object}           [return json object to be stored in a json file]
 */
const buildJSONFile = async dataset => {
	let jsonFile = {};
	let metadata = [];
	let childDataClasses = [];
	let regex = new RegExp('properties/observation/', 'g');

	//Convert all answersQuestion entries into format for importing to MDC and taking out observation entries
	let observationQuestions = [];
	Object.keys(dataset.questionAnswers).forEach(item => {
		if (item.match(regex)) {
			observationQuestions.push({ key: item, value: dataset.questionAnswers[item] });
		} else {
			let value = !isString(dataset.questionAnswers[item]) ? JSON.stringify(dataset.questionAnswers[item]) : dataset.questionAnswers[item];
			if (
				item === 'properties/provenance/temporal/startDate' ||
				item === 'properties/provenance/temporal/endDate' ||
				item === 'properties/provenance/temporal/distributionReleaseDate'
			)
				value = moment(value, 'DD/MM/YYYY').format('YYYY-MM-DD');

			const newDatasetCatalogueItems = {
				namespace: 'org.healthdatagateway',
				key: item,
				value,
			};
			metadata.push(newDatasetCatalogueItems);
		}
	});

	//Convert observation entries into format for importing to MDC, while in the questionAnswers object they are stored with unique ids but are required to be a single string for MDC
	let observationUniqueIds = [''];
	observationQuestions.forEach(item => {
		let [, uniqueId] = item.key.split('_');
		if (!isEmpty(uniqueId) && !observationUniqueIds.find(x => x === uniqueId)) {
			observationUniqueIds.push(uniqueId);
		}
	});

	let observations = [];
	observationUniqueIds.forEach(uniqueId => {
		let entry = {};
		if (uniqueId === '') {
			observationQuestions.forEach(question => {
				if (!question.key.includes('_')) {
					let [, key] = question.key.split('properties/observation/');
					let newEntry = { [key]: question.value };
					entry = { ...entry, ...newEntry };
				}
			});
		} else {
			observationQuestions.forEach(question => {
				if (question.key.includes(uniqueId)) {
					let [keyLong] = question.key.split('_');
					let [, key] = keyLong.split('properties/observation/');
					let newEntry = { [key]: question.value };
					entry = { ...entry, ...newEntry };
				}
			});
		}
		observations.push(entry);
	});

	if (!isEmpty(observations)) {
		const newDatasetCatalogueItems = {
			namespace: 'org.healthdatagateway',
			key: 'properties/observations/observations',
			value: JSON.stringify(observations),
		};
		metadata.push(newDatasetCatalogueItems);
	}

	//Adding in the publisher entries for importing to MDC
	Object.keys(dataset.datasetv2.summary.publisher).forEach(item => {
		if (!isEmpty(dataset.datasetv2.summary.publisher[item])) {
			const newDatasetCatalogueItems = {
				namespace: 'org.healthdatagateway',
				key: `properties/summary/publisher/${item}`,
				value: dataset.datasetv2.summary.publisher[item],
			};
			metadata.push(newDatasetCatalogueItems);
		}
	});

	//Converting the strutural metadata into format for importing to MDC
	const orderedMetadata = map(groupBy(orderBy(dataset.structuralMetadata, ['tableName'], ['asc']), 'tableName'), (children, tableName) => ({
		tableName,
		children,
	}));

	orderedMetadata.forEach(item => {
		let childDataElements = [];
		item.children.forEach(child => {
			childDataElements.push({
				label: child.columnName,
				description: child.columnDescription,
				dataType: {
					label: child.dataType,
					domainType: 'PrimitiveType',
				},
			});
		});

		childDataClasses.push({
			label: item.children[0].tableName,
			description: item.children[0].tableDescription,
			childDataElements: childDataElements,
		});
	});

	//Assemble the different parts into the main json object
	jsonFile = {
		dataModel: {
			label: dataset.questionAnswers['properties/summary/title'],
			description:
				dataset.questionAnswers['properties/documentation/description'] || dataset.questionAnswers['properties/summary/abstract'],
			type: 'Data Asset',
			metadata: metadata,
			childDataClasses: childDataClasses,
		},
	};

	return jsonFile;
};

/**
 * Takes in the dataset and the v2Objects and builds the metadata quality object which contains the overall score, the error score and the completeness score
 *
 * @param   {Object}  dataset   [dataset object]
 * @param   {Object}  v2Object  [v2Object of the dataset object]
 * @param   {String}  pid       [pid of the dataset]
 *
 * @return  {Object}            [returns the metadata quality object]
 */
const buildMetadataQuality = async (dataset, v2Object, pid) => {
	let weights = {
		//'1: Summary': {
		identifier: 0.026845638,
		'summary.title': 0.026845638,
		'summary.abstract': 0.026845638,
		'summary.contactPoint': 0.026845638,
		'summary.keywords': 0.026845638,
		'summary.doiName': 0.026845638,
		'summary.publisher.name': 0.026845638,
		'summary.publisher.contactPoint': 0.0,
		'summary.publisher.memberOf': 0.006711409,
		//},
		//'2: Documentation': {
		'documentation.description': 0.026845638,
		'documentation.associatedMedia': 0.0,
		'documentation.isPartOf': 0.0,
		//},
		//'3: Coverage': {
		'coverage.spatial': 0.026845638,
		'coverage.typicalAgeRange': 0.026845638,
		'coverage.physicalSampleAvailability': 0.026845638,
		'coverage.followup': 0.006711409,
		'coverage.pathway': 0.006711409,
		//},
		//'4: Provenance': {
		'provenance.origin.purpose': 0.006711409,
		'provenance.origin.source': 0.006711409,
		'provenance.origin.collectionSituation': 0.006711409,
		'provenance.temporal.accrualPeriodicity': 0.026845638,
		'provenance.temporal.distributionReleaseDate': 0.0,
		'provenance.temporal.startDate': 0.026845638,
		'provenance.temporal.endDate': 0.0,
		'provenance.temporal.timeLag': 0.006711409,
		//},
		//'5: Accessibility': {
		'accessibility.usage.dataUseLimitation': 0.026845638,
		'accessibility.usage.dataUseRequirements': 0.026845638,
		'accessibility.usage.resourceCreator': 0.026845638,
		'accessibility.usage.investigations': 0.006711409,
		'accessibility.usage.isReferencedBy': 0.006711409,
		'accessibility.access.accessRights': 0.026845638,
		'accessibility.access.accessService': 0.006711409,
		'accessibility.access.accessRequestCost': 0.026845638,
		'accessibility.access.deliveryLeadTime': 0.026845638,
		'accessibility.access.jurisdiction': 0.026845638,
		'accessibility.access.dataController': 0.026845638,
		'accessibility.access.dataProcessor': 0.0,
		'accessibility.formatAndStandards.vocabularyEncodingScheme': 0.026845638,
		'accessibility.formatAndStandards.conformsTo': 0.026845638,
		'accessibility.formatAndStandards.language': 0.026845638,
		'accessibility.formatAndStandards.format': 0.026845638,
		//},
		//'6: Enrichment & Linkage': {
		'enrichmentAndLinkage.qualifiedRelation': 0.006711409,
		'enrichmentAndLinkage.derivation': 0.006711409,
		'enrichmentAndLinkage.tools': 0.006711409,
		//},
		//'7. Observations': {
		'observation.observedNode': 0.026845638,
		'observation.measuredValue': 0.026845638,
		'observation.disambiguatingDescription': 0.0,
		'observation.observationDate': 0.0,
		'observation.measuredProperty': 0.0,
		//},
		//'8. Structural metadata': {
		'structuralMetadata.dataClassesCount': 0.026845638,
		'structuralMetadata.tableName': 0.026845638,
		'structuralMetadata.tableDescription': 0.026845638,
		'structuralMetadata.columnName': 0.026845638,
		'structuralMetadata.columnDescription': 0.026845638,
		'structuralMetadata.dataType': 0.026845638,
		'structuralMetadata.sensitive': 0.026845638,
		//},
	};

	let metadataquality = {
		schema_version: '2.0.1',
		pid: dataset.pid,
		id: dataset.datasetid,
		publisher: dataset.datasetv2.summary.publisher.name,
		title: dataset.name,
		weighted_quality_rating: 'Not Rated',
		weighted_quality_score: 0,
		weighted_completeness_percent: 0,
		weighted_error_percent: 0,
	};

	metadataquality.pid = pid;
	metadataquality.id = v2Object.identifier;
	metadataquality.publisher = v2Object.summary.publisher.memberOf + ' > ' + v2Object.summary.publisher.name;
	metadataquality.title = v2Object.summary.title;

	const cleanV2Object = cleanUpV2Object(v2Object);

	let completeness = [];
	let totalCount = 0,
		totalWeight = 0;

	Object.entries(weights).forEach(([key, weight]) => {
		let [parentKey, subKey] = key.split('.');

		if (parentKey === 'structuralMetadata') {
			if (subKey === 'dataClassesCount') {
				if (!isEmpty(dataset.structuralMetadata)) {
					totalCount++;
					totalWeight += weight;
				} else completeness.push({ value: 'structuralMetadata.dataClassesCount', weight });
			} else if (subKey === 'tableName') {
				if (!isEmpty(dataset.structuralMetadata.filter(data => !isEmpty(data.tableName)))) {
					totalCount++;
					totalWeight += weight;
				} else completeness.push({ value: 'structuralMetadata.tableName', weight });
			} else if (subKey === 'tableDescription') {
				if (!isEmpty(dataset.structuralMetadata.filter(data => !isEmpty(data.tableDescription)))) {
					totalCount++;
					totalWeight += weight;
				} else completeness.push({ value: 'structuralMetadata.tableDescription', weight });
			} else if (subKey === 'columnName') {
				if (!isEmpty(dataset.structuralMetadata.filter(data => !isEmpty(data.columnName)))) {
					totalCount++;
					totalWeight += weight;
				} else completeness.push({ value: 'structuralMetadata.columnName', weight });
			} else if (subKey === 'columnDescription') {
				if (!isEmpty(dataset.structuralMetadata.filter(data => !isEmpty(data.columnDescription)))) {
					totalCount++;
					totalWeight += weight;
				} else completeness.push({ value: 'structuralMetadata.columnDescription', weight });
			} else if (subKey === 'dataType') {
				if (!isEmpty(dataset.structuralMetadata.filter(data => !isEmpty(data.dataType)))) {
					totalCount++;
					totalWeight += weight;
				} else completeness.push({ value: 'structuralMetadata.dataType', weight });
			} else if (subKey === 'sensitive') {
				if (!isEmpty(dataset.structuralMetadata.filter(data => !isEmpty(data.sensitive)))) {
					totalCount++;
					totalWeight += weight;
				} else completeness.push({ value: 'structuralMetadata.sensitive', weight });
			}
		} else if (parentKey === 'observation') {
			if (subKey === 'observedNode') {
				if (!isEmpty(cleanV2Object.observations.filter(data => !isEmpty(data.observedNode)))) {
					totalCount++;
					totalWeight += weight;
				} else completeness.push({ value: 'observation.observedNode', weight });
			} else if (subKey === 'measuredValue') {
				if (!isEmpty(cleanV2Object.observations.filter(data => !isEmpty(data.measuredValue)))) {
					totalCount++;
					totalWeight += weight;
				} else completeness.push({ value: 'observation.measuredValue', weight });
			} else if (subKey === 'disambiguatingDescription') {
				if (!isEmpty(cleanV2Object.observations.filter(data => !isEmpty(data.disambiguatingDescription)))) {
					totalCount++;
					totalWeight += weight;
				} else completeness.push({ value: 'observation.disambiguatingDescription', weight });
			} else if (subKey === 'observationDate') {
				if (!isEmpty(cleanV2Object.observations.filter(data => !isEmpty(data.observationDate)))) {
					totalCount++;
					totalWeight += weight;
				} else completeness.push({ value: 'observation.observationDate', weight });
			} else if (subKey === 'measuredProperty') {
				if (!isEmpty(cleanV2Object.observations.filter(data => !isEmpty(data.measuredProperty)))) {
					totalCount++;
					totalWeight += weight;
				} else completeness.push({ value: 'observation.measuredProperty', weight });
			}
		} else {
			let datasetValue = getDatasetValue(cleanV2Object, key);

			if (!isEmpty(datasetValue)) {
				totalCount++;
				totalWeight += weight;
			} else {
				completeness.push({ key, weight });
			}
		}
		//special rules around provenance.temporal.accrualPeriodicity = CONTINUOUS
	});

	let schema = {};

	let rawdata = fs.readFileSync(__dirname + '/schema.json');
	schema = JSON.parse(rawdata);

	const ajv = new Ajv({ strict: false, allErrors: true });
	addFormats(ajv);
	const validate = ajv.compile(schema);
	validate(cleanV2Object);

	let errors = [];
	let errorCount = 0,
		errorWeight = 0;

	Object.entries(weights).forEach(([key, weight]) => {
		let datasetValue = getDatasetValue(cleanV2Object, key);
		let updatedKey = '/' + key.replace(/\./g, '/');

		let errorIndex = Object.keys(validate.errors).find(key => validate.errors[key].instancePath === updatedKey);
		if (errorIndex && !isEmpty(datasetValue)) {
			errors.push({ key, value: datasetValue, weight });
			errorCount += 1;
			errorWeight += weight;
		}
	});

	metadataquality.weighted_completeness_percent = Number(100 * totalWeight).toFixed(2);
	metadataquality.weighted_error_percent = Number(100 * errorWeight).toFixed(2);
	metadataquality.weighted_quality_score = Number(50 * (totalWeight + (1 - errorWeight))).toFixed(2);

	let rating = 'Not Rated';
	if (metadataquality.weighted_quality_score > 60 && metadataquality.weighted_quality_score <= 70) rating = 'Bronze';
	else if (metadataquality.weighted_quality_score > 70 && metadataquality.weighted_quality_score <= 80) rating = 'Silver';
	else if (metadataquality.weighted_quality_score > 80 && metadataquality.weighted_quality_score <= 90) rating = 'Gold';
	else if (metadataquality.weighted_quality_score > 90) rating = 'Platinum';
	metadataquality.weighted_quality_rating = rating;

	return metadataquality;
};

/**
 * Takes the dataset v2Object and cleans up fields
 *
 * @var {Object} v2Object 		[v2Object of the dataset object]
 *
 * @return  {Object}            [returns the v2Object with the fields updated]
 */
const cleanUpV2Object = v2Object => {
	let clonedV2Object = cloneDeep(v2Object);
	//Change dates to ISO format
	if (!isEmpty(clonedV2Object.provenance.temporal.startDate))
		clonedV2Object.provenance.temporal.startDate = moment(clonedV2Object.provenance.temporal.startDate, 'DD/MM/YYYY').format('YYYY-MM-DD');
	if (!isEmpty(clonedV2Object.provenance.temporal.endDate))
		clonedV2Object.provenance.temporal.endDate = moment(clonedV2Object.provenance.temporal.endDate, 'DD/MM/YYYY').format('YYYY-MM-DD');
	if (!isEmpty(clonedV2Object.provenance.temporal.distributionReleaseDate))
		clonedV2Object.provenance.temporal.distributionReleaseDate = moment(
			clonedV2Object.provenance.temporal.distributionReleaseDate,
			'DD/MM/YYYY'
		).format('YYYY-MM-DD');
	return clonedV2Object;
};

/**
 * Take in a field and find its value in the dataset object
 *
 * @param   {Object}  dataset  [dataset object]
 * @param   {String}  field    [field string]
 *
 * @return  {String}           [return field value that is found in the dataset]
 */
const getDatasetValue = (dataset, field) => {
	return field.split('.').reduce(function (o, k) {
		return o && o[k];
	}, dataset);
};

/**
 * Takes in the type of notification and the context which contains the fields required to build the notifications
 *
 * @param   {String}  type     [type of notificaton]
 * @param   {Object}  context  [context object]
 */
const createNotifications = async (type, context) => {
	let options = {},
		html = '',
		team,
		teamMembers = [],
		teamMembersDetails,
		teamMembersIds = [];

	switch (type) {
		case constants.notificationTypes.DATASETSUBMITTED:
			// 1. Get user removed
			let adminTeam = await TeamModel.findOne({ type: 'admin' }).lean();

			let adminMembers = [];
			for (let member of adminTeam.members) {
				adminMembers.push(member.memberid);
			}

			let adminMembersDetails = await UserModel.find({ _id: { $in: adminMembers } })
				.populate('additionalInfo')
				.lean();

			let adminMembersIds = [];
			for (let member of adminMembersDetails) {
				adminMembersIds.push(member.id);
			}

			// 2. Create user notifications
			notificationBuilder.triggerNotificationMessage(
				adminMembersIds,
				context.datasetVersion !== '1.0.0'
					? `A new dataset version for "${context.name}" is available for review`
					: `A new dataset "${context.name}" is available for review`,
				'dataset submitted',
				context._id
			);
			// 3. Create email
			options = {
				name: context.name,
				publisher: context.datasetv2.summary.publisher.name,
			};
			html = emailGenerator.generateMetadataOnboardingSumbitted(options);
			emailGenerator.sendEmail(adminMembersDetails, constants.hdrukEmail, `Dataset version available for review`, html, false);
			break;
		case constants.notificationTypes.DATASETAPPROVED:
			// 1. Get user removed
			team = await TeamModel.findOne({ _id: context.datasetv2.summary.publisher.identifier }).lean();

			for (let member of team.members) {
				if (
					(Array.isArray(member.roles) && member.roles.some(role => ['manager', 'metadata_editor'].includes(role))) ||
					(typeof member.roles === 'string' && ['manager', 'metadata_editor'].includes(member.roles))
				) {
					teamMembers.push(member.memberid);
				}
			}

			teamMembersDetails = await UserModel.find({ _id: { $in: teamMembers } })
				.populate('additionalInfo')
				.lean();

			for (let member of teamMembersDetails) {
				teamMembersIds.push(member.id);
			}
			// 2. Create user notifications
			notificationBuilder.triggerNotificationMessage(
				teamMembersIds,
				context.datasetVersion !== '1.0.0'
					? `Your dataset version for "${context.name}" has been approved and is now active`
					: `A dataset "${context.name}" has been approved and is now active`,
				'dataset approved',
				context.pid
			);
			// 3. Create email
			options = {
				name: context.name,
				publisherId: context.datasetv2.summary.publisher.identifier,
				comment: context.applicationStatusDesc,
			};
			html = emailGenerator.generateMetadataOnboardingApproved(options);
			emailGenerator.sendEmail(
				teamMembersDetails,
				constants.hdrukEmail,
				`Your dataset version has been approved and is now active`,
				html,
				false
			);
			break;
		case constants.notificationTypes.DATASETREJECTED:
			// 1. Get user removed
			team = await TeamModel.findOne({ _id: context.datasetv2.summary.publisher.identifier })
				.populate([{ path: 'publisher' }])
				.lean();

			const isFederated = !_.isUndefined(team.publisher.federation) && team.publisher.federation.active;

			for (let member of team.members) {
				teamMembers.push(member.memberid);
			}

			teamMembersDetails = await UserModel.find({ _id: { $in: teamMembers } })
				.populate('additionalInfo')
				.lean();

			for (let member of team.members) {
				if (member.roles.some(role => ['manager', 'metadata_editor'].includes(role))) teamMembers.push(member.memberid);
			}
			// 2. Create user notifications
			notificationBuilder.triggerNotificationMessage(
				teamMembersIds,
				context.datasetVersion !== '1.0.0'
					? `Your dataset version for "${context.name}" has been reviewed and rejected`
					: `A dataset "${context.name}" has been reviewed and rejected`,
				'dataset rejected',
				context.datasetv2.summary.publisher.identifier
			);
			// 3. Create email
			options = {
				name: context.name,
				publisherId: context.datasetv2.summary.publisher.identifier,
				comment: context.applicationStatusDesc,
				isFederated,
			};

			html = emailGenerator.generateMetadataOnboardingRejected(options);
			let subject = options.isFederated
				? 'Your federated dataset has been rejected and requires review'
				: 'Your dataset version has been reviewed and rejected';
			emailGenerator.sendEmail(teamMembersDetails, constants.hdrukEmail, subject, html, false);
			break;
		case constants.notificationTypes.DATASETDUPLICATED:
			// 1. Get user removed
			team = await TeamModel.findOne({ _id: context.datasetv2.summary.publisher.identifier }).lean();

			for (let member of team.members) {
				teamMembers.push(member.memberid);
			}

			teamMembersDetails = await UserModel.find({ _id: { $in: teamMembers } })
				.populate('additionalInfo')
				.lean();

			for (let member of team.members) {
				if (member.roles.some(role => ['manager', 'metadata_editor'].includes(role))) teamMembers.push(member.memberid);
			}

			// 2. Create user notifications
			notificationBuilder.triggerNotificationMessage(
				teamMembersIds,
				`${context.datasetv2.summary.publisher.name} has duplicated ${context.version} of ${context.name} dataset.`,
				context.datasetv2.summary.publisher.identifier
			);
			// 3. Create email
			options = {
				name: context.name,
				publisher: context.datasetv2.summary.publisher,
				version: context.datasetVersion,
			};
			html = emailGenerator.generateMetadataOnboardingDuplicated(options);
			emailGenerator.sendEmail(
				teamMembersDetails,
				constants.hdrukEmail,
				`${context.datasetv2.summary.publisher.name} has duplicated ${context.datasetVersion} of ${context.name} dataset.`,
				html,
				false
			);
	}
};

/**
 * Take in a field and find its value in the dataset object
 *
 * @param   {Object}  dataset  [dataset object]
 * @param   {String}  field    [field string]
 *
 * @return  {String}           [return field value that is found in the dataset]
 */
const buildBulkUploadObject = async arrayOfDraftDatasets => {
	let resultObject = {
		result: true,
		error: [],
		datasets: [],
	};
	try {
		for (let dataset of arrayOfDraftDatasets) {
			try {
				//Go through each dataset and build the object to send to the DB
				let questionAnswers = populateQuestionAnswers(dataset);
				let structuralMetadata = [];
				if (!isEmpty(dataset.structuralMetadata)) {
					structuralMetadata = populateStructuralMetadata(dataset.structuralMetadata.classes);
				}
				let publisher = {};
				if (!isEmpty(dataset.summary.publisher)) {
					//Check to see that publisher exists
					publisher = await PublisherModel.findOne({ _id: { $eq: dataset.summary.publisher } }).lean();
					if (isEmpty(publisher)) {
						resultObject.error.push(`${dataset.summary.title} failed because publisher was no found`);
						resultObject.result = false;
					}

					//Check to see if this is a new entry or a new version
					let version = '',
						pid = '';
					if (!isEmpty(dataset.revisions)) {
						for (const [, value] of Object.entries(dataset.revisions)) {
							//Find a dataset that matches in the revision list
							let datasetFound = await Data.findOne({ datasetid: value }, { pid: 1 }).lean();
							if (!isEmpty(datasetFound)) {
								let latestVersion = await Data.findOne(
									{ pid: datasetFound.pid, activeflag: 'active' },
									{ pid: 1, datasetVersion: 1 }
								).lean();
								if (isEmpty(latestVersion)) {
									//If no active version found look for the next latest version using the pid and set the isDatasetArchived flag to true
									latestVersion = await Data.findOne({ pid: datasetFound.pid, activeflag: 'archive' }, { pid: 1, datasetVersion: 1 })
										.sort({ createdAt: -1 })
										.lean();
								}
								if (!isEmpty(latestVersion)) {
									pid = latestVersion.pid;
									version = incrementVersion([1, 0, 0], latestVersion.datasetVersion);
								}
							}
						}

						//If no pid then all the datasets in the revision history do not exist on the Gateway
						if (isEmpty(pid)) {
							resultObject.error.push(
								`${dataset.summary.title} failed because there was revision history but did not match an existing dataset on the Gateway`
							);
							resultObject.result = false;
						}

						//Check there is not already a draft
						let isDraft = await Data.findOne({ pid, activeflag: 'draft' }, { pid: 1 }).lean();
						if (!isEmpty(isDraft)) {
							resultObject.error.push(`${dataset.summary.title} failed because there was already a draft for this dataset`);
							resultObject.result = false;
						}

						//Check there is not already a draft in review
						let isDraftInReview = await Data.findOne({ pid, activeflag: 'inReview' }, { pid: 1 }).lean();
						if (!isEmpty(isDraftInReview)) {
							resultObject.error.push(`${dataset.summary.title} failed because there was already a draft in review for this dataset`);
							resultObject.result = false;
						}
					}

					resultObject.datasets.push({
						publisher,
						version,
						pid,
						questionAnswers,
						structuralMetadata,
						title: dataset.summary.title,
					});
				} else {
					resultObject.error.push(`${dataset.summary.title} failed because there was no publisher`);
					resultObject.result = false;
				}
			} catch (err) {
				resultObject.error.push(`${dataset.summary.title} failed because ${err}`);
				resultObject.result = false;
			}
		}

		return resultObject;
	} catch (err) {
		resultObject.error.push(`Failed because ${err}`);
		resultObject.result = false;
	}
};

/**
 * Build the datasetV2 object from dataset.questionAnswers
 *
 * @param   {Object}  dataset  [dataset.questionAnswers object]
 *
 * @return  {Object}           [return datasetv2 object]
 */
const buildv2Object = async (dataset, newDatasetVersionId = '') => {
	const publisherData = await PublisherModel.find({ _id: dataset.datasetv2.summary.publisher.identifier }).lean();
	const questionAnswers = dataset.questionAnswers;
	const observations = await buildObservations(dataset.questionAnswers);

	let datasetv2Object = {
		identifier: newDatasetVersionId || '',
		version: dataset.datasetVersion,
		issued: moment(Date.now()).format('DD/MM/YYYY'),
		modified: moment(Date.now()).format('DD/MM/YYYY'),
		revisions: [],
		summary: {
			title: questionAnswers['properties/summary/title'] || '',
			abstract: questionAnswers['properties/summary/abstract'] || '',
			publisher: {
				identifier: publisherData[0]._id.toString(),
				name: publisherData[0].publisherDetails.name,
				logo: publisherData[0].publisherDetails.logo || '',
				description: publisherData[0].publisherDetails.description || '',
				contactPoint: publisherData[0].publisherDetails.contactPoint || [],
				memberOf: publisherData[0].publisherDetails.memberOf,
				accessRights: publisherData[0].publisherDetails.accessRights || [],
				deliveryLeadTime: publisherData[0].publisherDetails.deliveryLeadTime || '',
				accessService: publisherData[0].publisherDetails.accessService || '',
				accessRequestCost: publisherData[0].publisherDetails.accessRequestCost || '',
				dataUseLimitation: publisherData[0].publisherDetails.dataUseLimitation || [],
				dataUseRequirements: publisherData[0].publisherDetails.dataUseRequirements || [],
			},
			contactPoint: questionAnswers['properties/summary/contactPoint'] || '',
			keywords: questionAnswers['properties/summary/keywords'] || [],
			alternateIdentifiers: questionAnswers['properties/summary/alternateIdentifiers'] || [],
			doiName: questionAnswers['properties/summary/doiName'] || '',
		},
		documentation: {
			description: questionAnswers['properties/documentation/description'] || '',
			associatedMedia: questionAnswers['properties/documentation/associatedMedia'] || [],
			isPartOf: questionAnswers['properties/documentation/isPartOf'] || [],
		},
		coverage: {
			spatial: questionAnswers['properties/coverage/spatial'] || [],
			typicalAgeRange: questionAnswers['properties/coverage/typicalAgeRange'] || '',
			physicalSampleAvailability: questionAnswers['properties/coverage/physicalSampleAvailability'] || [],
			followup: questionAnswers['properties/coverage/followup'] || '',
			pathway: questionAnswers['properties/coverage/pathway'] || '',
		},
		provenance: {
			origin: {
				purpose: questionAnswers['properties/provenance/origin/purpose'] || [],
				source: questionAnswers['properties/provenance/origin/source'] || [],
				collectionSituation: questionAnswers['properties/provenance/origin/collectionSituation'] || [],
			},
			temporal: {
				accrualPeriodicity: questionAnswers['properties/provenance/temporal/accrualPeriodicity'] || '',
				distributionReleaseDate: questionAnswers['properties/provenance/temporal/distributionReleaseDate'] || '',
				startDate: questionAnswers['properties/provenance/temporal/startDate'] || '',
				endDate: questionAnswers['properties/provenance/temporal/endDate'] || '',
				timeLag: questionAnswers['properties/provenance/temporal/timeLag'] || '',
			},
		},
		accessibility: {
			usage: {
				dataUseLimitation: questionAnswers['properties/accessibility/usage/dataUseLimitation'] || [],
				dataUseRequirements: questionAnswers['properties/accessibility/usage/dataUseRequirements'] || [],
				resourceCreator: questionAnswers['properties/accessibility/usage/resourceCreator'] || [],
				investigations: questionAnswers['properties/accessibility/usage/investigations'] || [],
				isReferencedBy: questionAnswers['properties/accessibility/usage/isReferencedBy'] || [],
			},
			access: {
				accessRights: questionAnswers['properties/accessibility/access/accessRights'] || [],
				accessService: questionAnswers['properties/accessibility/access/accessService'] || '',
				accessRequestCost: questionAnswers['properties/accessibility/access/accessRequestCost'] || [],
				deliveryLeadTime: questionAnswers['properties/accessibility/access/deliveryLeadTime'] || '',
				jurisdiction: questionAnswers['properties/accessibility/access/jurisdiction'] || [],
				dataProcessor: questionAnswers['properties/accessibility/access/dataProcessor'] || '',
				dataController: questionAnswers['properties/accessibility/access/dataController'] || '',
			},
			formatAndStandards: {
				vocabularyEncodingScheme: questionAnswers['properties/accessibility/formatAndStandards/vocabularyEncodingScheme'] || [],
				conformsTo: questionAnswers['properties/accessibility/formatAndStandards/conformsTo'] || [],
				language: questionAnswers['properties/accessibility/formatAndStandards/language'] || [],
				format: questionAnswers['properties/accessibility/formatAndStandards/format'] || [],
			},
		},
		enrichmentAndLinkage: {
			qualifiedRelation: questionAnswers['properties/enrichmentAndLinkage/qualifiedRelation'] || [],
			derivation: questionAnswers['properties/enrichmentAndLinkage/derivation'] || [],
			tools: questionAnswers['properties/enrichmentAndLinkage/tools'] || [],
		},
		observations: observations,
	};
	return datasetv2Object;
};

const datasetv2ObjectComparison = (updatedJSON, previousJSON) => {
	updatedJSON = flatten(updatedJSON, { safe: true, delimiter: '/' });
	previousJSON = flatten(previousJSON, { safe: true, delimiter: '/' });

	// Remove fields which change automatically between datasets
	const unusedKeys = ['identifier', 'version', 'issued', 'modified'];
	unusedKeys.forEach(key => {
		delete updatedJSON[key];
		delete previousJSON[key];
	});

	let result = [];
	const datasetv2Keys = [...new Set(Object.keys(updatedJSON).concat(Object.keys(previousJSON)))];
	datasetv2Keys.forEach(key => {
		if (
			previousJSON[key] !== updatedJSON[key] &&
			!_.isArray(updatedJSON[key], previousJSON[key]) &&
			!_.isObject(updatedJSON[key], previousJSON[key]) &&
			key !== 'observations'
		) {
			let arrayObject = {};
			arrayObject[key] = { previousAnswer: previousJSON[key], updatedAnswer: updatedJSON[key] };
			result.push(arrayObject);
		}
		if ((_.isArray(previousJSON[key]) || _.isArray(updatedJSON[key])) && key !== 'observations') {
			let previousAnswer = _.isArray(previousJSON[key]) ? previousJSON[key].join(', ') : previousJSON[key];
			let updatedAnswer = _.isArray(updatedJSON[key]) ? updatedJSON[key].join(', ') : updatedJSON[key];
			if (!_.isEqual(updatedAnswer, previousAnswer)) {
				let arrayObject = {};
				arrayObject[key] = {
					previousAnswer: previousAnswer,
					updatedAnswer: updatedAnswer,
				};
				result.push(arrayObject);
			}
		}
	});

	// Compute diff of 'observations' separately, which can be an array of objects
	const observationKeys = ['observedNode', 'measuredValue', 'disambiguatingDescription', 'observationDate', 'measuredProperty'];

	const maxObservationLength = Math.max(previousJSON['observations'].length, updatedJSON['observations'].length);
	let resultObservations = {};
	for (let i = 0; i < maxObservationLength; i++) {
		let observationNumberKey = 'observations/' + (i + 1).toString() + '/';
		resultObservations[observationNumberKey] = {};
		if (updatedJSON['observations'][i] === undefined) {
			updatedJSON['observations'][i] = {};
			observationKeys.forEach(key => {
				updatedJSON['observations'][i][key] = '';
			});
		}

		if (previousJSON['observations'][i] === undefined) {
			previousJSON['observations'][i] = {};
			observationKeys.forEach(key => {
				previousJSON['observations'][i][key] = '';
			});
		}

		observationKeys.forEach(key => {
			if (updatedJSON['observations'][i][key] === undefined) updatedJSON['observations'][i][key] = '';
			if (previousJSON['observations'][i][key] === undefined) previousJSON['observations'][i][key] = '';
			if (!_.isEqual(updatedJSON['observations'][i][key], previousJSON['observations'][i][key])) {
				resultObservations[observationNumberKey + key] = {
					previousAnswer: previousJSON['observations'][i][key],
					updatedAnswer: updatedJSON['observations'][i][key],
				};
			}
		});
		if (_.isEmpty(resultObservations[observationNumberKey])) delete resultObservations[observationNumberKey];
	}

	// Append observation diff to previous result array
	Object.keys(resultObservations).forEach(key => {
		let arrayObject = {};
		arrayObject[key] = resultObservations[key];
		result.push(arrayObject);
	});

	return result;
};

export default {
	getUserPermissionsForDataset,
	populateQuestionAnswers,
	populateStructuralMetadata,
	incrementVersion,
	buildUpdateObject,
	updateDataset,
	buildObservations,
	buildTechnicalDetails,
	buildJSONFile,
	buildMetadataQuality,
	createNotifications,
	buildBulkUploadObject,
	buildv2Object,
	datasetv2ObjectComparison,
	returnAsDate,
};
