import express from 'express';
import passport from 'passport';

import { utils } from '../auth';
import { UserModel } from './user.model';
import { ROLES } from './user.roles';
import { setCohortDiscoveryAccess, getUsers } from './user.service';
import { upperCase } from 'lodash';

import {
    checkInputMiddleware,
    checkMinLengthMiddleware,
} from '../../middlewares/index';

const router = express.Router();

// @router   GET /api/v1/users/:userID
// @desc     find user by id
// @access   Private
router.get('/:userID', passport.authenticate('jwt'), utils.checkIsUser(), async (req, res) => {
	//req.params.id is how you get the id from the url
	var q = UserModel.find({ id: req.params.userID });

	q.exec((err, userdata) => {
		if (err) return res.json({ success: false, error: err });
		return res.json({ success: true, userdata: userdata });
	});
});

// @router   GET /api/v1/users
// @desc     get all
// @access   Private
router.get('/', passport.authenticate('jwt'), async (req, res) => {
	let reqUserId = req.user.id;
	await getUsers(reqUserId)
		.then(response => {
			return res.json({ success: true, data: response });
		})
		.catch(err => {
			return new Error({ success: false, error: err });
		});
});

// @router   GET /api/v1/users/search/:filter
// @desc     get all filtered by text
// @access   Private
router.get('/search/:filter', passport.authenticate('jwt'), [checkInputMiddleware, checkMinLengthMiddleware], async (req, res) => {
	let filterString = req.params.filter;
	let reqUserId = req.user.id;
	await getUsers(reqUserId, filterString)
		.then(response => {

			const usersFiltered = [];
			response.map((item) => {
				if (item.name.toLowerCase().includes(filterString.toLowerCase())) {
					usersFiltered.push(item);
				}
			});
			
			return res.json({ success: true, data: usersFiltered });
		})
		.catch(err => {
			return new Error({ success: false, error: err });
		});
});

// @router   PATCH /api/v1/users/advancedSearch/terms/:id
// @desc     Accept the advanced search T&Cs for a user
// @access   Private
router.patch('/advancedSearch/terms/:id', passport.authenticate('jwt'), utils.checkIsUser(), async (req, res) => {
	const { acceptedAdvancedSearchTerms } = req.body;
	if (typeof acceptedAdvancedSearchTerms !== 'boolean') {
		return res.status(400).json({ status: 'error', message: 'Invalid input supplied.' });
	}
	let user = await UserModel.findOneAndUpdate({ id: req.params.id }, { acceptedAdvancedSearchTerms }, { new: true }, err => {
		if (err) return res.json({ success: false, error: err });
	});
	return res.status(200).json({ status: 'success', response: user });
});

// @router   PATCH /api/v1/users/advancedSearch/customRoles/:id
// @desc     Allow admin to set custom advanced search roles for a user
// @access   Private
router.patch('/advancedSearch/customRoles/:id', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Admin), async (req, res) => {
	const { advancedSearchRoles } = req.body;
	if (typeof advancedSearchRoles !== 'object') {
		return res.status(400).json({ status: 'error', message: 'Invalid role(s) supplied.' });
	}

	await setCohortDiscoveryAccess(req.params.id, advancedSearchRoles)
		.then(response => {
			return res.status(200).json({ status: 'success', response });
		})
		.catch(err => {
			return res.status(err.statusCode).json({ status: 'error', message: err.message });
		});
});

// @router   PATCH /api/v1/users/advancedSearch/roles/:id
// @desc     Grant basic advanced search role for an Open Athens user
// @access   Private
router.patch('/advancedSearch/roles/:id', passport.authenticate('jwt'), utils.checkIsUser(), async (req, res) => {
	if (upperCase(req.user.provider) !== 'OIDC')
		return res.status(403).json({ status: 'error', message: 'Only Open Athens users are permitted to use this route.' });

	const advancedSearchRoles = ['GENERAL_ACCESS'];

	await setCohortDiscoveryAccess(req.params.id, advancedSearchRoles)
		.then(response => {
			return res.status(200).json({ status: 'success', response });
		})
		.catch(err => {
			return res.status(err.statusCode).json({ status: 'error', message: err.message });
		});
});

// @router   POST /api/v1/users/serviceaccount
// @desc     create service account
// @access   Private
// router.post('/serviceaccount', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Admin), async (req, res) => {
// 	try {
// 		// 1. Validate request body params
// 		let { firstname = '', lastname = '', email = '', teamId = '' } = req.body;
// 		if (_.isEmpty(firstname) || _.isEmpty(lastname) || _.isEmpty(email) || _.isEmpty(teamId)) {
// 			return res.status(400).json({
// 				success: false,
// 				message: 'You must supply a first name, last name, email address and teamId',
// 			});
// 		}
// 		// 2. Create service account
// 		const serviceAccount = await createServiceAccount(firstname, lastname, email, teamId);
// 		if(_.isNil(serviceAccount)) {
// 			return res.status(400).json({
// 				success: false,
// 				message: 'Unable to create service account with provided details',
// 			});
// 		}
// 		// 3. Return service account details
// 		return res.status(200).json({
// 			success: true,
// 			serviceAccount
// 		});
// 	} catch (err) {
//		process.stdout.write(`USER - create service account: ${err.message}\n`);
// 		return res.status(500).json(err);
// 	}
// });

module.exports = router;
