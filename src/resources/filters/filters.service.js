import { isArray, isEmpty, isNil, uniq } from 'lodash';

export default class FiltersService {
	constructor(filtersRepository, datasetRepository) {
		this.filtersRepository = filtersRepository;
		this.datasetRepository = datasetRepository;
	}

	async getFilters(id, query = {}) {
		const filters = await this.filtersRepository.getFilters(id, query);
		const mappedFilters = filters.mapDto();
		return mappedFilters;
	}

	async optimiseFilters(type) {
		// 1. Build filters from type using entire Db collection
		const filters = await this.buildFilters(type);
		// 2. Save updated filter values to filter cache
		this.saveFilters(filters, type);
	}

	async buildFilters(type, entities = []) {
		let filters = {};
		// 1. Query Db for required entity if array of entities has not been passed
		if (isEmpty(entities)) {
			switch (type) {
				case 'dataset':
					entities = await this.datasetRepository.getDatasets();
					break;
			}
		}
		// 2. Loop over each entity
		entities.forEach(entity => {
			// 3. Get the filter values provided by each entity
			const filterValues = this.getFilterValues(entity, type);
			// 4. Iterate through each filter value/property
			for (const key in filterValues) {
				let values = [];
				// 5. Normalise string and array data by maintaining only arrays in 'values'
				if (isArray(filterValues[key])) {
					if (!isEmpty(filterValues[key]) && !isNil(filterValues[key])) {
						values = filterValues[key];
					}
				} else {
					if (!isEmpty(filterValues[key]) && !isNil(filterValues[key])) {
						values = [filterValues[key]];
					}
				}
				// 6. Populate running filters with all values
				if (!filters[key]) {
					filters[key] = [...values];
				} else {
					filters[key] = [...filters[key], ...values];
				}
			}
		});
		return filters;
	}

	getFilterValues(entity, type) {
		let filterValues = {};
		// 1. Switch between entity type for varying filters
		switch (type) {
			case 'dataset':
				// 2. Extract all properties used for filtering
				const {
					tags: { features = [] } = {},
					datasetfields: { datautility = {}, publisher = '', phenotypes = [] } = {},
					datasetv2: {
						coverage = {},
						provenance: { origin = {}, temporal = {} } = {},
						accessibility: { access = {}, formatAndStandards = {} },
					} = { coverage: {}, provenance: {}, accessibility: {} },
				} = entity.toObject();
				// 3. Create flattened filter props object
				filterValues = {
					publisher,
					...phenotypes,
					features,
					...datautility,
					...coverage,
					...origin,
					...temporal,
					...access,
					...formatAndStandards,
				};
				break;
		}
		// 4. Return filter values
		return filterValues;
	};
	
	async saveFilters (filters, type) {
		// 1. Establish object for saving to MongoDb once populated
		const sortedFilters = {};
		// 2. Iterate through each filter
		Object.keys(filters).forEach(filterKey => {
			// 3. Distinct filter values
			const distinctFilter = uniq(filters[filterKey]);
			// 4. Sort filter values and update final object for saving
			sortedFilters[filterKey] = distinctFilter.sort(function (a, b) {
				return a.toString().toLowerCase().localeCompare(b.toString().toLowerCase());
			});
		});
		// 5. Save filters to MongoDb
		await this.filtersRepository.updateFilterSet(sortedFilters, type);
	};
}


