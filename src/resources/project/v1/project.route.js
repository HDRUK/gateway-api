/* eslint-disable no-undef */
import express from 'express';
import helper from '../../utilities/helper.util';
import escape from 'escape-html';
import passport from 'passport';

import { Data } from '../../tool/data.model';
import { ROLES } from '../../user/user.roles';
import { authUtils } from '../../../utils';
import { addTool, editTool, setStatus, getTools, getToolsAdmin, getAllTools } from '../../tool/data.repository';

const router = express.Router();

// @router   POST /api/v1/project
// @desc     Add project user
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

// @router   GET /api/v1/project/getList
// @desc     Returns List of Project Objects Authenticated
// @access   Private
router.get('/getList', passport.authenticate('jwt'), async (req, res) => {
	req.params.type = 'project';
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

// @router   GET /api/v1/project
// @desc     Returns List of Project Objects No auth
//           This unauthenticated route was created specifically for API-docs
// @access   Public
router.get('/', async (req, res) => {
	req.params.type = 'project';
	await getAllTools(req)
		.then(data => {
			return res.json({ success: true, data });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

// @router   GET /api/v1/project/{projectID}
// @desc     Returns a Project object
// @access   Public
router.get('/:projectID', async (req, res) => {
	var q = Data.aggregate([
		{
			$match: {
				$and: [{ id: parseInt(req.params.projectID) }, { type: 'project' }],
			},
		},
		{
			$lookup: {
				from: 'tools',
				localField: 'authors',
				foreignField: 'id',
				as: 'persons',
			},
		},
		{ $lookup: { from: 'tools', localField: 'uploader', foreignField: 'id', as: 'uploaderIs' } },
		{
			$addFields: {
				uploader: {
					$concat: [{ $arrayElemAt: ['$uploaderIs.firstname', 0] }, ' ', { $arrayElemAt: ['$uploaderIs.lastname', 0] }],
				},
				uploaderId: '$uploader',
			},
		},
	]);
	q.exec((err, data) => {
		if (err) return res.json({ success: false, error: err });

		if (data.length > 0) {
			data[0].persons = helper.hidePrivateProfileDetails(data[0].persons);
			var p = Data.aggregate([
				{
					$match: {
						$and: [
							{
								relatedObjects: {
									$elemMatch: { objectId: req.params.projectID },
								},
							},
						],
					},
				},
			]);

			p.exec(async (err, relatedData) => {
				relatedData.forEach(dat => {
					dat.relatedObjects.forEach(x => {
						if (x.objectId === req.params.projectID && dat.id !== req.params.projectID) {
							if (typeof data[0].relatedObjects === 'undefined') data[0].relatedObjects = [];
							data[0].relatedObjects.push({
								objectId: dat.id,
								reason: x.reason,
								objectType: dat.type,
								user: x.user,
								updated: x.updated,
							});
						}
					});
				});

				if (err) return res.json({ success: false, error: err });
				return res.json({ success: true, data: data });
			});
		} else {
			return res.status(404).send(`Project not found for Id: ${escape(req.params.projectID)}`);
		}
	});
});

// @router   PATCH /api/v1/project/status/{id}
// @desc     Set project status
// @access   Private
router.patch('/:id', passport.authenticate('jwt'), authUtils.checkAllowedToAccess('project'), async (req, res) => {
	await setStatus(req)
		.then(response => {
			return res.json({ success: true, response });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

// @router   PUT /api/v1/project/{id}
// @desc     Edit project
// @access   Private
router.put('/:id', passport.authenticate('jwt'), authUtils.checkAllowedToAccess('project'), async (req, res) => {
	await editTool(req)
		.then(response => {
			return res.json({ success: true, response });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

module.exports = router;
