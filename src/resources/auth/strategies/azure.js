import passport from 'passport';
import passportAzure from 'passport-azure-ad-oauth2';
import { to } from 'await-to-js';
import jwt from 'jsonwebtoken';

import { catchLoginErrorAndRedirect, loginAndSignToken } from '../utils';
import { getUserByProviderId } from '../../user/user.repository';
import { createUser } from '../../user/user.service';
import { ROLES } from '../../user/user.roles';

const AzureStrategy = passportAzure.Strategy;

const strategy = app => {
	const strategyOptions = {
		clientID: process.env.AZURE_SSO_CLIENT_ID,
		clientSecret: process.env.AZURE_SSO_CLIENT_SECRET,
		callbackURL: `/auth/azure/callback`,
		proxy: true,
	};

	const verifyCallback = async (accessToken, refreshToken, params, profile, done) => {
		let decodedToken;

		try {
			decodedToken = jwt.decode(params.id_token);
		} catch (err) {
			return done('loginError');
		}

		if (!decodedToken.oid || decodedToken.oid === '') return done('loginError');

		let [err, user] = await to(getUserByProviderId(decodedToken.oid));
		if (err || user) {
			return done(err, user);
		}

		const [createdError, createdUser] = await to(
			createUser({
				provider: 'azure',
				providerId: decodedToken.oid,
				firstname: decodedToken.given_name,
				lastname: decodedToken.family_name,
				password: null,
				email: decodedToken.email,
				role: ROLES.Creator,
			})
		);

		return done(createdError, createdUser);
	};

	passport.use('azure_ad_oauth2', new AzureStrategy(strategyOptions, verifyCallback));

	app.get(
		`/auth/azure`,
		(req, res, next) => {
			// Save the url of the user's current page so the app can redirect back to it after authorization
			if (req.headers.referer) {
				req.param.returnpage = req.headers.referer;
			}
			next();
		},
		passport.authenticate('azure_ad_oauth2')
	);

	app.get(
		`/auth/azure/callback`,
		(req, res, next) => {
			passport.authenticate('azure_ad_oauth2', (err, user) => {
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
