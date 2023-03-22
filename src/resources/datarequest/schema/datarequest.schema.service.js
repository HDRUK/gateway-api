export default class DatarequestschemaService {
	constructor(datarequestschemaRepository) {
		this.datarequestschemaRepository = datarequestschemaRepository;
	}

	getDatarequestschema(id, query = {}, options = {}) {
		// Protect for no id passed
		if (!id) return;

		query = { ...query, id };
		return this.datarequestschemaRepository.getDatarequestschema(query, options);
	}

	getDatarequestschemaById(id) {
		return this.datarequestschemaRepository.findById(id, { lean: true });
	}

	getDatarequestschemas(query = {}) {
		return this.datarequestschemaRepository.getDatarequestschemas(query);
	}

	updateDatarequestschema(id, datarequestschema) {
		return this.datarequestschemaRepository.update(id, datarequestschema);
	}
}
