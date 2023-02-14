import Entity from '../base/entity';

export default class DatasetClass extends Entity {
	constructor(obj) {
		super();
		Object.assign(this, obj);
	}

	checkLatestVersion() {
		return this.activeflag === 'active';
	}

	toV2Format() {
		// Version 2 transformer map
		const transformer = {
			dataset: {
				pid: 'pid',
				id: 'datasetid',
				version: 'datasetVersion',
				identifier: 'datasetv2.identifier',
				summary: 'datasetv2.summary',
				documentation: 'datasetv2.documentation',
				revisions: 'revisions',
				modified: 'updatedAt',
				issued: 'createdAt',
				accessibility: 'datasetv2.accessibility',
				observations: 'datasetv2.observations',
				provenance: 'datasetv2.provenance',
				coverage: 'datasetv2.coverage',
				enrichmentAndLinkage: 'datasetv2.enrichmentAndLinkage',
				structuralMetadata: {
					structuralMetadataCount: {},
					dataClasses: 'datasetfields.technicaldetails',
				},
			},
			relatedObjects: 'relatedObjects',
			metadataQuality: 'datasetfields.metadataquality',
			dataUtility: 'datasetfields.datautility',
			viewCounter: 'counter',
			submittedDataAccessRequests: 'submittedDataAccessRequests',
		};

		// Transform entity into v2 using map, with stict applied to retain null values
		const transformedObject = this.transformTo(transformer, { strict: false });

		// Manually update identifier URL link
		transformedObject.dataset.identifier = `https://web.www.healthdatagateway.org/dataset/${this.datasetid}`;
		
		// Append static schema details for v2
		transformedObject.dataset['@schema'] = {
			type: `Dataset`,
			version: `2.0.0`,
			url: `https://raw.githubusercontent.com/HDRUK/schemata/master/schema/dataset/latest/dataset.schema.json`,
		}

		// Return v2 object
		return transformedObject;
	}
}
