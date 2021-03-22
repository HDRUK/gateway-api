import { Data } from '../tool/data.model';
import { Course } from '../course/course.model';
import { Collections } from '../collections/collections.model';
import { findNodeInTree } from '../filters/utils/filters.util';
import { datasetFilters } from '../filters/filters.mapper';
import _ from 'lodash';
import moment from 'moment';
import helperUtil from '../utilities/helper.util';

export async function getObjectResult(type, searchAll, searchQuery, startIndex, maxResults, sort) {

	let collection = Data;
	if (type === 'course') {
		collection = Course;
	} else if (type === 'collection') {
		collection = Collections;
	}
	// ie copy deep object
	let newSearchQuery = _.cloneDeep(searchQuery);
	if (type !== 'collection') {
		newSearchQuery['$and'].push({ type: type });
	} else {
		newSearchQuery['$and'].push({ publicflag: true });
	}

	if (type === 'course') {
		newSearchQuery['$and'].forEach(x => {
			if (x.$or) {
				x.$or.forEach(y => {
					if (y['courseOptions.startDate']) y['courseOptions.startDate'] = new Date(y['courseOptions.startDate']);
				});
			}
		});
		newSearchQuery['$and'].push({
			$or: [{ 'courseOptions.startDate': { $gte: new Date(Date.now()) } }, { 'courseOptions.flexibleDates': true }],
		});
	}

	let queryObject;
	if (type === 'course') {
		queryObject = [
			{ $match: newSearchQuery },
			{ $unwind: '$courseOptions' },
			{
				$project: {
					_id: 0,
					id: 1,
					title: 1,
					provider: 1,
					type: 1,
					description: 1,
					'courseOptions.flexibleDates': 1,
					'courseOptions.startDate': 1,
					'courseOptions.studyMode': 1,
					domains: 1,
					award: 1,
				},
			},
		];
	} else if (type === 'collection') {
		queryObject = [{ $match: newSearchQuery }, { $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } }];
	} else {
		queryObject = [
			{ $match: newSearchQuery },
			{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
			{
				$project: {
					_id: 0,
					id: 1,
					name: 1,
					type: 1,
					description: 1,
					bio: {
						$cond: {
							if: { $eq: [false, '$showBio'] },
							then: '$$REMOVE',
							else: '$bio',
						},
					},
					'categories.category': 1,
					'categories.programmingLanguage': 1,
					'programmingLanguage.programmingLanguage': 1,
					'programmingLanguage.version': 1,
					license: 1,
					'tags.features': 1,
					'tags.topics': 1,
					firstname: 1,
					lastname: 1,
					datasetid: 1,
					pid: 1,
					'datasetfields.publisher': 1,
					'datasetfields.geographicCoverage': 1,
					'datasetfields.physicalSampleAvailability': 1,
					'datasetfields.abstract': 1,
					'datasetfields.ageBand': 1,
					'datasetfields.phenotypes': 1,
					'datasetv2.summary.publisher.name': 1,
					'datasetv2.summary.publisher.logo': 1,
					'datasetv2.summary.publisher.memberOf': 1,

					'persons.id': 1,
					'persons.firstname': 1,
					'persons.lastname': 1,

					activeflag: 1,
					counter: 1,
					'datasetfields.metadataquality.quality_score': 1,
				},
			},
		];
	}

	if (sort === '' || sort === 'relevance') {
		if (type === 'person') {
			if (searchAll) queryObject.push({ $sort: { lastname: 1 } });
			else queryObject.push({ $sort: { score: { $meta: 'textScore' } } });
		} else {
			if (searchAll) queryObject.push({ $sort: { name: 1 } });
			else queryObject.push({ $sort: { score: { $meta: 'textScore' } } });
		}
	} else if (sort === 'popularity') {
		if (type === 'person') {
			if (searchAll) queryObject.push({ $sort: { counter: -1, lastname: 1 } });
			else queryObject.push({ $sort: { counter: -1, score: { $meta: 'textScore' } } });
		} else {
			if (searchAll) queryObject.push({ $sort: { counter: -1, name: 1 } });
			else queryObject.push({ $sort: { counter: -1, score: { $meta: 'textScore' } } });
		}
	} else if (sort === 'metadata') {
		if (searchAll) queryObject.push({ $sort: { 'datasetfields.metadataquality.quality_score': -1, name: 1 } });
		else queryObject.push({ $sort: { 'datasetfields.metadataquality.quality_score': -1, score: { $meta: 'textScore' } } });
	} else if (sort === 'startdate') {
		if (searchAll) queryObject.push({ $sort: { 'courseOptions.startDate': 1 } });
		else queryObject.push({ $sort: { 'courseOptions.startDate': 1, score: { $meta: 'textScore' } } });
	}
	// Get paged results based on query params
	const searchResults = await collection.aggregate(queryObject).skip(parseInt(startIndex)).limit(parseInt(maxResults));
	// Return data
	return { data: searchResults };
}

export function getObjectCount(type, searchAll, searchQuery) {
	let collection = Data;
	if (type === 'course') {
		collection = Course;
	} else if (type === 'collection') {
		collection = Collections;
	}
	let newSearchQuery = JSON.parse(JSON.stringify(searchQuery));
	if (type !== 'collection') {
		newSearchQuery['$and'].push({ type: type });
	} else {
		newSearchQuery['$and'].push({ publicflag: true });
	}
	if (type === 'course') {
		newSearchQuery['$and'].forEach(x => {
			if (x.$or) {
				x.$or.forEach(y => {
					if (y['courseOptions.startDate']) y['courseOptions.startDate'] = new Date(y['courseOptions.startDate']);
				});
			}
		});
		newSearchQuery['$and'].push({
			$or: [{ 'courseOptions.startDate': { $gte: new Date(Date.now()) } }, { 'courseOptions.flexibleDates': true }],
		});
	}

	var q = '';
	if (type === 'course') {
		if (searchAll) {
			q = collection.aggregate([
				{ $match: newSearchQuery },
				{ $unwind: '$courseOptions' },
				{
					$group: {
						_id: {},
						count: {
							$sum: 1,
						},
					},
				},
				{
					$project: {
						count: '$count',
						_id: 0,
					},
				},
			]);
		} else {
			q = collection
				.aggregate([
					{ $match: newSearchQuery },
					{ $unwind: '$courseOptions' },
					{
						$group: {
							_id: {},
							count: {
								$sum: 1,
							},
						},
					},
					{
						$project: {
							count: '$count',
							_id: 0,
						},
					},
				])
				.sort({ score: { $meta: 'textScore' } });
		}
	} else if (type === 'collection') {
		if (searchAll) {
			q = collection.aggregate([
				{ $match: newSearchQuery },
				{
					$group: {
						_id: {},
						count: {
							$sum: 1,
						},
					},
				},
				{
					$project: {
						count: '$count',
						_id: 0,
					},
				},
			]);
		} else {
			q = collection
				.aggregate([
					{ $match: newSearchQuery },
					{
						$group: {
							_id: {},
							count: {
								$sum: 1,
							},
						},
					},
					{
						$project: {
							count: '$count',
							_id: 0,
						},
					},
				])
				.sort({ score: { $meta: 'textScore' } });
		}
	} else {
		if (searchAll) {
			q = collection.aggregate([
				{ $match: newSearchQuery },
				{
					$group: {
						_id: {},
						count: {
							$sum: 1,
						},
					},
				},
				{
					$project: {
						count: '$count',
						_id: 0,
					},
				},
			]);
		} else {
			q = collection
				.aggregate([
					{ $match: newSearchQuery },
					{
						$group: {
							_id: {},
							count: {
								$sum: 1,
							},
						},
					},
					{
						$project: {
							count: '$count',
							_id: 0,
						},
					},
				])
				.sort({ score: { $meta: 'textScore' } });
		}
	}

	return new Promise((resolve, reject) => {
		q.exec((err, data) => {
			if (typeof data === 'undefined') resolve([]);
			else resolve(data);
		});
	});
}

export function getObjectFilters(searchQueryStart, req, type) {
	let searchQuery = JSON.parse(JSON.stringify(searchQueryStart));

	let {
		toolprogrammingLanguage = '',
		toolcategories = '',
		toolfeatures = '',
		tooltopics = '',
		projectcategories = '',
		projectfeatures = '',
		projecttopics = '',
		paperfeatures = '',
		papertopics = '',
		coursestartdates = '',
		coursedomains = '',
		coursekeywords = '',
		courseprovider = '',
		courselocation = '',
		coursestudymode = '',
		courseaward = '',
		courseentrylevel = '',
		courseframework = '',
		coursepriority = '',
		collectionpublisher = '',
		collectionkeywords = '',
	} = req.query;

	if (type === 'dataset') {
		// iterate over query string keys
		for (const key of Object.keys(req.query)) {
			try {
				const filterValues = req.query[key].split('::');
				// check mapper for query type
				const filterNode = findNodeInTree(datasetFilters, key);
				if (filterNode) {
					// switch on query type	and build up query object
					const { type = '', dataPath = '', matchField = '' } = filterNode;
					switch (type) {
						case 'contains':
							// use regex to match without case sensitivity
							searchQuery['$and'].push({
								$or: filterValues.map(value => {
									return { [`${dataPath}`]: { $regex: helperUtil.escapeRegexChars(value), $options: 'i' } };
								}),
							});
							break;
						case 'elementMatch':
							// use regex to match objects within an array without case sensitivity
							searchQuery['$and'].push({
								[`${dataPath}`]: {
									$elemMatch: {
										$or: filterValues.map(value => {
											return { [`${matchField}`]: { $regex: value, $options: 'i' } };
										}),
									},
								},
							});
							break;
						case 'boolean':
							searchQuery['$and'].push({ [`${dataPath}`]: true });
							break;
						default:
							break;
					}
				}
			} catch (err) {
				console.error(err.message);
			}
		}
	}

	if (type === 'tool') {
		if (toolprogrammingLanguage.length > 0) {
			let filterTermArray = [];
			toolprogrammingLanguage.split('::').forEach(filterTerm => {
				filterTermArray.push({ 'programmingLanguage.programmingLanguage': filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}

		if (toolcategories.length > 0) {
			let filterTermArray = [];
			toolcategories.split('::').forEach(filterTerm => {
				filterTermArray.push({ 'categories.category': filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}

		if (toolfeatures.length > 0) {
			let filterTermArray = [];
			toolfeatures.split('::').forEach(filterTerm => {
				filterTermArray.push({ 'tags.features': filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}

		if (tooltopics.length > 0) {
			let filterTermArray = [];
			tooltopics.split('::').forEach(filterTerm => {
				filterTermArray.push({ 'tags.topics': filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}
	} else if (type === 'project') {
		if (projectcategories.length > 0) {
			let filterTermArray = [];
			projectcategories.split('::').forEach(filterTerm => {
				filterTermArray.push({ 'categories.category': filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}

		if (projectfeatures.length > 0) {
			let filterTermArray = [];
			projectfeatures.split('::').forEach(filterTerm => {
				filterTermArray.push({ 'tags.features': filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}

		if (projecttopics.length > 0) {
			let filterTermArray = [];
			projecttopics.split('::').forEach(filterTerm => {
				filterTermArray.push({ 'tags.topics': filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}
	} else if (type === 'paper') {
		if (paperfeatures.length > 0) {
			let filterTermArray = [];
			paperfeatures.split('::').forEach(filterTerm => {
				filterTermArray.push({ 'tags.features': filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}

		if (papertopics.length > 0) {
			let filterTermArray = [];
			papertopics.split('::').forEach(filterTerm => {
				filterTermArray.push({ 'tags.topics': filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}
	} else if (type === 'course') {
		if (coursestartdates.length > 0) {
			let filterTermArray = [];
			coursestartdates.split('::').forEach(filterTerm => {
				filterTermArray.push({ 'courseOptions.startDate': filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}

		if (courseprovider.length > 0) {
			let filterTermArray = [];
			courseprovider.split('::').forEach(filterTerm => {
				filterTermArray.push({ provider: filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}

		if (courselocation.length > 0) {
			let filterTermArray = [];
			courselocation.split('::').forEach(filterTerm => {
				filterTermArray.push({ location: filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}

		if (coursestudymode.length > 0) {
			let filterTermArray = [];
			coursestudymode.split('::').forEach(filterTerm => {
				filterTermArray.push({ 'courseOptions.studyMode': filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}

		if (courseaward.length > 0) {
			let filterTermArray = [];
			courseaward.split('::').forEach(filterTerm => {
				filterTermArray.push({ award: filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}

		if (courseentrylevel.length > 0) {
			let filterTermArray = [];
			courseentrylevel.split('::').forEach(filterTerm => {
				filterTermArray.push({ 'entries.level': filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}

		if (coursedomains.length > 0) {
			let filterTermArray = [];
			coursedomains.split('::').forEach(filterTerm => {
				filterTermArray.push({ domains: filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}

		if (coursekeywords.length > 0) {
			let filterTermArray = [];
			coursekeywords.split('::').forEach(filterTerm => {
				filterTermArray.push({ keywords: filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}

		if (courseframework.length > 0) {
			let filterTermArray = [];
			courseframework.split('::').forEach(filterTerm => {
				filterTermArray.push({ competencyFramework: filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}

		if (coursepriority.length > 0) {
			let filterTermArray = [];
			coursepriority.split('::').forEach(filterTerm => {
				filterTermArray.push({ nationalPriority: filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}
	} else if (type === 'collection') {
		if (collectionkeywords.length > 0) {
			let filterTermArray = [];
			collectionkeywords.split('::').forEach(filterTerm => {
				filterTermArray.push({ keywords: filterTerm });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}

		if (collectionpublisher.length > 0) {
			let filterTermArray = [];
			collectionpublisher.split('::').forEach(filterTerm => {
				filterTermArray.push({ authors: parseInt(filterTerm) });
			});
			searchQuery['$and'].push({ $or: filterTermArray });
		}
	}
	return searchQuery;
}

export const getFilter = async (searchString, type, field, isArray, activeFiltersQuery) => {
	return new Promise(async (resolve, reject) => {
		let collection = Data;
		if (type === 'course') {
			collection = Course;
		} else if (type === 'collection') {
			collection = Collections;
		}
		let q = '',
			p = '';
		let combinedResults = [],
			activeCombinedResults = [],
			publishers = [];

		if (searchString) q = collection.aggregate(filterQueryGenerator(field, searchString, type, isArray, {}));
		else q = collection.aggregate(filterQueryGenerator(field, '', type, isArray, {}));

		q.exec((err, data) => {
			if (err) return resolve({});

			if (data.length) {
				data.forEach(dat => {
					if (dat.result && dat.result !== '') {
						if (field === 'datasetfields.phenotypes') combinedResults.push(dat.result.name.trim());
						else if (field === 'courseOptions.startDate') combinedResults.push(moment(dat.result).format('DD MMM YYYY'));
						else {
							if (_.isString(dat.result)) {
								combinedResults.push(dat.result.trim());
							} else if (field === 'authors' && dat.id === dat.result) {
								combinedResults.push(dat);
							}
						}
					}
				});
			}

			var newSearchQuery = JSON.parse(JSON.stringify(activeFiltersQuery));
			if (type !== 'collection') {
				newSearchQuery['$and'].push({ type: type });
			}

			if (searchString) p = collection.aggregate(filterQueryGenerator(field, searchString, type, isArray, newSearchQuery));
			else p = collection.aggregate(filterQueryGenerator(field, '', type, isArray, newSearchQuery));

			p.exec((activeErr, activeData) => {
				if (activeData.length) {
					activeData.forEach(dat => {
						if (dat.result && dat.result !== '') {
							if (field === 'datasetfields.phenotypes') activeCombinedResults.push(dat.result.name.trim());
							else if (field === 'courseOptions.startDate') activeCombinedResults.push(moment(dat.result).format('DD MMM YYYY'));
							else {
								if (_.isString(dat.result)) {
									activeCombinedResults.push(dat.result.trim());
								} else if (field === 'authors' && dat.id === dat.result) {
									activeCombinedResults.push(dat);
								}
							}
						}
					});
				}
				resolve([combinedResults, activeCombinedResults, publishers]);
			});
		});
	});
};

export function filterQueryGenerator(filter, searchString, type, isArray, activeFiltersQuery) {
	var queryArray = [];

	if (!_.isEmpty(activeFiltersQuery)) {
		queryArray.push({ $match: activeFiltersQuery });
	} else {
		if (searchString !== '') {
			type !== 'collection'
				? queryArray.push({ $match: { $and: [{ $text: { $search: searchString } }, { type: type }, { activeflag: 'active' }] } })
				: queryArray.push({ $match: { $and: [{ $text: { $search: searchString } }, { activeflag: 'active' }, { publicflag: true }] } });
		} else {
			type !== 'collection'
				? queryArray.push({ $match: { $and: [{ type: type }, { activeflag: 'active' }] } })
				: queryArray.push({ $match: { $and: [{ activeflag: 'active' }, { publicflag: true }] } });
		}
	}

	if (type === 'course') {
		queryArray.push({
			$match: { $or: [{ 'courseOptions.startDate': { $gte: new Date(Date.now()) } }, { 'courseOptions.flexibleDates': true }] },
		});
		queryArray.push({ $unwind: '$courseOptions' });
	}

	if (type === 'collection' && filter === 'authors') {
		queryArray.push({ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } });
		queryArray.push(
			{ $unwind: '$persons' },
			{
				$project: {
					result: '$' + filter,
					_id: 0,
					value: { $concat: ['$persons.firstname', ' ', '$persons.lastname'] },
					id: '$persons.id',
				},
			}
		);
	} else {
		queryArray.push({
			$project: {
				result: '$' + filter,
				_id: 0,
			},
		});
	}

	if (isArray) {
		queryArray.push({ $unwind: '$result' });
		queryArray.push({ $unwind: '$result' });
	}

	queryArray.push(
		{
			$group: {
				_id: null,
				distinct: {
					$addToSet: '$$ROOT',
				},
			},
		},
		{
			$unwind: {
				path: '$distinct',
				preserveNullAndEmptyArrays: false,
			},
		},
		{
			$replaceRoot: {
				newRoot: '$distinct',
			},
		},
		{
			$sort: {
				result: 1,
			},
		}
	);

	return queryArray;
}
