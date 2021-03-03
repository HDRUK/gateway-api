import Entity from '../base/entity';
import * as mapper from './filters.mapper';

export default class FiltersClass extends Entity {
	constructor(obj) {
		super();
		Object.assign(this, obj);
	}

	mapDto() {
		if (!this.id) {
			console.error('Failed to load filters');
			return;
		}

		const filters = mapper[`${this.id}Filters`];

		console.log(this.keys);
		// Generic recursive loop to build filter tree with filter key/values from MongoDb

		return filters;
	}
}


