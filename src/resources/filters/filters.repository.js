import Repository from '../base/repository';
import { Filters } from './filters.model';

export default class FiltersRepository extends Repository {
	constructor() {
		super(Filters);
		this.filters = Filters;
	}

	async getFilters(id, query = {}, options) {
		query = { ...query, id };
		return this.findOne(query, options);
	}

	async updateFilterSet(filters, type) {
		await Filters.findOneAndUpdate({ id: type }, { keys: filters }, { upsert: true }, (err) => {
			if(err) {
				console.error(err.message);
			}
		});
	}
}
