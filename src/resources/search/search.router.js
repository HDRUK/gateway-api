import express from 'express';

import { RecordSearchData } from '../search/record.search.model';
import { getObjectResult, getObjectCount, getObjectFilters } from './search.repository';

const router = express.Router();

/**
 * {get} /api/search Search tools
 *
 * Return list of tools, this can be with filters or/and search criteria. This will also include pagination on results.
 * The free word search criteria can be improved on with node modules that specialize with searching i.e. js-search
 */
router.get('/', async (req, res) => {
	var authorID = parseInt(req.query.userID);
	var searchString = req.query.search || ''; //If blank then return all
	//If searchString is applied, format any hyphenated words to enclose them as a phrase
	if (searchString.includes('-') && !searchString.includes('"')) {
		// Matches on any whole word containing a hyphen
		const regex = /(?=\S*[-])([a-zA-Z'-]+)/g;
		// Surround matching words in quotation marks
		searchString = searchString.replace(regex, '"$1"');
	}
	var tab = req.query.tab || '';
	let searchQuery = { $and: [{ activeflag: 'active' }] };

	if (req.query.form) {
		searchQuery = { $and: [{ $or: [{ $and: [{ activeflag: 'review' }, { authors: authorID }] }, { activeflag: 'active' }] }] };
	}

	var searchAll = false;

	if (searchString.length > 0) {
		searchQuery['$and'].push({ $text: { $search: searchString } });

		/* datasetSearchString = '"' + searchString.split(' ').join('""') + '"';
        //The following code is a workaround for the way search works TODO:work with MDC to improve API
        if (searchString.match(/"/)) {
            //user has added quotes so pass string through
            datasetSearchString = searchString;
        } else {
            //no quotes so lets a proximiy search
            datasetSearchString = '"'+searchString+'"~25';
        } */
	} else {
		searchAll = true;
	}

	let allResults = [],
		datasetResults = [],
		toolResults = [],
		projectResults = [],
		paperResults = [],
		personResults = [],
		courseResults = [],
		collectionResults = [];

	if (tab === '') {
		allResults = await Promise.all([
			getObjectResult(
				'dataset',
				searchAll,
				getObjectFilters(searchQuery, req, 'dataset'),
				req.query.datasetIndex || 0,
				req.query.maxResults || 40,
				req.query.datasetSort || ''
			),
			getObjectResult(
				'tool',
				searchAll,
				getObjectFilters(searchQuery, req, 'tool'),
				req.query.toolIndex || 0,
				req.query.maxResults || 40,
				req.query.toolSort || ''
			),
			getObjectResult(
				'project',
				searchAll,
				getObjectFilters(searchQuery, req, 'project'),
				req.query.projectIndex || 0,
				req.query.maxResults || 40,
				req.query.projectSort || ''
			),
			getObjectResult(
				'paper',
				searchAll,
				getObjectFilters(searchQuery, req, 'paper'),
				req.query.paperIndex || 0,
				req.query.maxResults || 40,
				req.query.paperSort || ''
			),
			getObjectResult('person', searchAll, searchQuery, req.query.personIndex || 0, req.query.maxResults || 40, req.query.personSort),
			getObjectResult(
				'course',
				searchAll,
				getObjectFilters(searchQuery, req, 'course'),
				req.query.courseIndex || 0,
				req.query.maxResults || 40,
				'startdate'
			),
			getObjectResult(
				'collection',
				searchAll,
				getObjectFilters(searchQuery, req, 'collection'),
				req.query.collectionIndex || 0,
				req.query.maxResults || 40,
				req.query.collectionSort || ''
			),
		]);
	} else if (tab === 'Datasets') {
		// build filter tree for highlights



		datasetResults = await Promise.all([
			getObjectResult(
				'dataset',
				searchAll,
				getObjectFilters(searchQuery, req, 'dataset'),
				req.query.datasetIndex || 0,
				req.query.maxResults || 40,
				req.query.datasetSort || ''
			),
		]);
	} else if (tab === 'Tools') {
		toolResults = await Promise.all([
			getObjectResult(
				'tool',
				searchAll,
				getObjectFilters(searchQuery, req, 'tool'),
				req.query.toolIndex || 0,
				req.query.maxResults || 40,
				req.query.toolSort || ''
			),
		]);
	} else if (tab === 'Projects') {
		projectResults = await Promise.all([
			getObjectResult(
				'project',
				searchAll,
				getObjectFilters(searchQuery, req, 'project'),
				req.query.projectIndex || 0,
				req.query.maxResults || 40,
				req.query.projectSort || ''
			),
		]);
	} else if (tab === 'Papers') {
		paperResults = await Promise.all([
			getObjectResult(
				'paper',
				searchAll,
				getObjectFilters(searchQuery, req, 'paper'),
				req.query.paperIndex || 0,
				req.query.maxResults || 40,
				req.query.paperSort || ''
			),
		]);
	} else if (tab === 'People') {
		personResults = await Promise.all([
			getObjectResult('person', searchAll, searchQuery, req.query.personIndex || 0, req.query.maxResults || 40, req.query.personSort || ''),
		]);
	} else if (tab === 'Courses') {
		courseResults = await Promise.all([
			getObjectResult(
				'course',
				searchAll,
				getObjectFilters(searchQuery, req, 'course'),
				req.query.courseIndex || 0,
				req.query.maxResults || 40,
				'startdate'
			),
		]);
	} else if (tab === 'Collections') {
		collectionResults = await Promise.all([
			getObjectResult(
				'collection',
				searchAll,
				getObjectFilters(searchQuery, req, 'collection'),
				req.query.collectionIndex || 0,
				req.query.maxResults || 40,
				req.query.collectionSort || ''
			),
		]);
	}

	var summaryCounts = await Promise.all([
		getObjectCount('dataset', searchAll, getObjectFilters(searchQuery, req, 'dataset')),
		getObjectCount('tool', searchAll, getObjectFilters(searchQuery, req, 'tool')),
		getObjectCount('project', searchAll, getObjectFilters(searchQuery, req, 'project')),
		getObjectCount('paper', searchAll, getObjectFilters(searchQuery, req, 'paper')),
		getObjectCount('person', searchAll, searchQuery),
		getObjectCount('course', searchAll, getObjectFilters(searchQuery, req, 'course')),
		getObjectCount('collection', searchAll, getObjectFilters(searchQuery, req, 'collection')),
	]);

	var summary = {
		datasets: summaryCounts[0][0] !== undefined ? summaryCounts[0][0].count : 0,
		tools: summaryCounts[1][0] !== undefined ? summaryCounts[1][0].count : 0,
		projects: summaryCounts[2][0] !== undefined ? summaryCounts[2][0].count : 0,
		papers: summaryCounts[3][0] !== undefined ? summaryCounts[3][0].count : 0,
		persons: summaryCounts[4][0] !== undefined ? summaryCounts[4][0].count : 0,
		courses: summaryCounts[5][0] !== undefined ? summaryCounts[5][0].count : 0,
		collections: summaryCounts[6][0] !== undefined ? summaryCounts[6][0].count : 0,
	};

	let recordSearchData = new RecordSearchData();
	recordSearchData.searched = searchString;
	recordSearchData.returned.dataset = summaryCounts[0][0] !== undefined ? summaryCounts[0][0].count : 0;
	recordSearchData.returned.tool = summaryCounts[1][0] !== undefined ? summaryCounts[1][0].count : 0;
	recordSearchData.returned.project = summaryCounts[2][0] !== undefined ? summaryCounts[2][0].count : 0;
	recordSearchData.returned.paper = summaryCounts[3][0] !== undefined ? summaryCounts[3][0].count : 0;
	recordSearchData.returned.person = summaryCounts[4][0] !== undefined ? summaryCounts[4][0].count : 0;
	recordSearchData.returned.course = summaryCounts[5][0] !== undefined ? summaryCounts[5][0].count : 0;
	recordSearchData.returned.collection = summaryCounts[6][0] !== undefined ? summaryCounts[6][0].count : 0;
	recordSearchData.datesearched = Date.now();
	recordSearchData.save(err => {});

	if (tab === '') {
		return res.json({
			success: true,
			datasetResults: allResults[0],
			toolResults: allResults[1],
			projectResults: allResults[2],
			paperResults: allResults[3],
			personResults: allResults[4],
			courseResults: allResults[5],
			collectionResults: allResults[6],
			summary: summary,
		});
	}
	return res.json({
		success: true,
		datasetResults: datasetResults[0],
		toolResults: toolResults[0],
		projectResults: projectResults[0],
		paperResults: paperResults[0],
		personResults: personResults[0],
		courseResults: courseResults[0],
		collectionResults: collectionResults[0],
		summary: summary,
	});
});

module.exports = router;
