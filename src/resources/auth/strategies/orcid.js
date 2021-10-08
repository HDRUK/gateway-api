import passport from 'passport';
import passportOrcid from 'passport-orcid';
import { to } from 'await-to-js';
import axios from 'axios';

import { catchLoginErrorAndRedirect, loginAndSignToken } from '../utils';
import { getUserByProviderId } from '../../user/user.repository';
import { createUser } from '../../user/user.service';
import { ROLES } from '../../user/user.roles';

const OrcidStrategy = passportOrcid.Strategy;

const strategy = app => {
	const strategyOptions = {
		sandbox: process.env.ORCID_SSO_ENV,
		clientID: process.env.ORCID_SSO_CLIENT_ID,
		clientSecret: process.env.ORCID_SSO_CLIENT_SECRET,
		callbackURL: `/auth/orcid/callback`,
		scope: `/authenticate /read-limited`,
		proxy: true,
	};

	const verifyCallback = async (accessToken, refreshToken, params, profile, done) => {
		if (!params.orcid || params.orcid === '') return done('loginError');

		let [err, user] = await to(getUserByProviderId(params.orcid));
		if (err || user) {
			return done(err, user);
		}
		// Orcid does not include email natively
		const requestedEmail = await axios
			.get(`${process.env.ORCID_SSO_BASE_URL}/v3.0/${params.orcid}/email`, {
				headers: { Authorization: `Bearer ` + accessToken, Accept: 'application/json' },
			})
			.then(response => {
				const email = response.data.email[0].email;
				return email == undefined || !/\b[a-zA-Z0-9-_.]+\@[a-zA-Z0-9-_]+\.\w+(?:\.\w+)?\b/.test(email) ? '' : email;
			})
			.catch(err => {
				console.log(err);
				return '';
			});

		const [createdError, createdUser] = await to(
			createUser({
				provider: 'orcid',
				providerId: params.orcid,
				firstname: params.name.split(' ')[0],
				lastname: params.name.split(' ')[1],
				password: null,
				email: requestedEmail,
				role: ROLES.Creator,
			})
		);

		return done(createdError, createdUser);
	};

	passport.use('orcid', new OrcidStrategy(strategyOptions, verifyCallback));

	app.get(
		`/auth/orcid`,
		(req, res, next) => {
			// Save the url of the user's current page so the app can redirect back to it after authorization
			if (req.headers.referer) {
				req.param.returnpage = req.headers.referer;
			}
			next();
		},
		passport.authenticate('orcid')
	);

	app.get(
		'/auth/orcid/callback',
		(req, res, next) => {
			passport.authenticate('orcid', (err, user) => {
				req.auth = {
					err: err,
					user: user,
				};
				next();
			})(req, res, next);
		},
		catchLoginErrorAndRedirect,
		loginAndSignToken
	);
	return app;
};

export { strategy };
