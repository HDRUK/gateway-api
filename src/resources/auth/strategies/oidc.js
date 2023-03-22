import passport from 'passport';
import passportOidc from 'passport-openidconnect';
import { to } from 'await-to-js';

import { catchLoginErrorAndRedirect, loginAndSignToken } from '../utils';
import { getUserByProviderId } from '../../user/user.repository';
import { createUser } from '../../user/user.service';
import { UserModel } from '../../user/user.model';
import { ROLES } from '../../user/user.roles';
import { isNil } from 'lodash';

const OidcStrategy = passportOidc.Strategy;
const baseAuthUrl = process.env.AUTH_PROVIDER_URI;

const strategy = app => {
	const strategyOptions = {
		issuer: baseAuthUrl,
		authorizationURL: baseAuthUrl + '/oidc/auth',
		tokenURL: baseAuthUrl + '/oidc/token',
		userInfoURL: baseAuthUrl + '/oidc/userinfo',
		clientID: process.env.openidClientID,
		clientSecret: process.env.openidClientSecret,
		callbackURL: `/auth/oidc/callback`,
		proxy: true,
	};

	const verifyCallback = async (accessToken, refreshToken, profile, done) => {
		if (!profile || !profile._json || !profile._json.eduPersonTargetedID || profile._json.eduPersonTargetedID === '')
			return done('loginError');

		let [err, user] = await to(getUserByProviderId(profile._json.eduPersonTargetedID));
		if (err || user) {
			if (user && !user.affiliation) {
				UserModel.findOneAndUpdate({ id: user.id }, { $set: { affiliation: profile._json.eduPersonScopedAffilation } });
			}
			return done(err, user);
		}

		const [createdError, createdUser] = await to(
			createUser({
				provider: 'oidc',
				providerId: profile._json.eduPersonTargetedID,
				affiliation: !isNil(profile._json.eduPersonScopedAffilation) ? profile._json.eduPersonScopedAffilation : 'no.organization',
				firstname: '',
				lastname: '',
				email: '',
				password: null,
				role: ROLES.Creator,
			})
		);

		return done(createdError, createdUser);
	};

	passport.use('oidc', new OidcStrategy(strategyOptions, verifyCallback));

	app.get(
		`/auth/oidc`,
		(req, res, next) => {
			// Save the url of the user's current page so the app can redirect back to it after authorization
			if (req.headers.referer) {
				req.param.returnpage = req.headers.referer;
			}
			next();
		},
		passport.authenticate('oidc')
	);

	app.get(
		'/auth/oidc/callback',
		(req, res, next) => {
			if (req.query.target_link_uri) {
				req.param.returnpage = req.query.target_link_uri;
			}
			passport.authenticate('oidc', (err, user) => {
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
