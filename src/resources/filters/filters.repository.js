import Repository from '../base/repository';
import { Filters } from './filters.model';

export default class FiltersRepository extends Repository {
	constructor() {
		super(Filters);
		this.filters = Filters;
	}

	async getFilters(id, query) {
		query = { ...query, id };
		const options = { lean: false };
		return this.findOne(query, options);
	}

	async updateFilterSet(filters, type) {
		await Filters.findOneAndUpdate({ type }, { keys: filters });
	}
}
