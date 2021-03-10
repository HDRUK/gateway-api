import express from 'express';
import { RecordSearchData } from '../search/record.search.model';
import { Data } from '../tool/data.model';
import { DataRequestModel } from '../datarequests/datarequests.model';

const router = express.Router();

router.get('', async (req, res) => {
	try {
		var selectedMonthStart = new Date(req.query.selectedDate);
		selectedMonthStart.setMonth(selectedMonthStart.getMonth());
		selectedMonthStart.setDate(1);
		selectedMonthStart.setHours(0, 0, 0, 0);

		var selectedMonthEnd = new Date(req.query.selectedDate);
		selectedMonthEnd.setMonth(selectedMonthEnd.getMonth() + 1);
		selectedMonthEnd.setDate(0);
		selectedMonthEnd.setHours(23, 59, 59, 999);

		switch (req.query.kpi) {
			case 'technicalmetadata':
				const technicalMetadataResults = await Data.aggregate([
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

				return res.json({
					success: true,
					data: {
						totalDatasets: technicalMetadataResults[0].TotalDataSets[0].TotalDataSets || 0,
						datasetsMetadata: technicalMetadataResults[0].TotalMetaData[0].TotalMetaData || 0,
					},
				});

			case 'searchanddar':
				var result;

				var aggregateQuerySearches = [
					{
						$facet: {
							totalMonth: [
								{ $match: { datesearched: { $gte: selectedMonthStart, $lt: selectedMonthEnd } } },

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
											{ datesearched: { $gte: selectedMonthStart, $lt: selectedMonthEnd } },
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
							accessRequestsMonth: [
								{
									$match: {
										dateSubmitted: { $gte: selectedMonthStart, $lt: selectedMonthEnd },
										applicationStatus: { $in: ['submitted', 'approved', 'rejected', 'inReview', 'approved with conditions'] },
									},
								},
							],
						},
					},
				];

				var q = RecordSearchData.aggregate(aggregateQuerySearches);

				var y = DataRequestModel.aggregate(aggregateQuerySearches);

				q.exec((err, dataSearches) => {
					if (err) return res.json({ success: false, error: err });

					if (typeof dataSearches[0].totalMonth[0] === 'undefined') {
						dataSearches[0].totalMonth[0] = { count: 0 };
					}
					if (typeof dataSearches[0].noResultsMonth[0] === 'undefined') {
						dataSearches[0].noResultsMonth[0] = { count: 0 };
					}

					y.exec(async (err, accessRequests) => {
						let hdrDatasetID = await getHdrDatasetId();
						let hdrDatasetIds = [];
						hdrDatasetID.map(hdrDatasetid => {
							hdrDatasetIds.push(hdrDatasetid.datasetid);
						});
						let accessRequestsMonthCount = 0;

						if (err) return res.json({ success: false, error: err });

						accessRequests[0].accessRequestsMonth.map(accessRequest => {
							if (accessRequest.datasetIds && accessRequest.datasetIds.length > 0) {
								accessRequest.datasetIds.map(datasetid => {
									if (!hdrDatasetIds.includes(datasetid)) {
										accessRequestsMonthCount++;
									}
								});
							}
						});

						result = res.json({
							success: true,
							data: {
								totalMonth: dataSearches[0].totalMonth[0].count,
								noResultsMonth: dataSearches[0].noResultsMonth[0].count,
								accessRequestsMonth: accessRequestsMonthCount,
							},
						});
					});
				});

				return result;

			case 'uptime':
				const monitoring = require('@google-cloud/monitoring');
				const projectId = 'hdruk-gateway';
				const client = new monitoring.MetricServiceClient();

				var result;

				const request = {
					name: client.projectPath(projectId),
					filter:
						'metric.type="monitoring.googleapis.com/uptime_check/check_passed" AND resource.type="uptime_url" AND metric.label."check_id"="check-production-web-app-qsxe8fXRrBo" AND metric.label."checker_location"="eur-belgium"',

					interval: {
						startTime: {
							seconds: selectedMonthStart.getTime() / 1000,
						},
						endTime: {
							seconds: selectedMonthEnd.getTime() / 1000,
						},
					},
					aggregation: {
						alignmentPeriod: {
							seconds: '86400s',
						},
						crossSeriesReducer: 'REDUCE_NONE',
						groupByFields: ['metric.label."checker_location"', 'resource.label."instance_id"'],
						perSeriesAligner: 'ALIGN_FRACTION_TRUE',
					},
				};

				// Writes time series data
				const [timeSeries] = await client.listTimeSeries(request);
				var dailyUptime = [];
				var averageUptime;

				timeSeries.forEach(data => {
					data.points.forEach(data => {
						dailyUptime.push(data.value.doubleValue);
					});

					averageUptime = (dailyUptime.reduce((a, b) => a + b, 0) / dailyUptime.length) * 100;

					result = res.json({
						success: true,
						data: averageUptime,
					});
				});

				return result;

			case 'topdatasets':
				const topDatasetResults = await DataRequestModel.aggregate([
					{
						$match: {
							dateSubmitted: {
								$gte: selectedMonthStart,
								$lt: selectedMonthEnd,
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

				return res.json({
					success: true,
					data: topDatasetResults,
				});
		}
	} catch (err) {
		return res.json({ success: false, error: err.message });
	}
});

module.exports = router;

export const getHdrDatasetId = async () => {
	return new Promise(async (resolve, reject) => {
		let hdrDatasetID = Data.find(
			{
				'datasetfields.publisher': { $in: ['HDR UK', 'OTHER > HEALTH DATA RESEARCH UK'] },
			},
			{
				_id: 0,
				datasetid: 1,
			}
		);

		hdrDatasetID.exec((err, data) => {
			if (err) reject(err);
			else resolve(data);
		});
	});
};
