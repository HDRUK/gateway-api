import express from 'express';

import { getObjectFilters, getFilter } from './search.repository';
import SearchFilterController from './searchFilter.controller';
import { filtersService } from '../filters/dependency';

const router = express.Router();

const searchFilterController = new SearchFilterController(filtersService);

// @route   GET api/v1/search/filter
// @desc    GET Get filters
// @access  Public
router.get('/', searchFilterController.getSearchFilters);

// @route   GET api/v1/search/filter/topic/:type
// @desc    GET Get list of topics by entity type
// @access  Public
router.get('/topic/:type', async (req, res) => {
	await getFilter(
		'',
		req.params.type,
		'tags.topics',
		true,
		getObjectFilters({ $and: [{ activeflag: 'active' }] }, req.query, req.params.type)
	)
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
	await getFilter(
		'',
		req.params.type,
		'tags.features',
		true,
		getObjectFilters({ $and: [{ activeflag: 'active' }] }, req.query, req.params.type)
	)
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
		getObjectFilters({ $and: [{ activeflag: 'active' }] }, req.query, req.params.type)
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
		getObjectFilters({ $and: [{ activeflag: 'active' }] }, req.query, req.params.type)
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
	await getFilter('', req.params.type, 'license', false, getObjectFilters({ $and: [{ activeflag: 'active' }] }, req.query, req.params.type))
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
	await getFilter(
		'',
		req.params.type,
		'organisation',
		false,
		getObjectFilters({ $and: [{ activeflag: 'active' }] }, req.query, req.params.type)
	)
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
	await getFilter('', req.params.type, 'domains', true, getObjectFilters({ $and: [{ activeflag: 'active' }] }, req.query, req.params.type))
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
	await getFilter('', req.params.type, 'keywords', true, getObjectFilters({ $and: [{ activeflag: 'active' }] }, req.query, req.params.type))
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
	await getFilter('', req.params.type, 'award', true, getObjectFilters({ $and: [{ activeflag: 'active' }] }, req.query, req.params.type))
		.then(data => {
			return res.json({ success: true, data });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

module.exports = router;
