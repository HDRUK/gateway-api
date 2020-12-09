import express from 'express'
import { ROLES } from '../user/user.roles'
import passport from "passport";
import { utils } from "../auth";
import { UserModel } from './user.model'
import { Data } from '../tool/data.model'
import helper from '../utilities/helper.util';

const router = express.Router();

// @router   GET /api/v1/users/:userID
// @desc     find user by id
// @access   Private
router.get(
	'/:userID',
	passport.authenticate('jwt'),
	utils.checkIsInRole(ROLES.Admin, ROLES.Creator),
	async (req, res) => {
		//req.params.id is how you get the id from the url
		var q = UserModel.find({ id: req.params.userID });

		q.exec((err, userdata) => {
			if (err) return res.json({ success: false, error: err });
			return res.json({ success: true, userdata: userdata });
		});
	}
);

// @router   GET /api/v1/users
// @desc     get all
// @access   Private
router.get(
	'/',
	passport.authenticate('jwt'),
	utils.checkIsInRole(ROLES.Admin, ROLES.Creator),
	async (req, res) => {
		var q = Data.aggregate([
			// Find all tools with type of person
			{ $match: { type: 'person' } },
			// Perform lookup to users
			{
				$lookup: {
					from: 'users',
					localField: 'id',
					foreignField: 'id',
					as: 'user',
				},
			},
			// select fields to use
			{
				$project: {
					_id: '$user._id',
					id: 1,
					firstname: 1,
					lastname: 1,
					orcid: {
						$cond: [{
							$eq: [ true, "$showOrcid" ]},
							"$orcid", "$$REMOVE"]
					},
					bio: {
						$cond: [{
							$eq: [ true, "$showBio" ]},
							"$bio", "$$REMOVE"]
					},
					email: '$user.email',
				},
			},
		]);

		q.exec((err, data) => {
			if (err) {
				return new Error({ success: false, error: err });
			}

			const users = [];
			data.map((dat) => {
				let { _id, id, firstname, lastname, orcid = '', bio = '', email = '' } = dat;
				if (email.length !== 0) email = helper.censorEmail(email[0]);
				users.push({ _id, id, orcid, name: `${firstname} ${lastname}`, bio, email });
			});

			return res.json({ success: true, data: users });
		});
	}
);

module.exports = router