import express from 'express';
import { Data } from '../../tool/data.model';
import { loadDataset, loadDatasets } from './dataset.service';
import { getAllTools } from '../../tool/data.repository';
import _ from 'lodash';
import escape from 'escape-html';
import { Course } from '../../course/course.model';
import { filtersService } from '../../filters/dependency';
import * as Sentry from '@sentry/node';
const router = express.Router();
const rateLimit = require('express-rate-limit');

const datasetLimiter = rateLimit({
	windowMs: 60 * 60 * 1000, // 1 hour window
	max: 10, // start blocking after 10 requests
	message: 'Too many calls have been made to this api from this IP, please try again after an hour',
});

router.post('/', async (req, res) => {
	try {
		//Check to see if header is in json format
		let parsedBody = {};
		if (req.header('content-type') === 'application/json') {
			parsedBody = req.body;
		} else {
			parsedBody = JSON.parse(req.body);
		}
		//Check for key
		if (parsedBody.key !== process.env.cachingkey) {
			return res.status(400).json({ success: false, error: 'Caching could not be started' });
		}

		if (parsedBody.error === true) {
			throw new Error('cache error test');
		}

		filtersService.optimiseFilters('dataset');
		// loadDatasets(parsedBody.override || false).then(() => {
		// 	filtersService.optimiseFilters('dataset');
		// });

		return res.status(200).json({ success: true, message: 'Caching started' });
	} catch (err) {
		Sentry.captureException(err);
		console.error(err.message);
		return res.status(500).json({ success: false, message: 'Caching failed' });
	}
});

// @router   GET /api/v1/datasets/pidList
// @desc     Returns List of PIDs with linked datasetIDs
// @access   Public
router.get('/pidList/', datasetLimiter, async (req, res) => {
	var q = Data.find({ type: 'dataset', pid: { $exists: true } }, { pid: 1, datasetid: 1 }).sort({ pid: 1 });

	q.exec((err, data) => {
		var listOfPIDs = [];

		data.forEach(item => {
			if (listOfPIDs.find(x => x.pid === item.pid)) {
				var index = listOfPIDs.findIndex(x => x.pid === item.pid);
				listOfPIDs[index].datasetIds.push(item.datasetid);
			} else {
				listOfPIDs.push({ pid: item.pid, datasetIds: [item.datasetid] });
			}
		});

		return res.json({ success: true, data: listOfPIDs });
	});
});

// @router   GET /api/v1/
// @desc     Returns a dataset based on either datasetID or PID provided
// @access   Public
router.get('/:datasetID', async (req, res) => {
	let { datasetID = '' } = req.params;
	if (_.isEmpty(datasetID)) {
		return res.status(400).json({ success: false });
	}

	let isLatestVersion = true;
	let isDatasetArchived = false;

	// try to find the dataset using the datasetid
	let dataVersion = await Data.findOne({ datasetid: datasetID });

	// if found then set the datasetID to the pid of the found dataset
	if (!_.isNil(dataVersion)) {
		datasetID = dataVersion.pid;
	}

	// find the active dataset using the pid
	let dataset = await Data.findOne({ pid: datasetID, activeflag: 'active' });

	if (_.isNil(dataset)) {
		// if no active version found look for the next latest version using the pid and set the isDatasetArchived flag to true
		dataset = await Data.findOne({ pid: datasetID, activeflag: 'archive' }).sort({ createdAt: -1 });
		if (_.isNil(dataset)) {
			try {
				// if still not found then look up the MDC for the dataset
				dataset = await loadDataset(datasetID);
			} catch (err) {
				return res.status(404).send(`Dataset not found for Id: ${escape(datasetID)}`);
			}
		} else {
			isDatasetArchived = true;
		}
		isLatestVersion = dataset.activeflag === 'active';
	}

	let pid = dataset.pid;

	// get a list of all the datasetids connected to a pid
	let dataVersions = await Data.find({ pid }, { _id: 0, datasetid: 1 });
	let dataVersionsArray = dataVersions.map(a => a.datasetid);
	dataVersionsArray.push(pid);

	// find the related resources using the pid or datasetids for legacy entries
	let relatedData = await Data.find({
		relatedObjects: {
			$elemMatch: {
				$or: [
					{
						objectId: { $in: dataVersionsArray },
					},
					{
						pid: pid,
					},
				],
			},
		},
		activeflag: 'active',
	});

	let relatedDataFromCourses = await Course.find({
		relatedObjects: {
			$elemMatch: {
				$or: [
					{
						objectId: { $in: dataVersionsArray },
					},
					{
						pid: pid,
					},
				],
			},
		},
		activeflag: 'active',
	});

	relatedData = [...relatedData, ...relatedDataFromCourses];

	relatedData.forEach(dat => {
		dat.relatedObjects.forEach(relatedObject => {
			if ((relatedObject.objectId === dataset.datasetid && dat.id !== dataset.datasetid) || (relatedObject.pid === pid && dat.id !== pid)) {
				if (typeof dataset.relatedObjects === 'undefined') dataset.relatedObjects = [];
				dataset.relatedObjects.push({
					objectId: dat.id,
					reason: relatedObject.reason,
					objectType: dat.type,
					user: relatedObject.user,
					updated: relatedObject.updated,
				});
			}
		});
	});

	return res.json({ success: true, isLatestVersion, isDatasetArchived, data: dataset });
});

// @router   GET /api/v1/
// @desc     Returns List of Dataset Objects No auth
//           This unauthenticated route was created specifically for API-docs
// @access   Public
router.get('/', async (req, res) => {
	req.params.type = 'dataset';
	await getAllTools(req)
		.then(data => {
			return res.json({ success: true, data });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

module.exports = router;
