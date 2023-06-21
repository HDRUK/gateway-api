import express from 'express';
import passport from 'passport';
import { signToken } from '../utils';
import { discourseLogin } from './sso.discourse.service';

const router = express.Router();

// @router   GET /api/v1/auth/sso/discourse
// @desc     Single Sign On for Discourse forum
// @access   Private
router.get('/', function (req, res, next) {
	passport.authenticate('jwt', function (err, user, info) {
		if (err || !user) {
			return res.status(200).json({ redirectUrl: null });
		} else {
			let redirectUrl = null;

			if (req.query.sso && req.query.sig) {
				try {
					redirectUrl = discourseLogin(req.query.sso, req.query.sig, req.user);
				} catch (err) {
					process.stdout.write(`Single Sign On for Discourse forum : ${err.message}\n`);
					return res.status(500).send('Error authenticating the user.');
				}
			}

			return res
				.status(200)
				.cookie(
					'jwt',
					signToken({
						_id: req.user._id,
						id: req.user.id,
						timeStamp: Date.now(),
					}),
					{
						httpOnly: true,
						secure: process.env.api_url ? true : false,
					}
				)
				.json({ redirectUrl: redirectUrl });
		}
	})(req, res, next);
});

module.exports = router;
