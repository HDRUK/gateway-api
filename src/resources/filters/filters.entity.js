import Entity from '../base/entity';
import * as mapper from './filters.mapper';
import { isEmpty, isNil } from 'lodash';
import { findNodeInTree, formatFilterOptions, updateTree } from './utils/filters.util';
import searchUtil from '../search/util/search.util';

export default class FiltersClass extends Entity {
	constructor(obj) {
		super();
		Object.assign(this, obj);
	}

	mapDto() {
		if (!this.id) {
			process.stdout.write(`Failed to load filters`);
			return;
		}

		// 1. the data tree we want to update
		let filters = mapper[`${this.id}Filters`];
		// 2. this.keys reperesents the filters data in db for the id
		const filterKeys = Object.keys(this.keys);
		// 3. avoid expensive call if no data present
		if(!isEmpty(this.keys)) {
			// 4. loop over filterKeys
			for (const filterKey of filterKeys) {
				let newFilterOptions = [];
				// 5. track new variable for filter values from our db
				let filterValues = this.keys[filterKey];
				// 6. check if filterKey exists in our tree, return {} or undefined
				let nodeItem = findNodeInTree(filters, filterKey);
				// 7. if exists find and update tree
				if (!isNil(nodeItem) && filterValues.length) {
					// 8. build the new options for the filters within tree
				 	newFilterOptions = formatFilterOptions(filterValues);
					// 9. insert new options into tree
					filters = updateTree(filters, filterKey, newFilterOptions);
					// update for spatial filter list
					if (filterKey === 'spatial' ) {
						filters = updateTree(filters, filterKey, searchUtil.arrayToTree(this.keys[filterKey]), 'filtersv2');
					}
				}
			}
		}
		return filters;
	}
}


