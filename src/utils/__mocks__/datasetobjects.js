import { ObjectID } from 'mongodb';

export const datasetQuestionAnswersMocks = {
	'properties/summary/abstract': 'test',
	'properties/summary/contactPoint': 'test@test.com',
	'properties/summary/keywords': ['testKeywordBowel', 'testKeywordCancer'],
	'properties/provenance/temporal/accrualPeriodicity': 'DAILY',
	'properties/provenance/temporal/startDate': '25/12/2021',
	'properties/provenance/temporal/timeLag': 'NOT APPLICABLE',
	'properties/accessibility/access/accessRights': ['http://www.google.com'],
	'properties/accessibility/access/jurisdiction': ['GB-GB'],
	'properties/accessibility/access/dataController': 'testtesttesttesttesttest',
	'properties/accessibility/formatAndStandards/vocabularyEncodingScheme': ['LOCAL'],
	'properties/accessibility/formatAndStandards/conformsTo': ['NHS SCOTLAND DATA DICTIONARY'],
	'properties/accessibility/formatAndStandards/language': ['ab'],
	'properties/accessibility/formatAndStandards/format': ['testtesttest'],
	'properties/observation/observedNode': 'PERSONS',
	'properties/observation/measuredValue': '25',
	'properties/observation/disambiguatingDescription': 'testtesttest',
	'properties/observation/observationDate': '03/09/2021',
	'properties/observation/measuredProperty': 'Count',
	'properties/summary/title': 'Test title',
	'properties/provenance/origin/purpose': ['STUDY'],
	'properties/coverage/physicalSampleAvailability': ['NOT AVAILABLE'],
	'properties/enrichmentAndLinkage/qualifiedRelation': ['https://google.com', 'https://google.com'],
	'properties/observation/observedNode_1xguo': 'EVENTS',
	'properties/observation/measuredValue_1xguo': '100',
	'properties/observation/disambiguatingDescription_1xguo': 'testtesttest',
	'properties/observation/observationDate_1xguo': '03/11/2021',
	'properties/observation/measuredProperty_1xguo': 'Count',
};

export const datasetv2ObjectMock = {
	identifier: '',
	version: '2.0.0',
	revisions: [],
	summary: {
		title: 'Test title',
		abstract: 'test',
		publisher: {
			identifier: '5f3f98068af2ef61552e1d75',
			name: 'SAIL',
			logo: '',
			description: '',
			contactPoint: [],
			memberOf: 'ALLIANCE',
			accessRights: [],
			deliveryLeadTime: '',
			accessService: '',
			accessRequestCost: '',
			dataUseLimitation: [],
			dataUseRequirements: [],
		},
		contactPoint: 'test@test.com',
		keywords: ['testKeywordBowel', 'testKeywordCancer'],
		alternateIdentifiers: [],
		doiName: '',
	},
	documentation: { description: '', associatedMedia: [], isPartOf: [] },
	coverage: {
		spatial: [],
		typicalAgeRange: '',
		physicalSampleAvailability: ['NOT AVAILABLE'],
		followup: '',
		pathway: '',
	},
	provenance: {
		origin: { purpose: ['STUDY'], source: [], collectionSituation: [] },
		temporal: {
			accrualPeriodicity: 'DAILY',
			distributionReleaseDate: '',
			startDate: '25/12/2021',
			endDate: '',
			timeLag: 'NOT APPLICABLE',
		},
	},
	accessibility: {
		usage: {
			dataUseLimitation: [],
			dataUseRequirements: [],
			resourceCreator: [],
			investigations: [],
			isReferencedBy: [],
		},
		access: {
			accessRights: ['http://www.google.com'],
			accessService: '',
			accessRequestCost: [],
			deliveryLeadTime: '',
			jurisdiction: ['GB-GB'],
			dataProcessor: '',
			dataController: 'testtesttesttesttesttest',
		},
		formatAndStandards: {
			vocabularyEncodingScheme: ['LOCAL'],
			conformsTo: ['NHS SCOTLAND DATA DICTIONARY'],
			language: ['ab'],
			format: ['testtesttest'],
		},
	},
	enrichmentAndLinkage: {
		qualifiedRelation: ['https://google.com', 'https://google.com'],
		derivation: [],
		tools: [],
	},
	observations: [
		{
			observedNode: 'PERSONS',
			measuredValue: '25',
			disambiguatingDescription: 'testtesttest',
			observationDate: '03/09/2021',
			measuredProperty: 'Count',
		},
		{
			observedNode: 'EVENTS',
			measuredValue: '100',
			disambiguatingDescription: 'testtesttest',
			observationDate: '03/11/2021',
			measuredProperty: 'Count',
		},
	],
};

export const publisherDetailsMock = [
	{
		_id: ObjectID('5f3f98068af2ef61552e1d75'),
		name: 'ALLIANCE > SAIL',
		active: true,
		imageURL: '',
		dataRequestModalContent: {},
		allowsMessaging: true,
		workflowEnabled: true,
		allowAccessRequestManagement: true,
		publisherDetails: { name: 'SAIL', memberOf: 'ALLIANCE' },
		uses5Safes: true,
		mdcFolderId: 'c4f50de0-2188-426b-a6cd-6b11a8d6c3cb',
	},
];

export const structuralMetadataMock = [
	{
		label: 'papers',
		description: 'HDR UK Paper and Preprints',
		domainType: 'DataClass',
		elements: [
			{
				label: 'urls',
				description: 'List of URLS (DOI, HTML, PDF)',
				dataType: {
					label: 'List (URLS)',
					domainType: 'PrimitiveType',
				},
				sensitive: true,
				domainType: 'DataElement',
			},
			{
				label: 'date',
				description: 'Date of Publication',
				dataType: {
					label: 'Date',
					domainType: 'PrimitiveType',
				},
				sensitive: false,
				domainType: 'DataElement',
			},
			{
				label: 'date',
				description: 'Date of Publication1',
				dataType: {
					label: 'Date',
					domainType: 'PrimitiveType',
				},
				domainType: 'DataElement',
			},
		],
	},
];
