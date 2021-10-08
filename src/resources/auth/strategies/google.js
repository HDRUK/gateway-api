import passport from 'passport';
import passportGoogle from 'passport-google-oauth';
import { to } from 'await-to-js';

import { catchLoginErrorAndRedirect, loginAndSignToken } from '../utils';
import { getUserByProviderId } from '../../user/user.repository';
import { createUser } from '../../user/user.service';
import { ROLES } from '../../user/user.roles';

const GoogleStrategy = passportGoogle.OAuth2Strategy;

const strategy = app => {
	const strategyOptions = {
		clientID: process.env.googleClientID,
		clientSecret: process.env.googleClientSecret,
		callbackURL: `/auth/google/callback`,
		proxy: true,
	};

	const verifyCallback = async (accessToken, refreshToken, profile, done) => {
		if (!profile.id || profile.id === '') return done('loginError');

		let [err, user] = await to(getUserByProviderId(profile.id));
		if (err || user) {
			return done(err, user);
		}

		const verifiedEmail = profile.emails.find(email => email.verified) || profile.emails[0];

		const [createdError, createdUser] = await to(
			createUser({
				provider: profile.provider,
				providerId: profile.id,
				firstname: profile.name.givenName,
				lastname: profile.name.familyName,
				email: verifiedEmail.value,
				password: null,
				role: ROLES.Creator,
			})
		);

		return done(createdError, createdUser);
	};

	passport.use(new GoogleStrategy(strategyOptions, verifyCallback));

	app.get(
		`/auth/google`,
		(req, res, next) => {
			// Save the url of the user's current page so the app can redirect back to it after authorization
			if (req.headers.referer) {
				req.param.returnpage = req.headers.referer;
			}
			next();
		},
		passport.authenticate('google', {
			scope: ['https://www.googleapis.com/auth/userinfo.profile', 'https://www.googleapis.com/auth/userinfo.email'],
		})
	);

	app.get(
		'/auth/google/callback',
		(req, res, next) => {
			passport.authenticate('google', (err, user) => {
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
