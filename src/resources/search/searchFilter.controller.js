import { isEqual, isEmpty, cloneDeep } from 'lodash';

import searchUtil from './util/search.util';
import { getObjectFilters } from './search.repository';
import constantsUtil from '../utilities/constants.util';
import {
	datasetFilters,
	toolFilters,
	projectFilters,
	paperFilters,
	collectionFilters,
	courseFilters,
	dataUseRegisterFilters,
} from '../filters/filters.mapper';
import { findNodeInTree } from '../filters/utils/filters.util';

export default class SearchFilterController {
	_filtersService;

	constructor(filtersService) {
		this._filtersService = filtersService;
	}

	/** Get the relevant filters for a given query */
	getSearchFilters = async (req, res) => {
		const query = req.query.search || '';
		const tab = req.query.tab || '';

		const dataType = !isEmpty(tab) && typeof tab === 'string' ? constantsUtil.searchDataTypes[tab] : '';

		let baseQuery = { $and: [{ activeflag: 'active' }] };

		if (dataType === 'collection') {
			baseQuery['$and'].push({ publicflag: true });
		}

		if (dataType === 'course') {
			baseQuery['$and'].push({
				$or: [{ 'courseOptions.startDate': { $gte: new Date(Date.now()) } }, { 'courseOptions.flexibleDates': true }],
			});
		}

		if (query.length > 0) {
			baseQuery['$and'].push({ $text: { $search: query } });
		}

		try {
			const filterQuery = getObjectFilters(baseQuery, req.query, dataType);
			const useCache = isEqual(baseQuery, filterQuery) && query.length === 0;

			let filters = await this._filtersService.buildFilters(dataType, filterQuery, useCache);

			if (dataType === 'dataset') {
				// enable new filter behaviour for datasets only - if statement can be removed to apply to all
				filters = await this._modifySelectedFilters(req.query, filters, baseQuery, dataType);
			}

			filters['spatialv2'] = searchUtil.arrayToTree(filters['spatial'] || []);

			return res.status(200).json({
				success: true,
				filters,
			});
		} catch (err) {
			process.stdout.write(`${err.message}\n`);
			return res.status(500).json({
				success: false,
				message: err.message,
			});
		}
	};

	/** Update options for selected filters by removing themselves from the query. */
	_modifySelectedFilters = async (selected, filters, baseQuery, dataType) => {
		const mapperOptions = {
			dataset: datasetFilters,
			tool: toolFilters,
			project: projectFilters,
			paper: paperFilters,
			collection: collectionFilters,
			course: courseFilters,
			dataUseRegister: dataUseRegisterFilters,
		};

		await Promise.all(
			Object.keys(selected).map(async filter => {
				const { key } = (await findNodeInTree(mapperOptions[dataType], filter)) || '';

				if (key) {
					let queryParams = cloneDeep(selected);

					delete queryParams[filter];

					const filterQuery = await getObjectFilters(baseQuery, queryParams, dataType);
					const updatedFilters = await this._filtersService.buildFilters(dataType, filterQuery, false);

					filters[key] = updatedFilters[key];
				}
			})
		);

		return filters;
	};
}
