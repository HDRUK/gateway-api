import { GlobalModel } from '../src/resources/global/global.model';

const mongoose = require('mongoose');

/**
 * Make any changes you need to make to the database here
 */
async function up() {
	// Write migration here
	await GlobalModel.updateOne({ localeId: globalData.localeId }, globalData, { upsert: true });
}

/**
 * Make any changes that UNDO the up function side effects here (if possible)
 */
async function down() {
	// Write migration here
	await GlobalModel.deleteOne({ localeId: globalData.localeId });
}

const globalData = {
	languageCode: 'en',
	localeId: 'en-gb',
	entry: {
		name: 'dataUtility',
		items: [
			{
				key: 'allowable_uses',
				dimension: 'Allowable uses',
				category: 'Access & Provision',
				definition: 'Allowable dataset usages as per the licencing agreement, following ethical and IG approval',
				includeInWizard: true,
				wizardStepTitle: 'Allowable uses',
				wizardStepDescription: 'Please select the identifier that is most relevant to you (optional)',
				wizardStepOrder: 1,
				wizardStepType: 'radio',
				entries: [
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 1,
						label: 'Academic research',
						impliedValues: ['platinum', 'gold', 'silver', 'bronze'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 2,
						label: 'Student / educational project',
						impliedValues: ['platinum', 'silver', 'gold', 'bronze'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 3,
						label: 'Charitable or non-profit project',
						impliedValues: ['platinum', 'gold', 'silver'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 4,
						label: 'Commercial project',
						impliedValues: ['platinum', 'gold'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 5,
						label: 'N/A',
						impliedValues: [],
					},
				],
			},
			{
				key: 'time_lag',
				dimension: 'Time Lag',
				category: 'Access & Provision',
				definition: 'Lag between the data being collected and added to the dataset',
				includeInWizard: true,
				wizardStepTitle: 'Time lag',
				wizardStepDescription:
					'What is the maximum acceptable time delay from the data being generated to entering the dataset? (optional)',
				wizardStepOrder: 2,
				wizardStepType: 'radio',
				entries: [
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 1,
						definition: 'Effectively real-time data',
						label: 'Near real-time data',
						impliedValues: ['platinum'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 2,
						definition: 'Approximately 1 week',
						label: '1 week',
						impliedValues: ['platinum', 'gold'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 3,
						definition: 'Approximately 1 month',
						label: '1 month',
						impliedValues: ['platinum', 'gold', 'silver'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 4,
						definition: 'Approximately 1 year',
						label: '1 year',
						impliedValues: ['platinum', 'gold', 'silver', 'bronze'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 5,
						definition: 'N/A',
						label: 'N/A',
						impliedValues: [],
					},
				],
			},
			{
				key: 'timeliness',
				dimension: 'Timeliness',
				category: 'Access & Provision',
				definition: 'Average data access request timeframe',
				entries: [
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 1,
						definition: 'Less than 2 weeks',
						impliedValues: ['platinum'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 2,
						definition: 'Less than 1 month',
						impliedValues: ['gold'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 3,
						definition: 'Less than 3 months',
						impliedValues: ['silver'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 4,
						definition: 'Less than 6 months',
						impliedValues: ['bronze'],
					},
				],
			},
			{
				key: 'data_quality_management_process',
				dimension: 'Data Quality Management Process',
				category: 'Technical Quality',
				definition: 'The level of maturity of the data quality management processes ',
				entries: [
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 1,
						definition: 'Externally verified compliance with the data management plan, e.g. by ISO, CQC, ICO or other body',
						impliedValues: ['platinum'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 2,
						definition: 'Evidence that the data management plan has been implemented is available',
						impliedValues: ['silver'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 3,
						definition: 'A documented data management plan covering collection, auditing, and management is available for the dataset',
						impliedValues: ['bronze'],
					},
				],
			},
			{
				key: 'pathway_coverage',
				dimension: 'Pathway coverage',
				category: 'Coverage',
				definition: 'Representation of multi-disciplinary healthcare data',
				entries: [
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 1,
						definition: 'Contains data across more than two tiers',
						impliedValues: ['platinum'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 2,
						definition: 'Contains multimodal data or data that is linked across two tiers (e.g. primary and secondary care)',
						impliedValues: ['gold'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 3,
						definition: 'Contains data from multiple specialties or services within a single tier of care',
						impliedValues: ['silver'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 4,
						definition: 'Contains data from a single speciality or area',
						impliedValues: ['bronze'],
					},
				],
			},
			{
				key: 'length_of_follow_up',
				dimension: 'Length of follow up',
				category: 'Coverage',
				definition: 'Average timeframe in which a patient appears in a dataset (follow up period)',
				wizardStepTitle: 'Length of follow up',
				wizardStepDescription: 'What is the minimum required time frame for patients appearing in dataset, on average? (optional)',
				includeInWizard: true,
				wizardStepOrder: 3,
				wizardStepType: 'radio',
				entries: [
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 1,
						definition: 'Between 1-6 months',
						label: 'Between 1-6 months',
						impliedValues: ['platinum', 'gold', 'silver', 'bronze'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 2,
						definition: 'Between 6-12 months',
						label: 'Between 6-12 months',
						impliedValues: ['platinum', 'gold', 'silver'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 3,
						definition: 'Between 1-10 years',
						label: 'Between 1-10 years',
						impliedValues: ['platinum', 'gold'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 4,
						definition: 'More than 10 years',
						label: 'More than 10 years',
						impliedValues: ['platinum'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 5,
						definition: 'N/A',
						label: 'N/A',
						impliedValues: [],
					},
				],
			},
			{
				key: 'availability_of_additional_documentation_and_support',
				dimension: 'Availability of additional documentation and support',
				category: 'Data Documentation',
				definition: 'Available dataset documentation in addition to the data dictionary',
				entries: [
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 1,
						definition: 'As Gold, plus support personnel available to answer questions',
						impliedValues: ['platinum'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 2,
						definition:
							'As Silver, plus dataset publication was supported with a journal article explaining the dataset in detail, or dataset training materials',
						impliedValues: ['gold'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 3,
						definition: 'Comprehensive ReadMe describing extracting and use of data, Dataset FAQS available, Visual data model provided',
						impliedValues: ['silver'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 4,
						definition: 'Past journal articles demonstrate that knowledge of the data exists',
						impliedValues: ['bronze'],
					},
				],
			},
			{
				key: 'data_model',
				dimension: 'Data Model',
				category: 'Data Documentation',
				definition: 'Availability of clear, documented data model',
				includeInWizard: true,
				wizardStepTitle: 'Data model',
				wizardStepDescription: 'What data model requirements do you have? (optional)',
				wizardStepOrder: 4,
				wizardStepType: 'radio',
				entries: [
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 1,
						definition: 'Known and accepted data model but some key field un-coded or free text',
						label: 'Known and accepted data model, with some key fields uncoded ',
						impliedValues: ['platinum', 'gold', 'silver', 'bronze'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 2,
						definition: 'Key fields codified using a local standard',
						label: 'Key fields coded using local standard',
						impliedValues: ['platinum', 'gold', 'silver'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 3,
						definition: 'Key fields codified using a national or international standard',
						label: 'Key fields coded using national/international standard',
						impliedValues: ['platinum', 'gold'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 4,
						definition: 'Data Model conforms to a national standard and key fields codified using a national/international standard',
						label: 'Model conforms to national standard and key fields coded to national/internal standard',
						impliedValues: ['platinum'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 5,
						definition: 'N/A',
						label: 'N/A',
						impliedValues: [],
					},
				],
			},
			{
				key: 'data_dictionary',
				dimension: 'Data Dictionary',
				category: 'Data Documentation',
				definition: 'Provided documented data dictionary and terminologies',
				entries: [
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 1,
						definition: 'Dictionary is based on international standards and includes mapping',
						impliedValues: ['platinum'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 2,
						definition: 'Dictionary relates to national definitions',
						impliedValues: ['gold'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 3,
						definition: 'Definitions compiled into local data dictionary which is available online',
						impliedValues: ['silver'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 4,
						definition: 'Data definitions available',
						impliedValues: ['bronze'],
					},
				],
			},
			{
				key: 'provenance',
				dimension: 'Provenance',
				category: 'Data Documentation',
				definition: 'Clear description of source and history of the dataset, providing a "transparent data pipeline"',
				includeInWizard: true,
				wizardStepTitle: 'Provenance',
				wizardStepDescription: 'To what level of detail do you require the origin of the dataset to be documented? (optional)',
				wizardStepOrder: 5,
				wizardStepType: 'radio',
				entries: [
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 1,
						definition: 'Source of the dataset is documented',
						label: 'Dataset source documented',
						impliedValues: ['platinum', 'gold', 'silver', 'bronze'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 2,
						definition: 'Source of the dataset and any transformations, rules and exclusions documented',
						label: 'Dataset source, any transformations, rule and exclusions documented',
						impliedValues: ['platinum', 'gold', 'silver'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 3,
						definition: 'All original data items listed, all transformations, rules and exclusion listed and impact of these',
						label: 'All original data items, transformations, rules, exclusions and impact listed',
						impliedValues: ['platinum', 'gold'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 4,
						definition:
							'Ability to view earlier versions, including versions before any transformations have been applied data (in line with deidentification and IG approval) and review the impact of each stage of data cleaning',
						label: "Earlier and 'raw' versions and the impact of each stage of data cleaning",
						impliedValues: ['platinum'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 5,
						definition:'N/A',
						label: "N/A",
						impliedValues: [],
					},
				],
			},
			{
				includeInWizard: true,
				wizardStepTitle: 'Search terms',
				wizardStepDescription: 'Please type in any relevant search terms to refine your search (optional)',
				wizardStepOrder: 6,
				wizardStepType: 'search',
				entries: [],
			},
			{
				key: 'linkages',
				dimension: 'Linkages',
				category: 'Value & Interest',
				definition: 'Ability to link with other datasets',
				entries: [
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 1,
						definition: 'Existing linkage with reusable or downstream approvals',
						impliedValues: ['platinum'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 2,
						definition:
							'List of restrictions on the type of linkages detailed. List of previously successful dataset linkages performed, with navigable links to linked datasets via at DOI/URL',
						impliedValues: ['gold'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 3,
						definition: 'Available linkages outlined and/or List of datasets previously successfully linked provided',
						impliedValues: ['silver'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 4,
						definition: 'Identifiers to demonstrate ability to link to other datasets',
						impliedValues: ['bronze'],
					},
				],
			},
			{
				key: 'data_enrichments',
				dimension: 'Data Enrichments',
				category: 'Value & Interest',
				definition: 'Data sources enriched with annotations, image labels, phenomes, derivations, NLP derived data labels',
				entries: [
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 1,
						definition: 'The data include additional derived fields, or enriched data',
						impliedValues: ['platinum'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 2,
						definition: 'The data include additional derived fields, or enriched data used by other available data sources',
						impliedValues: ['gold'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 3,
						definition: 'The derived fields or enriched data were generated from, or used by, a peer reviewed algorithm',
						impliedValues: ['silver'],
					},
					{
						id: mongoose.Types.ObjectId(),
						displayOrder: 4,
						definition: 'The data includes derived fields or enriched data from a national report',
						impliedValues: ['bronze'],
					},
				],
			},
		],
	},
};

module.exports = { up, down };
