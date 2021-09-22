import Repository from '../base/repository';
import { StatsSnapshot } from './statsSnapshot.model';
import { Data } from '../tool/data.model';
import { RecordSearchData } from '../search/record.search.model';
import { DataRequestModel } from '../datarequest/datarequest.model';
import { Course } from '../course/course.model';
import { MessagesModel } from '../message/message.model';
import constants from '../utilities/constants.util';

export default class StatsRepository extends Repository {
	constructor() {
		super(StatsSnapshot);
		this.statsSnapshot = StatsSnapshot;
	}

	async getSnapshots(query) {
		const options = { lean: true };
		return this.find(query, options);
	}

	async createSnapshot(data) {
		return this.updateByQuery({ date: data.date }, data);
	}

	async getTechnicalMetadataStats() {
		const data = await Data.aggregate([
			{
				$facet: {
					TotalDataSets: [
						{
							$match: {
								activeflag: 'active',
								type: 'dataset',
								'datasetfields.publisher': { $nin: ['OTHER > HEALTH DATA RESEARCH UK', 'HDR UK'] },
							},
						},
						{ $count: 'TotalDataSets' },
					],
					TotalMetaData: [
						{
							$match: {
								activeflag: 'active',
								type: 'dataset',
								'datasetfields.technicaldetails': {
									$exists: true,
									$not: {
										$size: 0,
									},
								},
							},
						},
						{
							$count: 'TotalMetaData',
						},
					],
				},
			},
		]);

		return {
			totalDatasets: data[0].TotalDataSets[0].TotalDataSets || 0,
			datasetsMetadata: data[0].TotalMetaData[0].TotalMetaData || 0,
		};
	}

	async getUptimeStatsByMonth(startMonth, endMonth) {
		return DataRequestModel.aggregate([
			{
				$match: {
					dateSubmitted: {
						$gte: startMonth,
						$lt: endMonth,
					},
					applicationStatus: {
						$in: ['submitted', 'approved', 'rejected', 'inReview', 'approved with conditions'],
					},
					publisher: {
						$nin: ['HDR UK', 'OTHER > HEALTH DATA RESEARCH UK'],
					},
				},
			},
			{
				$lookup: {
					from: 'tools',
					localField: 'datasetIds',
					foreignField: 'datasetid',
					as: 'datasets',
				},
			},
			{
				$project: {
					'datasets.name': 1,
					'datasets.datasetfields.publisher': 1,
					'datasets.pid': 1,
					_id: 0,
				},
			},
			{
				$unwind: {
					path: '$datasets',
					preserveNullAndEmptyArrays: false,
				},
			},
			{
				$group: {
					_id: '$datasets.name',
					name: {
						$first: '$datasets.name',
					},
					publisher: {
						$first: '$datasets.datasetfields.publisher',
					},
					pid: {
						$first: '$datasets.pid',
					},
					requests: {
						$sum: 1,
					},
				},
			},
			{
				$sort: {
					requests: -1,
					publisher: 1,
					name: 1,
				},
			},
			{
				$limit: 5,
			},
		]);
	}

	async getSearchStatsByMonth(startMonth, endMonth) {
		const query = [
			{
				$facet: {
					totalMonth: [
						{ $match: { datesearched: { $gte: startMonth, $lt: endMonth } } },

						{
							$group: {
								_id: 'totalMonth',
								count: { $sum: 1 },
							},
						},
					],
					noResultsMonth: [
						{
							$match: {
								$and: [
									{ datesearched: { $gte: startMonth, $lt: endMonth } },
									{ 'returned.dataset': 0 },
									{ 'returned.tool': 0 },
									{ 'returned.project': 0 },
									{ 'returned.paper': 0 },
									{ 'returned.person': 0 },
								],
							},
						},
						{
							$group: {
								_id: 'noResultsMonth',
								count: { $sum: 1 },
							},
						},
					],
				},
			},
		];

		const dataSearches = await RecordSearchData.aggregate(query);

		return {
			totalMonth: dataSearches[0].totalMonth[0] ? dataSearches[0].totalMonth[0].count : 0,
			noResultsMonth: dataSearches[0].noResultsMonth[0] ? dataSearches[0].noResultsMonth[0].count : 0,
		};
	}

	async getDataAccessRequestStats(startMonth, endMonth) {
		const dateQuery = startMonth && endMonth ? { dateSubmitted: { $gte: startMonth, $lt: endMonth } } : {};
		const firstMessageDateQuery = startMonth && endMonth ? { createdDate: { $gte: startMonth, $lt: endMonth } } : {};
		const excludedDatasets = await this.getExcludedDatasetIds();
		const accessRequests = await DataRequestModel.find(
			{
				...dateQuery,
				applicationStatus: {
					$in: [
						constants.applicationStatuses.SUBMITTED,
						constants.applicationStatuses.APPROVED,
						constants.applicationStatuses.REJECTED,
						constants.applicationStatuses.INREVIEW,
						constants.applicationStatuses.APPROVEDWITHCONDITIONS,
					],
				},
				datasetIds: { $nin: excludedDatasets },
			},
			{ datasetIds: 1, _id: 0 }
		).lean();

		const accessRequestsCount = accessRequests.reduce((acc, cur) => {
			const { datasetIds = [] } = cur;
			acc = acc + datasetIds.length;
			return acc;
		}, 0);

		const firstMessagesCount = await MessagesModel.countDocuments({ ...firstMessageDateQuery, firstMessage: { $exists: true, $ne: {} } });

		const accessRequestAndFirstMessageCount = accessRequestsCount + firstMessagesCount;

		return accessRequestAndFirstMessageCount;
	}

	async getTopDatasetsByMonth(startMonth, endMonth) {
		return DataRequestModel.aggregate([
			{
				$match: {
					dateSubmitted: {
						$gte: startMonth,
						$lt: endMonth,
					},
					applicationStatus: {
						$in: ['submitted', 'approved', 'rejected', 'inReview', 'approved with conditions'],
					},
					publisher: {
						$nin: ['HDR UK', 'OTHER > HEALTH DATA RESEARCH UK'],
					},
				},
			},
			{
				$lookup: {
					from: 'tools',
					localField: 'datasetIds',
					foreignField: 'datasetid',
					as: 'datasets',
				},
			},
			{
				$project: {
					'datasets.name': 1,
					'datasets.datasetfields.publisher': 1,
					'datasets.pid': 1,
					_id: 0,
				},
			},
			{
				$unwind: {
					path: '$datasets',
					preserveNullAndEmptyArrays: false,
				},
			},
			{
				$group: {
					_id: '$datasets.name',
					name: {
						$first: '$datasets.name',
					},
					publisher: {
						$first: '$datasets.datasetfields.publisher',
					},
					pid: {
						$first: '$datasets.pid',
					},
					requests: {
						$sum: 1,
					},
				},
			},
			{
				$sort: {
					requests: -1,
					publisher: 1,
					name: 1,
				},
			},
			{
				$limit: 5,
			},
		]);
	}

	async getExcludedDatasetIds() {
		const results = await Data.find(
			{
				'datasetfields.publisher': { $in: ['HDR UK', 'OTHER > HEALTH DATA RESEARCH UK'] },
			},
			{
				_id: 0,
				datasetid: 1,
			}
		).lean();

		return results.map(dataset => dataset.datasetid);
	}

	async getTopSearchesByMonth(month, year) {
		return new Promise(async (resolve, reject) => {
			let q = RecordSearchData.aggregate([
				{ $addFields: { month: { $month: '$createdAt' }, year: { $year: '$createdAt' } } },
				{
					$match: {
						$and: [{ month }, { year }, { searched: { $ne: '' } }],
					},
				},
				{
					$group: {
						_id: { $toLower: '$searched' },
						count: { $sum: 1 },
					},
				},
				{ $sort: { count: -1 } },
			]).limit(10);

			q.exec(async (err, topSearches) => {
				if (err) reject(err);

				let resolvedArray = await Promise.all(
					topSearches.map(async topSearch => {
						let searchQuery = { $and: [{ activeflag: 'active' }] };
						searchQuery['$and'].push({ $text: { $search: topSearch._id } });

						await Promise.all([
							this.getObjectResult('dataset', searchQuery),
							this.getObjectResult('tool', searchQuery),
							this.getObjectResult('project', searchQuery),
							this.getObjectResult('paper', searchQuery),
							this.getObjectResult('course', searchQuery),
						]).then(resources => {
							topSearch.datasets = resources[0][0] !== undefined && resources[0][0].count !== undefined ? resources[0][0].count : 0;
							topSearch.tools = resources[1][0] !== undefined && resources[1][0].count !== undefined ? resources[1][0].count : 0;
							topSearch.projects = resources[2][0] !== undefined && resources[2][0].count !== undefined ? resources[2][0].count : 0;
							topSearch.papers = resources[3][0] !== undefined && resources[3][0].count !== undefined ? resources[3][0].count : 0;
							topSearch.course = resources[4][0] !== undefined && resources[4][0].count !== undefined ? resources[4][0].count : 0;
						});
						return topSearch;
					})
				);
				resolve(resolvedArray);
			});
		});
	}

	async getObjectResult(type, searchQuery) {
		let newSearchQuery = JSON.parse(JSON.stringify(searchQuery));
		newSearchQuery['$and'].push({ type });
		var q = '';

		q = Data.aggregate([
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

		return new Promise((resolve, reject) => {
			q.exec((err, data) => {
				if (typeof data === 'undefined') resolve([]);
				else resolve(data);
			});
		});
	}

	async getUnmetSearchesByMonth(entityType, month, year) {
		const entitySearch = { ['returned.' + entityType]: { $lte: 0 } };
		const terms = await this.getTopSearchesByMonth(month, year);
		const duplicateTerms = await this.getDuplicateTerms(entityType, terms);
		return RecordSearchData.aggregate([
			{ $addFields: { month: { $month: '$createdAt' }, year: { $year: '$createdAt' } } },
			{
				$match: {
					$and: [{ month }, { year }, entitySearch, { searched: { $nin: duplicateTerms } }],
				},
			},
			{
				$group: {
					_id: { $toLower: '$searched' },
					count: { $sum: 1 },
					maxDatasets: { $max: '$returned.dataset' },
					maxProjects: { $max: '$returned.project' },
					maxTools: { $max: '$returned.tool' },
					maxPapers: { $max: '$returned.paper' },
					maxCourses: { $max: '$returned.course' },
					maxPeople: { $max: '$returned.people' },
					entity: { $max: entityType },
				},
			},
			{ $sort: { count: -1 } },
			{ $limit: 10 },
		]);
	}

	//this should be a temporary fix until we find the root cause of the issue
	async getDuplicateTerms(entityType, terms) {
		let type;
		let duplicateTerms = [];

		switch (entityType) {
			case 'dataset':
				type = 'datasets';
				break;
			case 'tool':
				type = 'tools';
				break;
			case 'paper':
				type = 'papers';
				break;
			case 'project':
				type = 'projects';
				break;
			default:
				type = '';
		}

		if (type) {
			duplicateTerms = terms.map(value => value[type] > 0 && value._id).filter(Boolean);
		}
		duplicateTerms = [...duplicateTerms, ''];

		return duplicateTerms;
	}

	async getRecentSearches() {
		return RecordSearchData.aggregate([
			{ $match: { $or: [{ 'returned.tool': { $gt: 0 } }, { 'returned.project': { $gt: 0 } }, { 'returned.person': { $gt: 0 } }] } },
			{
				$group: {
					_id: { $toLower: '$searched' },
					count: { $sum: 1 },
					returned: { $first: '$returned' },
				},
			},
			{ $sort: { datesearched: 1 } },
			{ $limit: 10 },
		]);
	}

	async getPopularCourses() {
		return Course.aggregate([
			{
				$match: {
					counter: {
						$gt: 0,
					},
					title: {
						$exists: true,
					},
					activeflag: 'active',
				},
			},
			{
				$project: {
					_id: 0,
					type: 1,
					title: 1,
					provider: 1,
					courseOptions: 1,
					award: 1,
					domains: 1,
					description: 1,
					id: 1,
					counter: 1,
					activeflag: 1,
				},
			},
			{
				$group: {
					_id: '$title',
					type: { $first: '$type' },
					title: { $first: '$title' },
					provider: { $first: '$provider' },
					courseOptions: { $first: '$courseOptions' },
					award: { $first: '$award' },
					domains: { $first: '$domains' },
					description: { $first: '$description' },
					id: { $first: '$id' },
					counter: { $first: '$counter' },
					activeflag: { $first: '$activeflag' },
				},
			},
			{
				$sort: {
					counter: -1,
					title: 1,
				},
			},
			{
				$limit: 10,
			},
		]);
	}

	async getActiveCourseCount() {
		return Course.countDocuments({ activeflag: 'active' });
	}

	async getPopularEntitiesByType(entityType) {
		let entityTypeFilter = {};
		if (entityType) entityTypeFilter = { type: entityType };

		return Data.aggregate([
			{
				$match: {
					...entityTypeFilter,
					counter: {
						$gt: 0,
					},
					name: {
						$exists: true,
					},
					pid: {
						$ne: 'fd8d0743-344a-4758-bb97-f8ad84a37357', //PID for HDR-UK Papers dataset
					},
					activeflag: 'active',
				},
			},
			{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
			{
				$project: {
					_id: 0,
					type: 1,
					bio: 1,
					firstname: 1,
					lastname: 1,
					name: 1,
					categories: 1,
					pid: 1,
					id: 1,
					counter: 1,
					programmingLanguage: 1,
					tags: 1,
					description: 1,
					activeflag: 1,
					datasetv2: 1,
					datasetfields: 1,
					'persons.id': 1,
					'persons.firstname': 1,
					'persons.lastname': 1,
				},
			},
			{
				$group: {
					_id: '$name',
					type: { $first: '$type' },
					name: { $first: '$name' },
					pid: { $first: '$pid' },
					bio: { $first: '$bio' },
					firstname: { $first: '$firstname' },
					lastname: { $first: '$lastname' },
					id: { $first: '$id' },
					categories: { $first: '$categories' },
					counter: { $first: '$counter' },
					programmingLanguage: { $first: '$programmingLanguage' },
					tags: { $first: '$tags' },
					description: { $first: '$description' },
					activeflag: { $first: '$activeflag' },
					datasetv2: { $first: '$datasetv2' },
					datasetfields: { $first: '$datasetfields' },
					persons: { $first: '$persons' },
				},
			},
			{
				$sort: {
					counter: -1,
					name: 1,
				},
			},
			{
				$limit: 10,
			},
		]);
	}

	async getRecentlyUpdatedCourses() {
		return Course.find(
			{ activeflag: 'active' },
			{
				_id: 0,
				type: 1,
				title: 1,
				provider: 1,
				courseOptions: 1,
				award: 1,
				domains: 1,
				description: 1,
				id: 1,
				counter: 1,
				updatedon: 1,
			}
		)
			.sort({ updatedon: -1, title: 1 })
			.limit(10)
			.lean();
	}

	async getRecentlyUpdatedDatasets() {
		return Data.find(
			{
				$and: [
					{
						type: 'dataset',
						activeflag: 'active',
						pid: {
							$ne: 'fd8d0743-344a-4758-bb97-f8ad84a37357', //Production PID for HDR-UK Papers dataset
						},
					},
				],
			},
			{
				_id: 0,
				type: 1,
				name: 1,
				pid: 1,
				id: 1,
				counter: 1,
				activeflag: 1,
				datasetv2: 1,
				datasetfields: 1,
				description: 1,
				updatedAt: 1,
			}
		)
			.sort({ updatedAt: -1, name: 1 })
			.limit(10)
			.lean();
	}

	async getRecentlyUpdatedEntitiesByType(entityType) {
		if (entityType) {
			return Data.find(
				{
					$and: [
						{
							type: entityType,
							activeflag: 'active',
						},
					],
				},
				{
					_id: 0,
					type: 1,
					bio: 1,
					firstname: 1,
					lastname: 1,
					name: 1,
					categories: 1,
					id: 1,
					counter: 1,
					programmingLanguage: 1,
					tags: 1,
					description: 1,
					activeflag: 1,
					authors: 1,
					updatedon: 1,
				}
			)
				.populate([{ path: 'persons', options: { select: { id: 1, firstname: 1, lastname: 1 } } }])
				.sort({ updatedon: -1, name: 1 })
				.limit(10)
				.lean();
		} else {
			return Data.find({ activeflag: 'active' }).sort({ updatedon: -1 }).limit(10).lean();
		}
	}

	async getTotalSearchesByUsers() {
		const lastDay = new Date();
		lastDay.setDate(lastDay.getDate() - 1);

		const lastWeek = new Date();
		lastWeek.setDate(lastWeek.getDate() - 7);

		const lastMonth = new Date();
		lastMonth.setMonth(lastMonth.getMonth() - 1);

		const lastYear = new Date();
		lastYear.setYear(lastYear.getYear() - 1);

		const query = [
			{
				$facet: {
					lastDay: [
						{ $match: { datesearched: { $gt: lastDay } } },
						{
							$group: {
								_id: 'lastDay',
								count: { $sum: 1 },
							},
						},
					],
					lastWeek: [
						{ $match: { datesearched: { $gt: lastWeek } } },
						{
							$group: {
								_id: 'lastWeek',
								count: { $sum: 1 },
							},
						},
					],
					lastMonth: [
						{ $match: { datesearched: { $gt: lastMonth } } },
						{
							$group: {
								_id: 'lastMonth',
								count: { $sum: 1 },
							},
						},
					],
					lastYear: [
						{ $match: { datesearched: { $gt: lastYear } } },
						{
							$group: {
								_id: 'lastYear',
								count: { $sum: 1 },
							},
						},
					],
				},
			},
		];

		const results = await RecordSearchData.aggregate(query);

		return {
			day: results[0].lastDay[0] ? results[0].lastDay[0].count : 0,
			week: results[0].lastWeek[0] ? results[0].lastWeek[0].count : 0,
			month: results[0].lastMonth[0] ? results[0].lastMonth[0].count : 0,
			year: results[0].lastYear[0] ? results[0].lastYear[0].count : 0,
		};
	}

	async getTotalEntityCounts() {
		const query = [
			{
				$match: {
					$and: [
						{ activeflag: 'active' },
						{ 'datasetfields.publisher': { $ne: 'OTHER > HEALTH DATA RESEARCH UK' } },
						{ 'datasetfields.publisher': { $ne: 'HDR UK' } },
					],
				},
			},
			{ $group: { _id: '$type', count: { $sum: 1 } } },
		];

		return Data.aggregate(query);
	}
}
