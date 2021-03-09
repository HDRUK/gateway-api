import Repository from '../base/repository';
import { Dataset } from './dataset.model';

export default class DatasetRepository extends Repository {
	constructor() {
		super(Dataset);
		this.dataset = Dataset;
	}

	async getDataset(query, options) {
		return this.findOne(query, options);
	}

	async getDatasets(query, options) {
		return this.find(query, options);
	}

	async getDatasetRevisions(pid) {
		if (!pid) {
			return {};
		}
		// Get dataset versions using pid
		const query = { pid, fields:'datasetid,datasetVersion,activeflag' };
		const options = { lean: true };
		const datasets = await this.find(query, options);
		// Create revision structure
		return datasets.reduce((obj, dataset) => {
			const { datasetVersion = 'default', datasetid = 'empty', activeflag = '' } = dataset;
			obj[datasetVersion] = datasetid;
			// Set the active dataset as the latest version
			if (activeflag === 'active') {
				obj['latest'] = datasetid;
			}
			return obj;
		}, {});
	}
}
