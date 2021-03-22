import express from 'express';
import { getObjectFilters, getFilter } from './search.repository';
import { filtersService } from '../filters/dependency';
import { isEqual } from 'lodash';

const router = express.Router();

// @route   GET api/v1/search/filter
// @desc    GET Get filters
// @access  Public
router.get('/', async (req, res) => {
	let searchString = req.query.search || ''; //If blank then return all
	let tab = req.query.tab || ''; //If blank then return all
	if (tab === '') {
		let searchQuery = { $and: [{ activeflag: 'active' }] };
		if (searchString.length > 0) searchQuery['$and'].push({ $text: { $search: searchString } });

		await Promise.all([
			getFilter(searchString, 'tool', 'tags.topic', true, getObjectFilters(searchQuery, req, 'tool')),
			getFilter(searchString, 'tool', 'tags.features', true, getObjectFilters(searchQuery, req, 'tool')),
			getFilter(searchString, 'tool', 'programmingLanguage.programmingLanguage', true, getObjectFilters(searchQuery, req, 'tool')),
			getFilter(searchString, 'tool', 'categories.category', false, getObjectFilters(searchQuery, req, 'tool')),

			getFilter(searchString, 'project', 'tags.topics', true, getObjectFilters(searchQuery, req, 'project')),
			getFilter(searchString, 'project', 'tags.features', true, getObjectFilters(searchQuery, req, 'project')),
			getFilter(searchString, 'project', 'categories.category', false, getObjectFilters(searchQuery, req, 'project')),

			getFilter(searchString, 'paper', 'tags.topics', true, getObjectFilters(searchQuery, req, 'project')),
			getFilter(searchString, 'paper', 'tags.features', true, getObjectFilters(searchQuery, req, 'project')),
		]).then(values => {
			return res.json({
				success: true,
				allFilters: {
					toolTopicFilter: values[0][0],
					toolFeatureFilter: values[1][0],
					toolLanguageFilter: values[2][0],
					toolCategoryFilter: values[3][0],

					projectTopicFilter: values[4][0],
					projectFeatureFilter: values[5][0],
					projectCategoryFilter: values[6][0],

					paperTopicFilter: values[7][0],
					paperFeatureFilter: values[8][0],
				},
				filterOptions: {
					toolTopicsFilterOptions: values[0][1],
					featuresFilterOptions: values[1][1],
					programmingLanguageFilterOptions: values[2][1],
					toolCategoriesFilterOptions: values[3][1],

					projectTopicsFilterOptions: values[4][1],
					projectFeaturesFilterOptions: values[5][1],
					projectCategoriesFilterOptions: values[6][1],

					paperTopicsFilterOptions: values[7][1],
					paperFeaturesFilterOptions: values[8][1],
				},
			});
		});
	} else if (tab === 'Datasets') {
		const type = 'dataset';

		let defaultQuery = { $and: [{ activeflag: 'active', type }] };
		if (searchString.length > 0) defaultQuery['$and'].push({ $text: { $search: searchString } });
		const filterQuery = getObjectFilters(defaultQuery, req, type);
		const useCachedFilters = isEqual(defaultQuery, filterQuery) && searchString.length === 0;

		const filters = await filtersService.buildFilters(type, filterQuery, useCachedFilters);
		return res.json({
			success: true,
			filters
		});
	//const matchQuery = queryObject[0][`$match`];
	//const useCachedFilters = matchQuery[`$and`] && matchQuery[`$and`].length === 2;

	// Get paged results based on query params
	// const [searchResults, filters] = await Promise.all(
	// 	collection.aggregate(queryObject).skip(parseInt(startIndex)).limit(parseInt(maxResults)),
	// 	filtersService.buildFilters(type, matchQuery, useCachedFilters)
	// );


		// await Promise.all([
		// 	// getFilter(searchString, 'dataset', 'license', false, activeFiltersQuery),
		// 	// getFilter(searchString, 'dataset', 'datasetfields.physicalSampleAvailability', true, activeFiltersQuery),
		// 	// getFilter(searchString, 'dataset', 'tags.features', true, activeFiltersQuery),
		// 	// getFilter(searchString, 'dataset', 'datasetfields.publisher', false, activeFiltersQuery),
		// 	// getFilter(searchString, 'dataset', 'datasetfields.ageBand', true, activeFiltersQuery),
		// 	// getFilter(searchString, 'dataset', 'datasetfields.geographicCoverage', true, activeFiltersQuery),
		// 	// getFilter(searchString, 'dataset', 'datasetfields.phenotypes', true, activeFiltersQuery),
		// ]).then(values => {
		// 	return res.json({
		// 		success: true,
		// 		allFilters: {
		// 			// licenseFilter: values[0][0],
		// 			// sampleFilter: values[1][0],
		// 			// datasetFeatureFilter: values[2][0],
		// 			// publisherFilter: values[3][0],
		// 			// ageBandFilter: values[4][0],
		// 			// geographicCoverageFilter: values[5][0],
		// 			// phenotypesFilter: values[6][0],
		// 		},
		// 		filterOptions: {
		// 			// licenseFilterOptions: values[0][1],
		// 			// sampleFilterOptions: values[1][1],
		// 			// datasetFeaturesFilterOptions: values[2][1],
		// 			// publisherFilterOptions: values[3][1],
		// 			// ageBandFilterOptions: values[4][1],
		// 			// geographicCoverageFilterOptions: values[5][1],
		// 			// phenotypesOptions: values[6][1],
		// 		},
		// 	});
		// });
	} else if (tab === 'Tools') {
		let searchQuery = { $and: [{ activeflag: 'active' }] };
		if (searchString.length > 0) searchQuery['$and'].push({ $text: { $search: searchString } });
		var activeFiltersQuery = getObjectFilters(searchQuery, req, 'tool');

		await Promise.all([
			getFilter(searchString, 'tool', 'tags.topics', true, activeFiltersQuery),
			getFilter(searchString, 'tool', 'tags.features', true, activeFiltersQuery),
			getFilter(searchString, 'tool', 'programmingLanguage.programmingLanguage', true, activeFiltersQuery),
			getFilter(searchString, 'tool', 'categories.category', false, activeFiltersQuery),
		]).then(values => {
			return res.json({
				success: true,
				allFilters: {
					toolTopicFilter: values[0][0],
					toolFeatureFilter: values[1][0],
					toolLanguageFilter: values[2][0],
					toolCategoryFilter: values[3][0],
				},
				filterOptions: {
					toolTopicsFilterOptions: values[0][1],
					featuresFilterOptions: values[1][1],
					programmingLanguageFilterOptions: values[2][1],
					toolCategoriesFilterOptions: values[3][1],
				},
			});
		});
	} else if (tab === 'Projects') {
		let searchQuery = { $and: [{ activeflag: 'active' }] };
		if (searchString.length > 0) searchQuery['$and'].push({ $text: { $search: searchString } });
		var activeFiltersQuery = getObjectFilters(searchQuery, req, 'project');

		await Promise.all([
			getFilter(searchString, 'project', 'tags.topics', true, activeFiltersQuery),
			getFilter(searchString, 'project', 'tags.features', true, activeFiltersQuery),
			getFilter(searchString, 'project', 'categories.category', false, activeFiltersQuery),
		]).then(values => {
			return res.json({
				success: true,
				allFilters: {
					projectTopicFilter: values[0][0],
					projectFeatureFilter: values[1][0],
					projectCategoryFilter: values[2][0],
				},
				filterOptions: {
					projectTopicsFilterOptions: values[0][1],
					projectFeaturesFilterOptions: values[1][1],
					projectCategoriesFilterOptions: values[2][1],
				},
			});
		});
	} else if (tab === 'Papers') {
		let searchQuery = { $and: [{ activeflag: 'active' }] };
		if (searchString.length > 0) searchQuery['$and'].push({ $text: { $search: searchString } });
		var activeFiltersQuery = getObjectFilters(searchQuery, req, 'paper');
		await Promise.all([
			getFilter(searchString, 'paper', 'tags.topics', true, activeFiltersQuery),
			getFilter(searchString, 'paper', 'tags.features', true, activeFiltersQuery),
		]).then(values => {
			return res.json({
				success: true,
				allFilters: {
					paperTopicFilter: values[0][0],
					paperFeatureFilter: values[1][0],
				},
				filterOptions: {
					paperTopicsFilterOptions: values[0][1],
					paperFeaturesFilterOptions: values[1][1],
				},
			});
		});
	} else if (tab === 'Courses') {
		let searchQuery = { $and: [{ activeflag: 'active' }] };
		if (searchString.length > 0) searchQuery['$and'].push({ $text: { $search: searchString } });
		var activeFiltersQuery = getObjectFilters(searchQuery, req, 'course');

		await Promise.all([
			getFilter(searchString, 'course', 'courseOptions.startDate', true, activeFiltersQuery),
			getFilter(searchString, 'course', 'provider', true, activeFiltersQuery),
			getFilter(searchString, 'course', 'location', true, activeFiltersQuery),
			getFilter(searchString, 'course', 'courseOptions.studyMode', true, activeFiltersQuery),
			getFilter(searchString, 'course', 'award', true, activeFiltersQuery),
			getFilter(searchString, 'course', 'entries.level', true, activeFiltersQuery),
			getFilter(searchString, 'course', 'domains', true, activeFiltersQuery),
			getFilter(searchString, 'course', 'keywords', true, activeFiltersQuery),
			getFilter(searchString, 'course', 'competencyFramework', true, activeFiltersQuery),
			getFilter(searchString, 'course', 'nationalPriority', true, activeFiltersQuery),
		]).then(values => {
			return res.json({
				success: true,
				allFilters: {
					courseStartDatesFilter: values[0][0],
					courseProviderFilter: values[1][0],
					courseLocationFilter: values[2][0],
					courseStudyModeFilter: values[3][0],
					courseAwardFilter: values[4][0],
					courseEntryLevelFilter: values[5][0],
					courseDomainsFilter: values[6][0],
					courseKeywordsFilter: values[7][0],
					courseFrameworkFilter: values[8][0],
					coursePriorityFilter: values[9][0],
				},
				filterOptions: {
					courseStartDatesFilterOptions: values[0][1],
					courseProviderFilterOptions: values[1][1],
					courseLocationFilterOptions: values[2][1],
					courseStudyModeFilterOptions: values[3][1],
					courseAwardFilterOptions: values[4][1],
					courseEntryLevelFilterOptions: values[5][1],
					courseDomainsFilterOptions: values[6][1],
					courseKeywordsFilterOptions: values[7][1],
					courseFrameworkFilterOptions: values[8][1],
					coursePriorityFilterOptions: values[9][1],
				},
			});
		});
	} else if (tab === 'Collections') {
		let searchQuery = { $and: [{ activeflag: 'active' }, { publicflag: true }] };
		if (searchString.length > 0) searchQuery['$and'].push({ $text: { $search: searchString } });
		var activeFiltersQuery = getObjectFilters(searchQuery, req, 'collection');

		await Promise.all([
			getFilter(searchString, 'collection', 'keywords', true, activeFiltersQuery),
			getFilter(searchString, 'collection', 'authors', true, activeFiltersQuery),
		]).then(values => {
			return res.json({
				success: true,
				allFilters: {
					collectionKeywordFilter: values[0][0],
					collectionPublisherFilter: values[1][0],
				},
				filterOptions: {
					collectionKeywordsFilterOptions: values[0][1],
					collectionPublisherFilterOptions: values[1][1],
				},
			});
		});
	}
});

// @route   GET api/v1/search/filter/topic/:type
// @desc    GET Get list of topics by entity type
// @access  Public
router.get('/topic/:type', async (req, res) => {
	await getFilter('', req.params.type, 'tags.topics', true, getObjectFilters({ $and: [{ activeflag: 'active' }] }, req, req.params.type))
		.then(data => {
			return res.json({ success: true, data });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

// @route   GET api/v1/search/filter/feature/:type
// @desc    GET Get list of features by entity type
// @access  Public
router.get('/feature/:type', async (req, res) => {
	await getFilter('', req.params.type, 'tags.features', true, getObjectFilters({ $and: [{ activeflag: 'active' }] }, req, req.params.type))
		.then(data => {
			return res.json({ success: true, data });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

// @route   GET api/v1/search/filter/language/:type
// @desc    GET Get list of languages by entity type
// @access  Public
router.get('/language/:type', async (req, res) => {
	await getFilter(
		'',
		req.params.type,
		'programmingLanguage.programmingLanguage',
		true,
		getObjectFilters({ $and: [{ activeflag: 'active' }] }, req, req.params.type)
	)
		.then(data => {
			return res.json({ success: true, data });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

// @route   GET api/v1/search/filter/category/:type
// @desc    GET Get list of categories by entity type
// @access  Public
router.get('/category/:type', async (req, res) => {
	await getFilter(
		'',
		req.params.type,
		'categories.category',
		false,
		getObjectFilters({ $and: [{ activeflag: 'active' }] }, req, req.params.type)
	)
		.then(data => {
			return res.json({ success: true, data });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

// @route   GET api/v1/search/filter/license/:type
// @desc    GET Get list of licenses by entity type
// @access  Public
router.get('/license/:type', async (req, res) => {
	await getFilter('', req.params.type, 'license', false, getObjectFilters({ $and: [{ activeflag: 'active' }] }, req, req.params.type))
		.then(data => {
			return res.json({ success: true, data });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

// @route   GET api/v1/search/filter/organisation/:type
// @desc    GET Get list of organisations by entity type
// @access  Public
router.get('/organisation/:type', async (req, res) => {
	await getFilter('', req.params.type, 'organisation', false, getObjectFilters({ $and: [{ activeflag: 'active' }] }, req, req.params.type))
		.then(data => {
			return res.json({ success: true, data });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

// @route   GET api/v1/search/filter/domains/:type
// @desc    GET Get list of features by entity type
// @access  Public
router.get('/domains/:type', async (req, res) => {
	await getFilter('', req.params.type, 'domains', true, getObjectFilters({ $and: [{ activeflag: 'active' }] }, req, req.params.type))
		.then(data => {
			return res.json({ success: true, data });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

// @route   GET api/v1/search/filter/keywords/:type
// @desc    GET Get list of features by entity type
// @access  Public
router.get('/keywords/:type', async (req, res) => {
	await getFilter('', req.params.type, 'keywords', true, getObjectFilters({ $and: [{ activeflag: 'active' }] }, req, req.params.type))
		.then(data => {
			return res.json({ success: true, data });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

// @route   GET api/v1/search/filter/awards/:type
// @desc    GET Get list of features by entity type
// @access  Public
router.get('/awards/:type', async (req, res) => {
	await getFilter('', req.params.type, 'award', true, getObjectFilters({ $and: [{ activeflag: 'active' }] }, req, req.params.type))
		.then(data => {
			return res.json({ success: true, data });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

module.exports = router;
