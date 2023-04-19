/* eslint-disable no-undef */
import express from 'express';
import { Data } from '../../tool/data.model';
import { Course } from '../../course/course.model';
import { DataUseRegister } from '../../dataUseRegister/dataUseRegister.model';
import { ROLES } from '../../user/user.roles';
import passport from 'passport';
import { utils } from '../../auth';
import { addTool, editTool, setStatus, getTools, getToolsAdmin, getAllTools, formatRetroDocumentLinks } from '../../tool/data.repository';
import helper from '../../utilities/helper.util';
import escape from 'escape-html';
const router = express.Router();

// @router   POST /api/v1/paper
// @desc     Add paper user
// @access   Private
router.post('/', passport.authenticate('jwt'), async (req, res) => {
	await addTool(req)
		.then(response => {
			return res.json({ success: true, response });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

// @router   GET /api/v1/paper/getList
// @desc     Returns List of Paper Objects Authenticated
// @access   Private
router.get('/getList', passport.authenticate('jwt'), async (req, res) => {
	req.params.type = 'paper';
	let role = req.user.role;

	if (role === ROLES.Admin) {
		await getToolsAdmin(req)
			.then(data => {
				return res.json({ success: true, data });
			})
			.catch(err => {
				return res.json({ success: false, err });
			});
	} else if (role === ROLES.Creator) {
		await getTools(req)
			.then(data => {
				return res.json({ success: true, data });
			})
			.catch(err => {
				return res.json({ success: false, err });
			});
	}
});

// @router   POST /api/v1/validate
// @desc     Validates that a paper link does not exist on the gateway
// @access   Private
router.post('/validate', passport.authenticate('jwt'), async (req, res) => {
	try {
		// 1. Deconstruct message body which contains the link entered by the user against a paper
		const { link } = req.body;
		// 2. Front end validation should prevent this occurrence, but we return success if empty string or not param is passed
		if (!link) {
			return res.status(200).json({ success: true });
		}
		// 3. Use MongoDb to perform a direct comparison on all paper links, trimming leading and trailing white space from the request body
		const papers = await Data.find({ type: 'paper', link: link.trim(), activeflag: { $ne: 'rejected' } }).count();
		// 4. If any results are found, return error that the link exists on the Gateway already
		if (papers > 0)
			return res
				.status(200)
				.json({ success: true, error: 'This link is already associated to another paper on the HDR-UK Innovation Gateway' });
		// 5. Otherwise return valid
		return res.status(200).json({ success: true });
	} catch (err) {
		process.stdout.write(`PAPER - validate : ${err.message}\n`);
		return res.status(500).json({ success: false, error: 'Paper link validation failed' });
	}
});

// @router   GET /api/v1/paper
// @desc     Returns List of Paper Objects No auth
//           This unauthenticated route was created specifically for API-docs
// @access   Public
router.get('/', async (req, res) => {
	req.params.type = 'paper';
	await getAllTools(req)
		.then(data => {
			return res.json({ success: true, data });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

// @router   PATCH /api/v1/paper/{id}
// @desc     Change status of the Paper object.
// @access   Private
router.patch('/:id', passport.authenticate('jwt'), utils.checkAllowedToAccess('paper'), async (req, res) => {
	await setStatus(req)
		.then(response => {
			return res.json({ success: true, response });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

// @router   PUT /api/v1/paper/{id}
// @desc     Returns edited Paper object.
// @access   Private
router.put('/:id', passport.authenticate('jwt'), utils.checkAllowedToAccess('paper'), async (req, res) => {
	await editTool(req)
		.then(response => {
			return res.json({ success: true, response });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

// @router   GET /api/v1/paper/{paperID}
// @desc     Return the details on the paper based on the tool ID.
// @access   Public
router.get('/:paperID', async (req, res) => {
	var q = Data.aggregate([
		{ $match: { $and: [{ id: parseInt(req.params.paperID) }, { type: 'paper' }] } },
		{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
		{ $lookup: { from: 'tools', localField: 'uploader', foreignField: 'id', as: 'uploaderIs' } },
		{
			$addFields: {
				uploader: {
					$concat: [{ $arrayElemAt: ['$uploaderIs.firstname', 0] }, ' ', { $arrayElemAt: ['$uploaderIs.lastname', 0] }],
				},
			},
		},
	]);
	q.exec(async (err, data) => {
		if (data.length > 0) {
			let relatedData = await Data.find({
				relatedObjects: { $elemMatch: { objectId: req.params.paperID } },
				activeflag: 'active',
			});

			let relatedDataFromCourses = await Course.find({
				relatedObjects: { $elemMatch: { objectId: req.params.paperID } },
				activeflag: 'active',
			});

			let relatedDataFromDatauses = await DataUseRegister.find({
				relatedObjects: { $elemMatch: { objectId: req.params.paperID } },
				activeflag: 'active',
			});

			relatedData = [...relatedData, ...relatedDataFromCourses, ...relatedDataFromDatauses];

			relatedData.forEach(dat => {
				dat.relatedObjects.forEach(x => {
					if (x.objectId === req.params.paperID && dat.id !== req.params.paperID) {
						if (typeof data[0].relatedObjects === 'undefined') data[0].relatedObjects = [];
						data[0].relatedObjects.push({ objectId: dat.id, reason: x.reason, objectType: dat.type, user: x.user, updated: x.updated });
					}
				});
			});
			if (err) return res.json({ success: false, error: err });

			data[0].persons = helper.hidePrivateProfileDetails(data[0].persons);
			if (Array.isArray(data[0].document_links)) {
				data[0].document_links = formatRetroDocumentLinks(data[0].document_links);
			}
			return res.json({ success: true, data: data });
		} else {
			return res.status(404).send(`Paper not found for Id: ${escape(req.params.paperID)}`);
		}
	});
});

// @router   GET /api/v1/paper/edit/{paperID}
// @desc     Return the details on the paper based on the paper ID without reverse linked related resources.
// @access   Public
router.get('/edit/:paperID', async (req, res) => {
	var query = Data.aggregate([
		{ $match: { $and: [{ id: parseInt(req.params.paperID) }] } },
		{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
	]);
	query.exec((err, data) => {
		if (data.length > 0) {
			if (Array.isArray(data[0].document_links)) {
				data[0].document_links = formatRetroDocumentLinks(data[0].document_links);
			}
			return res.json({ success: true, data: data });
		} else {
			return res.json({ success: false, error: `Paper not found for paper id ${req.params.id}` });
		}
	});
});

module.exports = router;
