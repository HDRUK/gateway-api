import Repository from '../../base/repository';
import { DataRequestSchemaModel } from './datarequest.schemas.model';

export default class DatarequestschemaRepository extends Repository {
	constructor() {
		super(DataRequestSchemaModel);
		this.datarequestschema = DataRequestSchemaModel;
	}

	async getDatarequestschema(query, options) {
		return this.findOne(query, options);
	}

	async getDatarequestschemas(query) {
		const options = { lean: true };
		return this.find(query, options);
	}
}
