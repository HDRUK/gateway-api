import { isArray, isEmpty, isNil, uniq, has, isString, isObject } from 'lodash';
import helper from '../utilities/helper.util';
import { commericalConstants, validCommericalUseOptions } from './utils/filters.util';

export default class FiltersService {
	constructor(
		filtersRepository,
		datasetRepository,
		toolRepository,
		projectRepository,
		paperRepository,
		collectionRepository,
		courseRepository,
		DataUseRegisterRepository
	) {
		this.filtersRepository = filtersRepository;
		this.datasetRepository = datasetRepository;
		this.toolRepository = toolRepository;
		this.projectRepository = projectRepository;
		this.paperRepository = paperRepository;
		this.collectionRepository = collectionRepository;
		this.courseRepository = courseRepository;
		this.DataUseRegisterRepository = DataUseRegisterRepository;
	}

	async getFilters(id, query = {}) {
		// 1. Get filters from repository for the entity type and query provided
		const options = { lean: false };
		let filters = await this.filtersRepository.getFilters(id, query, options);
		if (filters && !has(query, 'fields')) {
			filters = filters.mapDto();
		}
		return filters;
	}

	/**
	 * [computeCommericalUse - Calculates and sets commericalUse value]
	 *
	 * @param   {Object}  dataUtility  [dataUtility Object]
	 * @param   {Object}  datasetv2    [datasetv2 Object]
	 * @return  {boolean}
	 */
	computeCommericalUse(dataUtility = {}, datasetv2 = {}) {
		// LOGIC agreed on IG-1661
		// 1. check object contains the fields we need to compute against for commericalUse
		let hasAllowableUses = has(dataUtility, 'allowable_uses');
		let hasDataUseLimitation = has(datasetv2, 'accessibility.usage.dataUseLimitation');
		// Not to be computed until further notice
		//let hasDataUseRequirements = has(datasetv2, 'accessibility.usage.dataUseRequirements');

		// 2. check allowable_uses
		if (hasAllowableUses) {
			const { allowable_uses = '' } = dataUtility;
			const allowableUses = allowable_uses.trim().toUpperCase();
			if (allowableUses === commericalConstants.gold || allowableUses === commericalConstants.platinum) {
				return true;
			}
		}
		// 3. check data_use_limitation set commercialUse true"
		if (hasDataUseLimitation) {
			// destructure out dataUseLimitation value
			const {
				accessibility: {
					usage: { dataUseLimitation },
				},
			} = datasetv2;

			return this.calculateCommercialUsage(dataUseLimitation);
		}
		return false;
	}

	/**
	 * [calculateCommercialUsage]
	 *
	 * @param   {Array | String }  dataType  [field type ie data_use_limitation, data_use_requirements]
	 * @return  {boolean}  return true false based on logic for commericalUsage
	 */
	calculateCommercialUsage(dataType) {
		const isTypeString = isString(dataType);
		const isTypeArray = isObject(dataType);

		if (
			isTypeString &&
			(dataType.trim().toUpperCase() === commericalConstants.noRestriction ||
				dataType.trim().toUpperCase() === commericalConstants.commercialResearchUse)
		)
			return true;
		else if (isTypeArray && validCommericalUseOptions(dataType)) return true;

		return false;
	}

	async optimiseFilters(type) {
		// 1. Build filters from type using entire Db collection
		let query = { $and: [{ activeflag: 'active' }] };
		if (type === 'collection') {
			query['$and'].push({ publicflag: true });
		} else if (type === 'course') {
			query['$and'].push({ $or: [{ 'courseOptions.startDate': { $gte: new Date(Date.now()) } }, { 'courseOptions.flexibleDates': true }] });
		}
		const filters = await this.buildFilters(type, query);
		// 2. Save updated filter values to filter cache
		//await this.saveFilters(filters, type);
		await this.filtersRepository.updateFilterSet(filters, type);
	}

	async buildFilters(type, query = {}, useCache = false) {
		// 1. Use cached filters if instructed, need to remove type when all v2 filters come on
		if (useCache && type === 'dataset') {
			const options = { lean: true };
			const { keys: filters = {} } = (await this.filtersRepository.getFilters(type, {}, options)) || {};
			return filters;
		}

		let filters = {},
			sortedFilters = {},
			entities = [],
			fields = '';

		// 2. Query Db for required entity if array of entities has not been passed
		query.limit = 0; // unlimited rows
		switch (type) {
			// Get minimal payload to build filters
			case 'dataset':
				fields = `hasTechnicalDetails,
							tags.features,
							datasetfields.datautility,datasetfields.publisher,datasetfields.phenotypes,
							datasetv2.coverage,datasetv2.provenance.origin,datasetv2.provenance.temporal,datasetv2.accessibility.access,datasetv2.accessibility.formatAndStandards`;
				entities = await this.datasetRepository.getDatasets({ ...query, fields }, { lean: true });
				break;
			case 'tool':
				fields = `categories.category,programmingLanguage.programmingLanguage,tags.features,tags.topics`;
				entities = await this.toolRepository.getTools({ ...query, fields }, { lean: true });
				break;
			case 'project':
				fields = `categories.category,tags.features,tags.topics`;
				entities = await this.projectRepository.getProjects({ ...query, fields }, { lean: true });
				break;
			case 'paper':
				fields = `tags.features,tags.topics`;
				entities = await this.paperRepository.getPapers({ ...query, fields }, { lean: true });
				break;
			case 'collection':
				fields = `persons.fullName,keywords`;
				entities = await this.collectionRepository.getCollections({ ...query, fields }, { aggregate: true });
				break;
			case 'course':
				fields = `courseOptions.startDate,provider,location,courseOptions.studyMode,award,entries.level,domains,keywords,competencyFramework,nationalPriority`;
				entities = await this.courseRepository.getCourses({ ...query, fields }, { lean: true, dateFormat: 'DD MMM YYYY' });
				break;
			case 'dataUseRegister':
				fields = `organisationName,organisationSector,keywords,publisherDetails.name,fundersAndSponsors`;
				entities = await this.DataUseRegisterRepository.getDataUseRegistersFilters({ ...query, fields }, { aggregate: true });
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
						values = filterValues[key].filter(value => !isNil(value) && !isEmpty(value.toString().trim()));
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
			// 9. Set filter values to title case (all except publisher) / upper case (publisher) and remove white space
			if (filterKey === 'publisher') {
				filters[filterKey] = filters[filterKey].map(value => 
					value.includes('>') ? value.split('>')[1].toString().toUpperCase().trim() : value.toString().toUpperCase().trim()
				);
			} else {
				filters[filterKey] = filters[filterKey].map(value => (filterKey === 'spatial') ? value.toString().trim() : helper.toTitleCase(value.toString().trim()));
			}
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
			case 'dataset': {
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
					phenotypes: [...phenotypes.map(phenotype => phenotype.name)],
					features,
					...datautility,
					...coverage,
					...origin,
					...temporal,
					...access,
					...formatAndStandards,
					publisher,
				};
				break;
			}
			case 'tool': {
				// 2. Extract all properties used for filtering
				let {
					categories: { category = '' } = {},
					programmingLanguage: [...programmingLanguage] = [],
					tags: { features = [], topics = [] } = {},
				} = entity;

				// 3. Create flattened filter props object
				filterValues = {
					type: category,
					programmingLanguage: [...programmingLanguage.map(p => p.programmingLanguage)],
					keywords: features,
					domain: topics,
				};
				break;
			}
			case 'project': {
				// 2. Extract all properties used for filtering
				let { categories: { category = '' } = {}, tags: { features = [], topics = [] } = {} } = entity;

				// 3. Create flattened filter props object
				filterValues = {
					type: category,
					keywords: features,
					domain: topics,
				};
				break;
			}
			case 'paper': {
				// 2. Extract all properties used for filtering
				let { tags: { features = [], topics = [] } = {} } = entity;

				// 3. Create flattened filter props object
				filterValues = {
					keywords: features,
					domain: topics,
				};
				break;
			}
			case 'collection': {
				// 2. Extract all properties used for filtering
				let { persons = [], keywords = [] } = entity;

				// 3. Create flattened filter props object
				filterValues = {
					keywords,
					publisher: persons.map(person => person.fullName),
				};
				break;
			}
			case 'course': {
				// 2. Extract all properties used for filtering
				let {
					courseOptions: [...courseOptions] = [],
					provider = '',
					location = '',
					award = '',
					entries: [...entries] = [],
					domains = [],
					keywords = [],
					competencyFramework = '',
					nationalPriority = '',
				} = entity;

				// 3. Create flattened filter props object
				filterValues = {
					startDate: [...courseOptions.map(c => c.startDate)],
					provider,
					location,
					studyMode: [...courseOptions.map(c => c.studyMode)],
					award,
					entryRequirements: [...entries.map(e => e.level)],
					domain: domains,
					keywords,
					competencyFramework,
					nationalPriorityAreas: nationalPriority,
				};
				break;
			}
			case 'dataUseRegister': {
				// 2. Extract all properties used for filtering
				let { keywords = [], organisationName = '', organisationSector = '', publisherDetails = '', fundersAndSponsors = [] } = entity;

				// 3. Create flattened filter props object
				filterValues = {
					keywords,
					organisationName,
					organisationSector,
					publisher: publisherDetails[0].name,
					fundersAndSponsors,
				};
				break;
			}
		}
		// 4. Return filter values
		return filterValues;
	}
}
