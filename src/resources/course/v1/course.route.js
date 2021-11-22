import express from 'express';
import { ROLES } from '../../user/user.roles';
import { Data } from '../../tool/data.model';
import { Course } from '../course.model';
import passport from 'passport';
import { authUtils } from '../../../utils';
import { addCourse, editCourse, setStatus, getCourseAdmin, getCourse, getAllCourses } from '../course.repository';
import escape from 'escape-html';
const router = express.Router();

// @router   POST /api/v1/course
// @desc     Add Course as user
// @access   Private
router.post('/', passport.authenticate('jwt'), async (req, res) => {
	await addCourse(req)
		.then(response => {
			return res.json({ success: true, response });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

// @router   PUT /api/v1/course/{id}
// @desc     Edit Course as user
// @access   Private
router.put('/:id', passport.authenticate('jwt'), authUtils.checkAllowedToAccess('course'), async (req, res) => {
	await editCourse(req)
		.then(response => {
			return res.json({ success: true, response });
		})
		.catch(err => {
			return res.json({ success: false, error: err.message });
		});
});

// @router   GET /api/v1/course/getList
// @desc     Returns List of Course objects
// @access   Private
router.get('/getList', passport.authenticate('jwt'), async (req, res) => {
	let role = req.user.role;

	if (role === ROLES.Admin) {
		await getCourseAdmin(req)
			.then(data => {
				return res.json({ success: true, data });
			})
			.catch(err => {
				return res.json({ success: false, err });
			});
	} else if (role === ROLES.Creator) {
		await getCourse(req)
			.then(data => {
				return res.json({ success: true, data });
			})
			.catch(err => {
				return res.json({ success: false, err });
			});
	}
});

// @router   GET /api/v1/course
// @desc     Returns List of course Objects No auth
//           This unauthenticated route was created specifically for API-docs
// @access   Public
router.get('/', async (req, res) => {
	await getAllCourses(req)
		.then(data => {
			return res.json({ success: true, data });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

// @router   PATCH /api/v1/course/{id}
// @desc     Set course status
// @access   Private
router.patch('/:id', passport.authenticate('jwt'), authUtils.checkAllowedToAccess('course'), async (req, res) => {
	await setStatus(req)
		.then(response => {
			return res.json({ success: true, response });
		})
		.catch(err => {
			return res.json({ success: false, error: err.message });
		});
});

/**
 * {get} /api/v1/course/:id Course
 *
 * Return the details on the tool based on the course ID.
 */
router.get('/:id', async (req, res) => {
	let id = parseInt(req.params.id);
	var query = Course.aggregate([
		{ $match: { id: parseInt(req.params.id) } },
		{
			$lookup: {
				from: 'tools',
				localField: 'creator',
				foreignField: 'id',
				as: 'creator',
			},
		},
	]);
	query.exec((err, data) => {
		if (data.length > 0) {
			var p = Data.aggregate([
				{
					$match: {
						$and: [{ relatedObjects: { $elemMatch: { objectId: req.params.id } } }],
					},
				},
			]);
			p.exec((err, relatedData) => {
				relatedData.forEach(dat => {
					dat.relatedObjects.forEach(x => {
						if (x.objectId === req.params.id && dat.id !== req.params.id) {
							let relatedObject = {
								objectId: dat.id,
								reason: x.reason,
								objectType: dat.type,
								user: x.user,
								updated: x.updated,
							};
							data[0].relatedObjects = [relatedObject, ...(data[0].relatedObjects || [])];
						}
					});
				});

				if (err) return res.json({ success: false, error: err });

				return res.json({
					success: true,
					data: data,
				});
			});
		} else {
			return res.status(404).send(`Course not found for Id: ${escape(id)}`);
		}
	});
});

/**
 * {get} /api/v1/course/edit/:id Course
 *
 * Return the details on the course based on the course ID for edit.
 */
router.get('/edit/:id', async (req, res) => {
	var query = Course.aggregate([
		{ $match: { $and: [{ id: parseInt(req.params.id) }] } },
		{
			$lookup: {
				from: 'tools',
				localField: 'authors',
				foreignField: 'id',
				as: 'creator',
			},
		},
	]);
	query.exec((err, data) => {
		if (data.length > 0) {
			return res.json({ success: true, data: data });
		} else {
			return res.json({
				success: false,
				error: `Course not found for course id ${req.params.id}`,
			});
		}
	});
});

//Validation required if Delete is to be implemented
// router.delete('/:id',
//   passport.authenticate('jwt'),
//   authUtils.checkIsInRole(ROLES.Admin, ROLES.Creator),
//     async (req, res) => {
//       await deleteTool(req, res)
//         .then(response => {
//           return res.json({success: true, response});
//         })
//         .catch(err => {
//           res.status(204).send(err);
//         });
//     }
// );

// eslint-disable-next-line no-undef
module.exports = router;
