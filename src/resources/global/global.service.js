export default class GlobalService {
	constructor(globalRepository) {
		this.globalRepository = globalRepository;
	}

	getGlobal(query = {}) {
		return this.globalRepository.getGlobal(query);
	}

	getMasterSchema(query = {}) {
		return this.globalRepository.getGlobal({
			...query,
			masterSchema: { $exists: true },
		});
	}
}
