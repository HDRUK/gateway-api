import { isArray, isEmpty, isNil, uniq } from 'lodash';
import helper from '../utilities/helper.util';

export default class FiltersService {
	constructor(filtersRepository, datasetRepository) {
		this.filtersRepository = filtersRepository;
		this.datasetRepository = datasetRepository;
	}

	async getFilters(id, query = {}) {
		// 1. Get filters from repository for the entity type and query provided
		const options = { lean: false };
		let filters = await this.filtersRepository.getFilters(id, query, options);
		if(filters) {
			filters = filters.mapDto();
		}
		return filters;
	}

	async optimiseFilters(type) {
		// 1. Build filters from type using entire Db collection
		const filters = await this.buildFilters(type, { activeflag: 'active' });
		// 2. Save updated filter values to filter cache
		//await this.saveFilters(filters, type);
		await this.filtersRepository.updateFilterSet(filters, type);
	}

	async buildFilters(type, query = {}, useCache = false) {
		// 1. Use cached filters if instructed, need to remove type when all v2 filters come on
		if (useCache && type === 'dataset') {
			const options = { lean: true };
			const { keys: filters = {} } = await this.filtersRepository.getFilters(type, {}, options) || {};
			return filters;
		}
		
		let filters = {},
			sortedFilters = {},
			entities = [],
			fields = '';
			
		// 2. Query Db for required entity if array of entities has not been passed
		switch (type) {
			case 'dataset':
				// Get minimal payload to build filters
				fields = `hasTechnicalDetails,
							tags.features,
							datasetfields.datautility,datasetfields.publisher,datasetfields.phenotypes,
							datasetv2.coverage,datasetv2.provenance.origin,datasetv2.provenance.temporal,datasetv2.accessibility.access,datasetv2.accessibility.formatAndStandards`;
				entities = await this.datasetRepository.getDatasets({ ...query, fields }, { lean: true });
				break;
		}
		// 3. Loop over each entity
		entities.forEach(entity => {
			// 4. Get the filter values provided by each entity
			const filterValues = this.getFilterValues(entity, type);
			// 5. Iterate through each filter value/property
			for (const key in filterValues) {
				let values = [];
				// 6. Normalise string and array data by maintaining only arrays in 'values'
				if (isArray(filterValues[key])) {
					if (!isEmpty(filterValues[key]) && !isNil(filterValues[key])) {
						values = filterValues[key].filter(value => !isEmpty(value.toString().trim()));
					}
				} else {
					if (!isEmpty(filterValues[key]) && !isNil(filterValues[key])) {
						values = [filterValues[key]];
					}
				}
				// 7. Populate running filters with all values
				if (!filters[key]) {
					filters[key] = [...values];
				} else {
					filters[key] = [...filters[key], ...values];
				}
			}
		});
		// 8. Iterate through each filter
		Object.keys(filters).forEach(filterKey => {
			// 9. Set filter values to title case and remove white space
			filters[filterKey] = filters[filterKey].map(value => helper.toTitleCase(value.toString().trim()));
			// 10. Distinct filter values
			const distinctFilter = uniq(filters[filterKey]);
			// 11. Sort filter values and update final object
			sortedFilters[filterKey] = distinctFilter.sort(function (a, b) {
				return a.toString().toLowerCase().localeCompare(b.toString().toLowerCase());
			});
		});
		return sortedFilters;
	}

	getFilterValues(entity, type) {
		let filterValues = {};
		// 1. Switch between entity type for varying filters
		switch (type) {
			case 'dataset':
				// 2. Extract all properties used for filtering
				if (isEmpty(entity.datasetv2)) {
					delete entity.datasetv2;
				}
				const {
					tags: { features = [] } = {},
					datasetfields: { datautility = {}, publisher = '', phenotypes = [] } = {},
					datasetv2: {
						coverage = {},
						provenance: { origin = {}, temporal = {} } = {},
						accessibility: { access = {}, formatAndStandards = {} },
					} = { coverage: {}, provenance: {}, accessibility: {} },
				} = entity;
				// 3. Create flattened filter props object
				filterValues = {
					publisher,
					phenotypes: [...phenotypes.map(phenotype => phenotype.name)],
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
	}
}
