import passport from 'passport';
import passportLinkedin from 'passport-linkedin-oauth2';
import { to } from 'await-to-js';

import { catchLoginErrorAndRedirect, loginAndSignToken } from '../utils';
import { getUserByProviderId } from '../../user/user.repository';
import { createUser } from '../../user/user.service';
import { ROLES } from '../../user/user.roles';

const LinkedinStrategy = passportLinkedin.OAuth2Strategy;

const strategy = app => {
	const strategyOptions = {
		clientID: process.env.linkedinClientID,
		clientSecret: process.env.linkedinClientSecret,
		callbackURL: `/auth/linkedin/callback`,
		proxy: true,
	};

	const verifyCallback = async (accessToken, refreshToken, profile, done) => {
		if (!profile.id || profile.id === '') return done('loginError');

		let [err, user] = await to(getUserByProviderId(profile.id));
		if (err || user) {
			return done(err, user);
		}

		const [createdError, createdUser] = await to(
			createUser({
				provider: profile.provider,
				providerId: profile.id,
				firstname: profile.name.givenName,
				lastname: profile.name.familyName,
				email: '',
				password: null,
				role: ROLES.Creator,
			})
		);

		return done(createdError, createdUser);
	};

	passport.use(new LinkedinStrategy(strategyOptions, verifyCallback));

	app.get(
		`/auth/linkedin`,
		(req, res, next) => {
			// Save the url of the user's current page so the app can redirect back to it after authorization
			if (req.headers.referer) {
				req.param.returnpage = req.headers.referer;
			}
			next();
		},
		passport.authenticate('linkedin', {
			scope: ['r_emailaddress', 'r_liteprofile'],
		})
	);

	app.get(
		'/auth/linkedin/callback',
		(req, res, next) => {
			passport.authenticate('linkedin', (err, user) => {
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
