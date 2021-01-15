import express from 'express';
import { RecordSearchData } from '../search/record.search.model';
import { Data } from '../tool/data.model';
import { DataRequestModel } from '../datarequests/datarequests.model';
import { getHdrDatasetId } from './kpis.router';

const router = express.Router();

/**
 * {get} /stats get some basic high level stats
 *
 * This will return a JSON document to show high level stats
 */
router.get('', async (req, res) => {
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
			];

			var y = DataRequestModel.aggregate(aggregateAccessRequests);

			q.exec((err, dataSearches) => {
				if (err) return res.json({ success: false, error: err });

				var x = Data.aggregate(aggregateQueryTypes);
				x.exec((errx, dataTypes) => {
					if (errx) return res.json({ success: false, error: errx });

					var counts = {}; //hold the type (i.e. tool, person, project, access requests) counts data
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
			var q = Data.find({ counter: { $gt: 0 } })
				.sort({ counter: -1 })
				.limit(10);

			if (req.query.type) {
				q = Data.find({ $and: [{ type: req.query.type, counter: { $gt: 0 } }] })
					.sort({ counter: -1 })
					.limit(10);
			}

			q.exec((err, data) => {
				if (err) return res.json({ success: false, error: err });
				return res.json({ success: true, data: data });
			});
			break;

		case 'updates':
			var q = Data.find({ activeflag: 'active', counter: { $gt: 0 } })
				.sort({ updatedon: -1 })
				.limit(10);

			if (req.query.type) {
				q = Data.find({ $and: [{ type: req.query.type, activeflag: 'active', updatedon: { $gt: 0 } }] })
					.sort({ counter: -1 })
					.limit(10);
			}

			q.exec((err, data) => {
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
