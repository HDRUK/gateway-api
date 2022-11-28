import { Data } from '../tool/data.model';
import { Course } from '../course/course.model';
import { Collections } from '../collections/collections.model';
import { DataUseRegister } from '../dataUseRegister/dataUseRegister.model';
import { findNodeInTree } from '../filters/utils/filters.util';
import {
	datasetFilters,
	toolFilters,
	projectFilters,
	paperFilters,
	collectionFilters,
	courseFilters,
	dataUseRegisterFilters,
} from '../filters/filters.mapper';
import _ from 'lodash';
import moment from 'moment';
import helperUtil from '../utilities/helper.util';

export async function getObjectResult(type, searchAll, searchQuery, startIndex, maxResults, sort, authorID, form) {
	let collection = Data;
	if (type === 'course') {
		collection = Course;
	} else if (type === 'collection') {
		collection = Collections;
	} else if (type === 'dataUseRegister') {
		collection = DataUseRegister;
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
					creator: 1,
				},
			},
			{
				$addFields: {
					myEntity: {
						$eq: ['$creator', authorID],
					},
				},
			},
		];
	} else if (type === 'collection') {
		const searchTerm = (newSearchQuery && newSearchQuery['$and'] && newSearchQuery['$and'].find(exp => !_.isNil(exp['$text']))) || {};

		if (searchTerm) {
			newSearchQuery['$and'] = newSearchQuery['$and'].filter(exp => !exp['$text']);
		}

		queryObject = [
			{ $match: searchTerm },
			{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
			{
				$addFields: {
					persons: {
						$map: {
							input: '$persons',
							as: 'row',
							in: {
								id: '$$row.id',
								firstname: '$$row.firstname',
								lastname: '$$row.lastname',
								fullName: { $concat: ['$$row.firstname', ' ', '$$row.lastname'] },
							},
						},
					},
				},
			},
			{ $match: newSearchQuery },
			{
				$project: {
					_id: 0,
					id: 1,
					name: 1,
					description: 1,
					imageLink: 1,
					relatedObjects: 1,

					'persons.id': 1,
					'persons.firstname': 1,
					'persons.lastname': 1,
					'persons.fullName': 1,

					activeflag: 1,
					counter: 1,
					latestUpdate: {
						$cond: {
							if: { $gte: ['$createdAt', '$updatedon'] },
							then: '$createdAt',
							else: '$updatedon',
						},
					},
					relatedresources: { $cond: { if: { $isArray: '$relatedObjects' }, then: { $size: '$relatedObjects' }, else: 0 } },
				},
			},
		];
	} else if (type === 'dataUseRegister') {
		const searchTerm = (newSearchQuery && newSearchQuery['$and'] && newSearchQuery['$and'].find(exp => !_.isNil(exp['$text']))) || {};

		if (searchTerm) {
			newSearchQuery['$and'] = newSearchQuery['$and'].filter(exp => !exp['$text']);
		}

		let dataUseSort = {};

		switch (sort) {
			case '':
				dataUseSort = searchAll ? { lastActivity: -1 } : { score: { $meta: 'textScore' } };
				break;
			case 'relevance':
				dataUseSort = searchAll ? { projectTitle: 1 } : { score: { $meta: 'textScore' } };
				break;
			case 'popularity':
				dataUseSort = searchAll ? { counter: -1, projectTitle: 1 } : { counter: -1, score: { $meta: 'textScore' } };
				break;
			case 'latest':
				dataUseSort = searchAll ? { lastActivity: -1 } : { lastActivity: -1, score: { $meta: 'textScore' } };
				break;
			case 'resources':
				dataUseSort = searchAll ? { relatedResourcesCount: -1 } : { relatedResourcesCount: -1, score: { $meta: 'textScore' } };
				break;
		}

		queryObject = [
			{ $match: searchTerm },
			{
				$lookup: {
					from: 'publishers',
					localField: 'publisher',
					foreignField: '_id',
					as: 'publisherDetails',
				},
			},
			{
				$addFields: {
					publisherInfo: { name: '$publisherDetails.name' },
				},
			},
			{ $match: newSearchQuery },
			{ $addFields: { relatedResourcesCount: { $size: { $ifNull: ['$relatedObjects', []] } } } },
			{ $sort: dataUseSort },
			{ $skip: parseInt(startIndex) },
			{ $limit: maxResults },
			{
				$lookup: {
					from: 'tools',
					let: {
						listOfGatewayDatasets: '$gatewayDatasets',
					},
					pipeline: [
						{
							$match: {
								$expr: {
									$and: [
										{ $in: ['$pid', '$$listOfGatewayDatasets'] },
										{
											$eq: ['$activeflag', 'active'],
										},
									],
								},
							},
						},
						{ $project: { pid: 1, name: 1 } },
					],
					as: 'gatewayDatasetsInfo',
				},
			},
			{
				$project: {
					_id: 0,
					id: 1,
					projectTitle: 1,
					organisationName: 1,
					keywords: 1,
					datasetTitles: 1,
					publisherInfo: 1,
					publisherDetails: 1,
					gatewayDatasetsInfo: 1,
					nonGatewayDatasets: 1,
					activeflag: 1,
					counter: 1,
					type: 1,
					latestUpdate: '$lastActivity',
					relatedresources: { $cond: { if: { $isArray: '$relatedObjects' }, then: { $size: '$relatedObjects' }, else: 0 } },
				},
			},
		];
	} else if (type === 'dataset') {
		queryObject = [
			{ $match: newSearchQuery },
			{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
			{
				$lookup: {
					from: 'tools',
					let: {
						pid: '$pid',
					},
					pipeline: [
						{ $unwind: '$relatedObjects' },
						{
							$match: {
								$expr: {
									$and: [
										{
											$eq: ['$relatedObjects.pid', '$$pid'],
										},
										{
											$eq: ['$activeflag', 'active'],
										},
									],
								},
							},
						},
						{ $group: { _id: null, count: { $sum: 1 } } },
					],
					as: 'relatedResourcesTools',
				},
			},
			{
				$lookup: {
					from: 'course',
					let: {
						pid: '$pid',
					},
					pipeline: [
						{ $unwind: '$relatedObjects' },
						{
							$match: {
								$expr: {
									$and: [
										{
											$eq: ['$relatedObjects.pid', '$$pid'],
										},
										{
											$eq: ['$activeflag', 'active'],
										},
									],
								},
							},
						},
						{ $group: { _id: null, count: { $sum: 1 } } },
					],
					as: 'relatedResourcesCourses',
				},
			},
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
					isCohortDiscovery: 1,
					'datasetfields.publisher': 1,
					'datasetfields.geographicCoverage': 1,
					'datasetfields.physicalSampleAvailability': 1,
					'datasetfields.abstract': 1,
					'datasetfields.ageBand': 1,
					'datasetfields.phenotypes': 1,
					'datasetv2.accessibility.access.deliveryLeadTime': 1,
					'datasetv2.summary.publisher.name': 1,
					'datasetv2.summary.publisher.logo': 1,
					'datasetv2.summary.publisher.memberOf': 1,
					'datasetv2.provenance.temporal.accrualPeriodicity': 1,

					'persons.id': 1,
					'persons.firstname': 1,
					'persons.lastname': 1,

					activeflag: 1,
					counter: 1,

					'datasetfields.metadataquality.weighted_quality_score': {
						$convert: {
							input: '$datasetfields.metadataquality.weighted_quality_score',
							to: 'double',
							onError: 0,
							onNull: 0,
						},
					},

					'datasetfields.metadataquality.weighted_quality_rating': 1,
					'datasetfields.metadataquality.weighted_error_percent': 1,
					'datasetfields.metadataquality.weighted_completeness_percent': 1,

					latestUpdate: '$timestamps.updated',
					relatedresources: {
						$add: [
							{
								$cond: {
									if: { $eq: [{ $size: '$relatedResourcesTools' }, 0] },
									then: 0,
									else: { $first: '$relatedResourcesTools.count' },
								},
							},
							{
								$cond: {
									if: { $eq: [{ $size: '$relatedResourcesCourses' }, 0] },
									then: 0,
									else: { $first: '$relatedResourcesCourses.count' },
								},
							},
						],
					},
				},
			},
		];
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
					'datasetfields.metadataquality.weighted_quality_score': 1,
					latestUpdate: {
						$cond: {
							if: { $gte: ['$createdAt', '$updatedon'] },
							then: '$createdAt',
							else: '$updatedon',
						},
					},
					relatedresources: { $cond: { if: { $isArray: '$relatedObjects' }, then: { $size: '$relatedObjects' }, else: 0 } },
					journalYear: 1,
					journal: 1,
					authorsNew: 1,
					authors: 1,
				},
			},
			{
				$addFields: {
					myEntity: {
						$in: [authorID, '$authors'],
					},
				},
			},
		];
	}

	if (type !== 'dataUseRegister') {
		if (sort === '') {
			if (type === 'dataset') {
				if (searchAll) queryObject.push({ $sort: { 'datasetfields.metadataquality.weighted_quality_score': -1, name: 1 } });
				else queryObject.push({ $sort: { score: { $meta: 'textScore' } } });
			} else if (type === 'paper') {
				if (searchAll) queryObject.push({ $sort: { journalYear: -1 } });
				else queryObject.push({ $sort: { journalYear: -1, score: { $meta: 'textScore' } } });
			} else {
				if (form === 'true' && searchAll) {
					queryObject.push({ $sort: { myEntity: -1, latestUpdate: -1 } });
				} else if (form === 'true' && !searchAll) {
					queryObject.push({ $sort: { myEntity: -1, score: { $meta: 'textScore' } } });
				} else if (form !== 'true' && searchAll) {
					queryObject.push({ $sort: { latestUpdate: -1 } });
				} else if (form !== 'true' && !searchAll) {
					queryObject.push({ $sort: { score: { $meta: 'textScore' } } });
				}
			}
		} else if (sort === 'relevance') {
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
			if (searchAll) queryObject.push({ $sort: { 'datasetfields.metadataquality.weighted_quality_score': -1, name: 1 } });
			else queryObject.push({ $sort: { 'datasetfields.metadataquality.weighted_quality_score': -1, score: { $meta: 'textScore' } } });
		} else if (sort === 'startdate') {
			if (form === 'true' && searchAll) {
				queryObject.push({ $sort: { myEntity: -1, 'courseOptions.startDate': 1 } });
			} else if (form === 'true' && !searchAll) {
				queryObject.push({ $sort: { myEntity: -1, 'courseOptions.startDate': 1, score: { $meta: 'textScore' } } });
			} else if (form !== 'true' && searchAll) {
				queryObject.push({ $sort: { 'courseOptions.startDate': 1 } });
			} else if (form !== 'true' && !searchAll) {
				queryObject.push({ $sort: { myEntity: -1, 'courseOptions.startDate': 1, score: { $meta: 'textScore' } } });
			}
		} else if (sort === 'latest') {
			if (searchAll) queryObject.push({ $sort: { latestUpdate: -1 } });
			else queryObject.push({ $sort: { latestUpdate: -1, score: { $meta: 'textScore' } } });
		} else if (sort === 'resources') {
			if (searchAll) queryObject.push({ $sort: { relatedresources: -1 } });
			else queryObject.push({ $sort: { relatedresources: -1, score: { $meta: 'textScore' } } });
		} else if (sort === 'sortbyyear') {
			if (type === 'paper') {
				if (searchAll) queryObject.push({ $sort: { journalYear: -1 } });
				else queryObject.push({ $sort: { journalYear: -1, score: { $meta: 'textScore' } } });
			}
		}
	}

	const searchResults =
		type === 'dataUseRegister'
			? await collection.aggregate(queryObject).catch(err => {
				process.stdout.write(`${err.message}\n`);
			  })
			: await collection
					.aggregate(queryObject)
					.skip(parseInt(startIndex))
					.limit(parseInt(maxResults))
					.catch(err => {
						process.stdout.write(`${err.message}\n`);
					});

	return { data: searchResults };
}

export function getObjectCount(type, searchAll, searchQuery) {
	let collection = Data;
	if (type === 'course') {
		collection = Course;
	} else if (type === 'collection') {
		collection = Collections;
	} else if (type === 'dataUseRegister') {
		collection = DataUseRegister;
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
		const searchTerm = (newSearchQuery && newSearchQuery['$and'] && newSearchQuery['$and'].find(exp => !_.isNil(exp['$text']))) || {};

		if (searchTerm) {
			newSearchQuery['$and'] = newSearchQuery['$and'].filter(exp => !exp['$text']);
		}

		if (searchAll) {
			q = collection.aggregate([
				{ $match: searchTerm },
				{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
				{
					$addFields: {
						persons: {
							$map: {
								input: '$persons',
								as: 'row',
								in: {
									id: '$$row.id',
									firstname: '$$row.firstname',
									lastname: '$$row.lastname',
									fullName: { $concat: ['$$row.firstname', ' ', '$$row.lastname'] },
								},
							},
						},
					},
				},
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
					{ $match: searchTerm },
					{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
					{
						$addFields: {
							persons: {
								$map: {
									input: '$persons',
									as: 'row',
									in: {
										id: '$$row.id',
										firstname: '$$row.firstname',
										lastname: '$$row.lastname',
										fullName: { $concat: ['$$row.firstname', ' ', '$$row.lastname'] },
									},
								},
							},
						},
					},
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
	} else if (type === 'dataUseRegister') {
		const searchTerm = (newSearchQuery && newSearchQuery['$and'] && newSearchQuery['$and'].find(exp => !_.isNil(exp['$text']))) || {};

		if (searchTerm) {
			newSearchQuery['$and'] = newSearchQuery['$and'].filter(exp => !exp['$text']);
		}

		q = collection.aggregate([
			{ $match: searchTerm },
			{
				$lookup: {
					from: 'publishers',
					localField: 'publisher',
					foreignField: '_id',
					as: 'publisherDetails',
				},
			},
			{
				$addFields: {
					publisherDetails: {
						$map: {
							input: '$publisherDetails',
							as: 'row',
							in: {
								name: '$$row.name',
							},
						},
					},
				},
			},
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

export function getMyObjectsCount(type, searchAll, searchQuery, authorID) {
	let newSearchQuery = JSON.parse(JSON.stringify(searchQuery));

	newSearchQuery['$and'].push({ type: type });

	let collection = Data;
	if (type === 'course') {
		collection = Course;
		newSearchQuery['$and'].push({ creator: authorID });
	} else {
		newSearchQuery['$and'].push({ authors: authorID });
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

export function getObjectFilters(searchQueryStart, queryParams, type) {
	let searchQuery = JSON.parse(JSON.stringify(searchQueryStart));

	// iterate over query string keys
	for (const key of Object.keys(queryParams)) {
		try {
			const filterValues = queryParams[key].split('::');
			// check mapper for query type
			// let filterNode = findNodeInTree(`${type}Filters`, key);
			let filterNode;
			if (type === 'dataset') {
				filterNode = findNodeInTree(datasetFilters, key);
			} else if (type === 'tool') {
				filterNode = findNodeInTree(toolFilters, key);
			} else if (type === 'project') {
				filterNode = findNodeInTree(projectFilters, key);
			} else if (type === 'paper') {
				filterNode = findNodeInTree(paperFilters, key);
			} else if (type === 'collection') {
				filterNode = findNodeInTree(collectionFilters, key);
			} else if (type === 'course') {
				filterNode = findNodeInTree(courseFilters, key);
			} else if (type === 'dataUseRegister') {
				filterNode = findNodeInTree(dataUseRegisterFilters, key);
			}

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
					case 'dateEquals':
						searchQuery['$and'].push({
							$or: filterValues.map(value => {
								return { [`${dataPath}`]: value };
							}),
						});
						break;
					default:
						break;
				}
			}
		} catch (err) {
			process.stdout.write(`SEARCH - GET OBJECT FILTERS : ${err.message}\n`);
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
		} else if (type === 'datause') {
			collection = DataUseRegister;
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
