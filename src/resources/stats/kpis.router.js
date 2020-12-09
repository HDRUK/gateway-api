import express from 'express';
import { RecordSearchData } from '../search/record.search.model';
import { Data } from '../tool/data.model';
import {DataRequestModel} from '../datarequests/datarequests.model';

const router = express.Router()

router.get('', async (req, res) => { 

    var selectedMonthStart = new Date(req.query.selectedDate);
    selectedMonthStart.setMonth(selectedMonthStart.getMonth());
    selectedMonthStart.setDate(1);
    selectedMonthStart.setHours(0,0,0,0);

    var selectedMonthEnd = new Date(req.query.selectedDate);
    selectedMonthEnd.setMonth(selectedMonthEnd.getMonth()+1);
    selectedMonthEnd.setDate(0); 
    selectedMonthEnd.setHours(23,59,59,999);

    switch (req.query.kpi) {
      case 'technicalmetadata':
        var totalDatasetsQuery = [
          {
            $facet: {
              TotalDataSets: [
                {
                  $match: {
                    $and: [
                      { activeflag: "active" },
                      { type: "dataset" },
                      { "datasetfields.publisher": { $ne: "OTHER > HEALTH DATA RESEARCH UK" } },
                      { "datasetfields.publisher": { $ne: "HDR UK" } },
                    ],
                  },
                },
                { $count: "TotalDataSets" },
              ],
              TotalMetaData: [
                {
                  $match: {
                    activeflag: "active",
                    type: "dataset",
                    "datasetfields.technicaldetails": {
                      $exists: true,
                      $not: {
                        $size: 0,
                      },
                    },
                  },
                },
                {
                  $count: "TotalMetaData",
                },
              ],
            },
          },
        ];

        var q = Data.aggregate(totalDatasetsQuery);

        var result;
        q.exec((err, dataSets) => {
          if (err) return res.json({ success: false, error: err });

          if (typeof dataSets[0].TotalDataSets[0] === "undefined") {
            dataSets[0].TotalDataSets[0].TotalDataSets = 0;
          }
          if (typeof dataSets[0].TotalMetaData[0] === "undefined") {
            dataSets[0].TotalMetaData[0].TotalMetaData = 0;
          }

          result = res.json({
            success: true,
            data: {
              totalDatasets: dataSets[0].TotalDataSets[0].TotalDataSets,
              datasetsMetadata: dataSets[0].TotalMetaData[0].TotalMetaData,
            },
          });
        });

        return result;
      break;

      case 'searchanddar':
        var result;

        var aggregateQuerySearches = [
          {
            $facet: {
              "totalMonth": [
                { "$match": { "datesearched": {"$gte": selectedMonthStart, "$lt": selectedMonthEnd} } },

                {
                  $group: {
                    _id: 'totalMonth',
                    count: { $sum: 1 }
                  },
                }
              ],
              "noResultsMonth": [
                { "$match": { $and: [{"datesearched": {"$gte": selectedMonthStart, "$lt": selectedMonthEnd} }, {"returned.dataset": 0}, {"returned.tool": 0}, {"returned.project": 0}, {"returned.paper": 0}, {"returned.person": 0} ] } },
                {
                  $group: {
                    _id: 'noResultsMonth',
                    count: { $sum: 1 }
                  }, 
                }
              ],
              "accessRequestsMonth": [
                //used only createdAt first { "$match": { "createdAt": {"$gte": selectedMonthStart, "$lt": selectedMonthEnd} } },
                // some older fields only have timeStamp --> only timeStamp in the production db
                //checking for both currently
                { $match: {
                    $and: [
                      { 
                        $or: [  
                          { "createdAt": {"$gte": selectedMonthStart, "$lt": selectedMonthEnd} },
                          { "timeStamp": {"$gte": selectedMonthStart, "$lt": selectedMonthEnd} } 
                        ] 
                      },
                      {
                        $or: [
                          {"applicationStatus":"submitted"}, 
                          {"applicationStatus":"approved"}, 
                          {"applicationStatus":"rejected"},
                          {"applicationStatus":"approved with conditions"}
                        ]
                      }
                    ] 
                  }
                },
                {
                  $lookup: {
                    from: "tools",
                    localField: "dataSetId",
                    foreignField: "datasetid",
                    as: "publisher",
                  },
                },
                { $match: { $and: [
                    {"publisher.datasetfields.publisher": { $ne: "OTHER > HEALTH DATA RESEARCH UK" } }, 
                    {"publisher.datasetfields.publisher": { $ne: "HDR UK" } } 
                ]}},
                { $group: { _id: 'accessRequestsMonth', count: { $sum: 1 } }, }
              ],
            }
          }];

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

              result = res.json(
                {
                  'success': true, 'data':
                  {
                      'totalMonth': dataSearches[0].totalMonth[0].count,
                      'noResultsMonth': dataSearches[0].noResultsMonth[0].count,
                      'accessRequestsMonth': accessRequests[0].accessRequestsMonth[0].count      
                    }
                  }
              )
          });
        });
  
        return result;
      break;

      case 'uptime':
        const monitoring = require('@google-cloud/monitoring');
        const projectId = 'hdruk-gateway';
        const client = new monitoring.MetricServiceClient();

        var result;
      
        const request = {
          name: client.projectPath(projectId),
          filter: 'metric.type="monitoring.googleapis.com/uptime_check/check_passed" AND resource.type="uptime_url" AND metric.label."check_id"="check-production-web-app-qsxe8fXRrBo" AND metric.label."checker_location"="eur-belgium"',
          
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
            groupByFields: [
              'metric.label."checker_location"',
              'resource.label."instance_id"'
            ],
            perSeriesAligner: 'ALIGN_FRACTION_TRUE',
          },

        };

        // Writes time series data
        const [timeSeries] = await client.listTimeSeries(request);
        var dailyUptime = [];
        var averageUptime;

        timeSeries.forEach(data => {
        
            data.points.forEach(data => { 
              dailyUptime.push(data.value.doubleValue)
            })

            averageUptime = (dailyUptime.reduce((a, b) => a + b, 0) / dailyUptime.length) * 100;

            result = res.json(
              {
                'success': true, 'data': averageUptime
                }
            )
        });
      
        return result;
      break; 
 
      case 'topdatasets':

      let DarInfoMap = new Map()

      let hdrDatasetID = await getHdrDatasetId()

          await getDarIds(req, selectedMonthStart, selectedMonthEnd)
            .then(async (data) => {

              for (let datasetIdObject in data) { 
                if(data[datasetIdObject].datasetIds && data[datasetIdObject].datasetIds.length > 0){

                  for (let datasetId in data[datasetIdObject].datasetIds) { 

                    if(data[datasetIdObject].datasetIds[datasetId] !== hdrDatasetID[0].datasetid){

                      let result = await getDarInfo(data[datasetIdObject].datasetIds[datasetId])

                      if(result.length > 0){
                        if (DarInfoMap.has(data[datasetIdObject].datasetIds[datasetId])){ 
                          let count = DarInfoMap.get(data[datasetIdObject].datasetIds[datasetId])
                          count.requests++
                          DarInfoMap.set(data[datasetIdObject].datasetIds[datasetId], {"requests": count.requests, "name": result[0].name, "publisher": result[0].datasetfields.publisher});
                          } else {
                            DarInfoMap.set(data[datasetIdObject].datasetIds[datasetId], {"requests": 1, "name": result[0].name, "publisher": result[0].datasetfields.publisher});
                          }
                      }
                    }
                  }
                } 
                else 
                if(data[datasetIdObject].dataSetId && data[datasetIdObject].dataSetId.length > 0 && data[datasetIdObject].dataSetId !== hdrDatasetID[0].datasetid ){
                  let result = await getDarInfo(data[datasetIdObject].dataSetId)
                  if(result.length > 0){
                    if (DarInfoMap.has(data[datasetIdObject].dataSetId)){ 
                      let count = DarInfoMap.get(data[datasetIdObject].dataSetId)
                      count.requests++
                      DarInfoMap.set(data[datasetIdObject].dataSetId, {"requests": count.requests, "name": result[0].name, "publisher": result[0].datasetfields.publisher});
                      } else {
                        DarInfoMap.set(data[datasetIdObject].dataSetId, {"requests": 1, "name": result[0].name, "publisher": result[0].datasetfields.publisher});
                      }
                  }
                }
            }
          })
          .catch((err) => {
            return res.json({ success: false, error: err });
          }); 

        let sortedResults = Array.from(DarInfoMap).sort((a,b) => { return b[1].requests - a[1].requests})

        sortedResults = sortedResults.slice(0, 5)

        return res.json({ success: true, data: sortedResults });

      break;
    }
  });

module.exports = router

const getHdrDatasetId = async() => {
  return new Promise(async (resolve, reject) => {
    let hdrDatasetID = Data.find(
      {   
          "datasetfields.publisher": "OTHER > HEALTH DATA RESEARCH UK" 
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
    })
}

const getDarIds = async(req, selectedMonthStart, selectedMonthEnd) => {
  return new Promise(async (resolve, reject) => {

    let DarDatasetIds = DataRequestModel.find(
      { 
        // VALUES YOU ARE CHECKING MATCH SPECIFIED CRITERIA IE. WHERE
        $and: [
          {
            $or: [
              {
                createdAt: {
                  $gte: selectedMonthStart,
                  $lt: selectedMonthEnd
                }
              },
              {
                timeStamp: {
                  $gte: selectedMonthStart,
                  $lt: selectedMonthEnd
                }
              }
            ]
          },
          {
            $or: [
              { applicationStatus: "submitted" },
              { applicationStatus: "approved" },
              { applicationStatus: "rejected" },
              {applicationStatus: "approved with conditions" }

            ]
          }
        ]
      },
      {
        // THE FIELDS YOU WANT TO RETURN
        _id: 0,
        dataSetId: 1, 
        datasetIds: 1
      }
    );

    DarDatasetIds.exec((err, data) => {
      if (err) reject(err);
      return resolve(data);
    });

  });
}

const getDarInfo = async(id) => { 
return new Promise(async (resolve, reject) => {
  let DarDatasetInfo = Data.find(
    { 
        datasetid: id  
      },
      {
        _id: 0,
        datasetid: 1,
        name: 1, 
        //RETURN EMBEDDED FIELD
        "datasetfields.publisher": 1
      }
    );

      DarDatasetInfo.exec((err, data) => {
        if (err) reject(err);
        else resolve(data);
      });
  })
}
