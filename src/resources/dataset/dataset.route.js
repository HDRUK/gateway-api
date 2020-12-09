import express from 'express'
import { Data } from '../tool/data.model'
import { loadDataset, loadDatasets } from './dataset.service';
import { getToolsAdmin } from '../tool/data.repository';
import _ from 'lodash';
const router = express.Router();
const rateLimit = require("express-rate-limit");

const datasetLimiter = rateLimit({
    windowMs: 60 * 60 * 1000, // 1 hour window
    max: 10, // start blocking after 10 requests
    message: "Too many calls have been made to this api from this IP, please try again after an hour"
});

router.post('/', async (req, res) => {
    //Check to see if header is in json format
    var parsedBody = {}
    if (req.header('content-type') === 'application/json') {
        parsedBody = req.body;
    } else {
        parsedBody = JSON.parse(req.body);
    }
    //Check for key
    if (parsedBody.key !== process.env.cachingkey) {
        return res.json({ success: false, error: "Caching failed" });
    }

    loadDatasets(parsedBody.override || false);
    return res.json({ success: true, message: "Caching started" });
});

// @router   GET /api/v1/datasets/pidList
// @desc     Returns List of PIDs with linked datasetIDs
// @access   Public
router.get(
    '/pidList/',
    datasetLimiter,
    async (req, res) => {
        var q = Data.find(
            { "type" : "dataset", "pid" : { "$exists" : true } }, 
            { "pid" : 1, "datasetid" : 1 }
        ).sort({ "pid" : 1 });
        
        q.exec((err, data) => {
            var listOfPIDs = []
            
            data.forEach((item) => {
                if (listOfPIDs.find(x => x.pid === item.pid)) {
                    var index = listOfPIDs.findIndex(x => x.pid === item.pid)
                    listOfPIDs[index].datasetIds.push(item.datasetid)
                }
                else {
                    listOfPIDs.push({"pid":item.pid, "datasetIds":[item.datasetid]})
                }
            
            })

            return res.json({ success: true, data: listOfPIDs });
        })        
    }
);

// @router   GET /api/v1/
// @desc     Returns a dataset based on either datasetID or PID provided
// @access   Public
router.get('/:datasetID', async (req, res) => {

    let { datasetID = ''} = req.params; 
    if(_.isEmpty(datasetID)) { 
        return res.status(400).json({ success: false })
    };

    let isLatestVersion = true;
    
    // Search for a dataset based on pid
    let dataset = await Data.findOne({ pid: datasetID, activeflag: 'active'}); 

    if(!_.isNil(dataset)){ 
        // Set the actual datasetId value based on pid provided 
        datasetID = dataset.datasetid; 
    }
    else{
        // Search for a dataset based on datasetID
        dataset = await Data.findOne({ datasetid: datasetID}); 
        
        // Pull a dataset version from MDC if it doesn't exist on our DB
        if(_.isNil(dataset)){ 
            dataset = await loadDataset(datasetID)
        }

        isLatestVersion = (dataset.activeflag === 'active');
    }

    let pid = dataset.pid;
    let relatedData = await Data.find({ "relatedObjects": { $elemMatch: { "objectId": {$in : [datasetID, pid] } } } });

    relatedData.forEach((dat) => {
        dat.relatedObjects.forEach((relatedObject) => {
            if ((relatedObject.objectId === datasetID && dat.id !== datasetID) || (relatedObject.objectId === pid && dat.id !== pid)){
                if (typeof dataset.relatedObjects === "undefined") dataset.relatedObjects=[];
                dataset.relatedObjects.push({ objectId: dat.id, reason: relatedObject.reason, objectType: dat.type, user: relatedObject.user, updated: relatedObject.updated })
            }
        })
    });

    return res.json({ success: true, isLatestVersion: isLatestVersion, data: dataset });
});

// @router   GET /api/v1/
// @desc     Returns List of Dataset Objects No auth
//           This unauthenticated route was created specifically for API-docs
// @access   Public
router.get(
    '/',
    async (req, res) => {
      req.params.type = 'dataset';
        await getToolsAdmin(req)
          .then((data) => {
            return res.json({ success: true, data });
          })
          .catch((err) => {
            return res.json({ success: false, err });
          });
    }
  );

module.exports = router;