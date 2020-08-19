import express from "express";
import axios from "axios";
import { RecordSearchData } from "../search/record.search.model";
import { Data } from "../tool/data.model";
import { DataRequestModel } from "../datarequests/datarequests.model";

const router = express.Router();

router.get("", async (req, res) => {
	var selectedMonthStart = new Date(req.query.selectedDate);
	selectedMonthStart.setMonth(selectedMonthStart.getMonth());
	selectedMonthStart.setDate(1);
	selectedMonthStart.setHours(0, 0, 0, 0);

	var selectedMonthEnd = new Date(req.query.selectedDate);
	selectedMonthEnd.setMonth(selectedMonthEnd.getMonth() + 1);
	selectedMonthEnd.setDate(0);
	selectedMonthEnd.setHours(23, 59, 59, 999);

	switch (req.query.kpi) {
		case "technicalmetadata":
			var result = [];
			var totalDatasets = 0;
			var datasetsMetadata = 0;

			axios
				.get(
					"https://raw.githubusercontent.com/HDRUK/datasets/master/datasets.csv"
				)
				.then(function (csv) {
					var lines = csv.data.split("\r\n");

					var commaRegex = /,(?=(?:[^\"]*\"[^\"]*\")*[^\"]*$)/g;

					var quotesRegex = /^"(.*)"$/g;

					var headers = lines[0]
						.split(commaRegex)
						.map((h) => h.replace(quotesRegex, "$1"));

					for (var i = 1; i < lines.length - 1; i++) {
						var obj = {};
						var currentline = lines[i].split(commaRegex);

						for (var j = 0; j < headers.length; j++) {
							obj[headers[j]] = currentline[j].replace(quotesRegex, "$1");
						}

						const publisher = "HDR UK";
						if (obj.publisher !== publisher) {
							result.push(obj);
						}
					}

					result.map((res) => {
						if (res.dataClassesCount !== "0") {
							datasetsMetadata++;
						}
					});

					totalDatasets = result.length;

					return res.json({
						success: true,
						data: {
							totalDatasets: totalDatasets,
							datasetsMetadata: datasetsMetadata,
						},
					});
				});
			break;

		case "searchanddar":
			var result;

			var aggregateQuerySearches = [
				{
					$facet: {
						totalMonth: [
							{
								$match: {
									datesearched: {
										$gte: selectedMonthStart,
										$lt: selectedMonthEnd,
									},
								},
							},

							{
								$group: {
									_id: "totalMonth",
									count: { $sum: 1 },
								},
							},
						],
						noResultsMonth: [
							{
								$match: {
									$and: [
										{
											datesearched: {
												$gte: selectedMonthStart,
												$lt: selectedMonthEnd,
											},
										},
										{ "returned.dataset": 0 },
										{ "returned.tool": 0 },
										{ "returned.project": 0 },
										{ "returned.paper": 0 },
										{ "returned.person": 0 },
									],
								},
							},
							{
								$group: {
									_id: "noResultsMonth",
									count: { $sum: 1 },
								},
							},
						],
						accessRequestsMonth: [
							//used only createdAt first { "$match": { "createdAt": {"$gte": selectedMonthStart, "$lt": selectedMonthEnd} } },
							// some older fields only have timeStamp --> only timeStamp in the production db
							//checking for both currently
							{
								$match: {
									$and: [
										{
											$or: [
												{
													createdAt: {
														$gte: selectedMonthStart,
														$lt: selectedMonthEnd,
													},
												},
												{
													timeStamp: {
														$gte: selectedMonthStart,
														$lt: selectedMonthEnd,
													},
												},
											],
										},
										{ applicationStatus: "submitted" },
									],
								},
							},
							{
								$group: {
									_id: "accessRequestsMonth",
									count: { $sum: 1 },
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

				if (typeof dataSearches[0].totalMonth[0] === "undefined") {
					dataSearches[0].totalMonth[0] = { count: 0 };
				}
				if (typeof dataSearches[0].noResultsMonth[0] === "undefined") {
					dataSearches[0].noResultsMonth[0] = { count: 0 };
				}

				y.exec((err, accessRequests) => {
					if (err) return res.json({ success: false, error: err });

					if (typeof accessRequests[0].accessRequestsMonth[0] === "undefined") {
						accessRequests[0].accessRequestsMonth[0] = { count: 0 };
					}

					result = res.json({
						success: true,
						data: {
							totalMonth: dataSearches[0].totalMonth[0].count,
							noResultsMonth: dataSearches[0].noResultsMonth[0].count,
							accessRequestsMonth:
								accessRequests[0].accessRequestsMonth[0].count,
						},
					});
				});
			});

			return result;
			break;

		case "uptime":
			const monitoring = require("@google-cloud/monitoring");
			const projectId = "hdruk-gateway";
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
						seconds: "86400s",
					},
					crossSeriesReducer: "REDUCE_NONE",
					groupByFields: [
						'metric.label."checker_location"',
						'resource.label."instance_id"',
					],
					perSeriesAligner: "ALIGN_FRACTION_TRUE",
				},
			};

			// Writes time series data
			const [timeSeries] = await client.listTimeSeries(request);
			var dailyUptime = [];
			var averageUptime;

			timeSeries.forEach((data) => {
				data.points.forEach((data) => {
					dailyUptime.push(data.value.doubleValue);
				});

				averageUptime =
					(dailyUptime.reduce((a, b) => a + b, 0) / dailyUptime.length) * 100;

				result = res.json({
					success: true,
					data: averageUptime,
				});
			});

			return result;
			break;
	}
});

module.exports = router;
