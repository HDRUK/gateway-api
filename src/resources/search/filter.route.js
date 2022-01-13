import express from 'express';
import { getObjectFilters, getFilter } from './search.repository';
import { filtersService } from '../filters/dependency';
import { isEqual, isEmpty } from 'lodash';

const router = express.Router();

const typeMapper = {
	Datasets: 'dataset',
	Tools: 'tool',
	Projects: 'project',
	Papers: 'paper',
	People: 'person',
	Courses: 'course',
	Collections: 'collection',
	Datauses: 'dataUseRegister',
};

// @route   GET api/v1/search/filter
// @desc    GET Get filters
// @access  Public
router.get('/', async (req, res) => {
	let searchString = req.query.search || ''; //If blank then return all
	let tab = req.query.tab || ''; //If blank then return all

	const type = !isEmpty(tab) && typeof tab === 'string' ? typeMapper[`${tab}`] : '';

	let defaultQuery = { $and: [{ activeflag: 'active' }] };
	if (type === 'collection') {
		defaultQuery['$and'].push({ publicflag: true });
	} else if (type === 'course') {
		defaultQuery['$and'].push({
			$or: [{ 'courseOptions.startDate': { $gte: new Date(Date.now()) } }, { 'courseOptions.flexibleDates': true }],
		});
	}

	if (searchString.length > 0) defaultQuery['$and'].push({ $text: { $search: searchString } });
	const filterQuery = getObjectFilters(defaultQuery, req, type);
	const useCachedFilters = isEqual(defaultQuery, filterQuery) && searchString.length === 0;

	const filters = await filtersService.buildFilters(type, filterQuery, useCachedFilters);
	return res.json({
		success: true,
		filters,
	});
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
