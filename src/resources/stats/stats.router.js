import express from 'express';
import { RecordSearchData } from '../search/record.search.model';
import { Data } from '../tool/data.model';
import { DataRequestModel } from '../datarequests/datarequests.model';
import { getHdrDatasetId } from './kpis.router';
import { Course } from '../course/course.model';
const router = express.Router();

/**
 * {get} /stats get some basic high level stats
 *
 * This will return a JSON document to show high level stats
 */
router.get('', async (req, res) => {
	try {
		const { query = {} } = req;

		switch (req.query.rank) {
			case undefined:
				var result;

				//get some dates for query
				var lastDay = new Date();
				lastDay.setDate(lastDay.getDate() - 1);

				var lastWeek = new Date();
				lastWeek.setDate(lastWeek.getDate() - 7);

				var lastMonth = new Date();
				lastMonth.setMonth(lastMonth.getMonth() - 1);

				var lastYear = new Date();
				lastYear.setYear(lastYear.getYear() - 1);

				var aggregateQuerySearches = [
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

				//set the aggregate queries
				var aggregateQueryTypes = [
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

				//set the aggregate queries
				const courseQuery = [
					{
						$match: {
							$and: [{ activeflag: 'active' }],
						},
					},
					{ $group: { _id: '$type', count: { $sum: 1 } } },
				];

				var q = RecordSearchData.aggregate(aggregateQuerySearches);

				var aggregateAccessRequests = [
					{
						$match: {
							$or: [
								{ applicationStatus: 'submitted' },
								{ applicationStatus: 'approved' },
								{ applicationStatus: 'rejected' },
								{ applicationStatus: 'inReview' },
								{ applicationStatus: 'approved with conditions' },
							],
						},
					},
					{ $project: { datasetIds: 1 } },
				];

				var y = DataRequestModel.aggregate(aggregateAccessRequests);
				let courseData = Course.aggregate(courseQuery);

				let counts = {}; //hold the type (i.e. tool, person, project, access requests) counts data
				await courseData.exec((err, res) => {
					if (err) return res.json({ success: false, error: err });

					let { count = 0 } = res[0];
					counts['course'] = count;
				});

				q.exec((err, dataSearches) => {
					if (err) return res.json({ success: false, error: err });

					var x = Data.aggregate(aggregateQueryTypes);
					x.exec((errx, dataTypes) => {
						if (errx) return res.json({ success: false, error: errx });

						for (var i = 0; i < dataTypes.length; i++) {
							//format the result in a clear and dynamic way
							counts[dataTypes[i]._id] = dataTypes[i].count;
						}

						y.exec(async (err, accessRequests) => {
							let hdrDatasetID = await getHdrDatasetId();
							let hdrDatasetIds = [];
							hdrDatasetID.map(hdrDatasetid => {
								hdrDatasetIds.push(hdrDatasetid.datasetid);
							});
							let accessRequestsCount = 0;

							if (err) return res.json({ success: false, error: err });

							accessRequests.map(accessRequest => {
								if (accessRequest.datasetIds && accessRequest.datasetIds.length > 0) {
									accessRequest.datasetIds.map(datasetid => {
										if (!hdrDatasetIds.includes(datasetid)) {
											accessRequestsCount++;
										}
									});
								}

								counts['accessRequests'] = accessRequestsCount;
							});

							if (typeof dataSearches[0].lastDay[0] === 'undefined') {
								dataSearches[0].lastDay[0] = { count: 0 };
							}
							if (typeof dataSearches[0].lastWeek[0] === 'undefined') {
								dataSearches[0].lastWeek[0] = { count: 0 };
							}
							if (typeof dataSearches[0].lastMonth[0] === 'undefined') {
								dataSearches[0].lastMonth[0] = { count: 0 };
							}
							if (typeof dataSearches[0].lastYear[0] === 'undefined') {
								dataSearches[0].lastYear[0] = { count: 0 };
							}

							result = res.json({
								success: true,
								data: {
									typecounts: counts,
									daycounts: {
										day: dataSearches[0].lastDay[0].count,
										week: dataSearches[0].lastWeek[0].count,
										month: dataSearches[0].lastMonth[0].count,
										year: dataSearches[0].lastYear[0].count,
									},
								},
							});
						});
					});
				});

				return result;
				break;

			case 'recent':
				var q = RecordSearchData.aggregate([
					{ $match: { $or: [{ 'returned.tool': { $gt: 0 } }, { 'returned.project': { $gt: 0 } }, { 'returned.person': { $gt: 0 } }] } },
					{
						$group: {
							_id: { $toLower: '$searched' },
							count: { $sum: 1 },
							returned: { $first: '$returned' },
						},
					},
					{ $sort: { datesearched: 1 } },
				]).limit(10);

				q.exec((err, data) => {
					if (err) return res.json({ success: false, error: err });
					return res.json({ success: true, data: data });
				});
				break;

			case 'popular':
				let popularType = {};
				if (query.type) popularType = { type: query.type };
				let popularData;

				if (popularType.type !== 'course') {
					popularData = await Data.aggregate([
						{
							$match: {
								...popularType,
								counter: {
									$gt: 0,
								},
								name: {
									$exists: true,
								},
								pid: {
									$ne: 'fd8d0743-344a-4758-bb97-f8ad84a37357', //PID for HDR-UK Papers dataset
								},
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
								counter: { $sum: '$counter' },
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
				} else if (popularType.type === 'course') {
					popularData = await Course.aggregate([
						{
							$match: {
								counter: {
									$gt: 0,
								},
								title: {
									$exists: true,
								},
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
								counter: { $sum: '$counter' },
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

				return res.json({ success: true, data: popularData });

			case 'updates':
				let recentlyUpdated = Data.find({ activeflag: 'active' }).sort({ updatedon: -1 }).limit(10);

				if (req.query.type && req.query.type === 'course') {
					recentlyUpdated = Course.find(
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
						.limit(10);
				} else if (req.query.type && req.query.type === 'dataset') {
					recentlyUpdated = Data.find(
						{
							$and: [
								{
									type: req.query.type,
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
							'timestamps.updated': 1,
						}
					)
						.sort({ 'timestamps.updated': -1, name: 1 })
						.limit(10);
				} else if (req.query.type && req.query.type !== 'course' && req.query.type !== 'dataset') {
					recentlyUpdated = Data.find(
						{
							$and: [
								{
									type: req.query.type,
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
						.limit(10);
				}

				recentlyUpdated.exec((err, data) => {
					if (err) return res.json({ success: false, error: err });
					return res.json({ success: true, data: data });
				});
				break;

			case 'unmet':
				switch (req.query.type) {
					case 'Datasets':
						req.entity = 'dataset';
						await getUnmetSearches(req)
							.then(data => {
								return res.json({ success: true, data: data });
							})
							.catch(err => {
								return res.json({ success: false, error: err });
							});
						break;

					case 'Tools':
						req.entity = 'tool';
						await getUnmetSearches(req)
							.then(data => {
								return res.json({ success: true, data: data });
							})
							.catch(err => {
								return res.json({ success: false, error: err });
							});
						break;

					case 'Projects':
						req.entity = 'project';
						await getUnmetSearches(req)
							.then(data => {
								return res.json({ success: true, data: data });
							})
							.catch(err => {
								return res.json({ success: false, error: err });
							});
						break;

					case 'Courses':
						req.entity = 'course';
						await getUnmetSearches(req)
							.then(data => {
								return res.json({ success: true, data: data });
							})
							.catch(err => {
								return res.json({ success: false, error: err });
							});
						break;

					case 'Papers':
						req.entity = 'paper';
						await getUnmetSearches(req)
							.then(data => {
								return res.json({ success: true, data: data });
							})
							.catch(err => {
								return res.json({ success: false, error: err });
							});
						break;

					case 'People':
						req.entity = 'person';
						await getUnmetSearches(req)
							.then(data => {
								return res.json({ success: true, data: data });
							})
							.catch(err => {
								return res.json({ success: false, error: err });
							});
						break;
				}
		}
	} catch (err) {
		process.stdout.write(`STATS - GET STATS : ${err.message}\n`);
		return res.json({ success: false, error: err.message });
	}
});

router.get('/topSearches', async (req, res) => {
	await getTopSearches(req)
		.then(data => {
			return res.json({ success: true, data: data });
		})
		.catch(err => {
			return res.json({ success: false, error: err });
		});
});

module.exports = router;

const getTopSearches = async (req, res) => {
	return new Promise(async (resolve, reject) => {
		let searchMonth = parseInt(req.query.month);
		let searchYear = parseInt(req.query.year);

		let q = RecordSearchData.aggregate([
			{ $addFields: { month: { $month: '$createdAt' }, year: { $year: '$createdAt' } } },
			{
				$match: {
					$and: [{ month: searchMonth }, { year: searchYear }, { searched: { $ne: '' } }],
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
						getObjectResult('dataset', searchQuery),
						getObjectResult('tool', searchQuery),
						getObjectResult('project', searchQuery),
						getObjectResult('paper', searchQuery),
						getObjectResult('course', searchQuery),
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
};

function getObjectResult(type, searchQuery) {
	var newSearchQuery = JSON.parse(JSON.stringify(searchQuery));
	newSearchQuery['$and'].push({ type: type });
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

const getUnmetSearches = async (req, res) => {
	return new Promise(async (resolve, reject) => {
		let searchMonth = parseInt(req.query.month);
		let searchYear = parseInt(req.query.year);
		let entitySearch = { ['returned.' + req.entity]: { $lte: 0 } };
		let q = RecordSearchData.aggregate([
			{ $addFields: { month: { $month: '$createdAt' }, year: { $year: '$createdAt' } } },
			{
				$match: {
					$and: [{ month: searchMonth }, { year: searchYear }, entitySearch, { searched: { $ne: '' } }],
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
					entity: { $max: req.entity },
				},
			},
			{ $sort: { count: -1 } },
		]).limit(10);

		q.exec((err, data) => {
			if (err) reject(err);
			return resolve(data);
		});
	});
};
