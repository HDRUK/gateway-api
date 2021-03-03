export default class FiltersService {
	constructor(filtersRepository) {
		this.filtersRepository = filtersRepository;
	}

	async getFilters(id, query = {}) {
		const filters = await this.filtersRepository.getFilters(id, query);
		const mappedFilters = filters.mapDto();
		return mappedFilters;
	}
}
