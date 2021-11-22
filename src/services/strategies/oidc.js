import passport from 'passport';
import passportOidc from 'passport-openidconnect';
import { to } from 'await-to-js';
import { isNil } from 'lodash';

import { getUserByProviderId } from '../../resources/user/user.repository';
import { createUser } from '../../resources/user/user.service';
import { UserModel } from '../../resources/user/user.model';
import { ROLES } from '../../resources/user/user.roles';

const passportOIDCStrategy = passportOidc.Strategy;

export default class OIDCStrategy {
	constructor() {
		const strategyOptions = {
			issuer: process.env.AUTH_PROVIDER_URI,
			authorizationURL: process.env.AUTH_PROVIDER_URI + '/oidc/auth',
			tokenURL: process.env.AUTH_PROVIDER_URI + '/oidc/token',
			userInfoURL: process.env.AUTH_PROVIDER_URI + '/oidc/userinfo',
			clientID: process.env.openidClientID,
			clientSecret: process.env.openidClientSecret,
			callbackURL: `/auth/oidc/callback`,
			proxy: true,
		};

		passport.use('oidc', new passportOIDCStrategy(strategyOptions, this.verifyCallback));
	}

	verifyCallback = async (accessToken, refreshToken, profile, done) => {
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

	initialise = (req, res, next) => {
		passport.authenticate('oidc')(req, res, next);
	};

	callback = (req, res, next) => {
		passport.authenticate('oidc', (err, user) => {
			req.auth = {
				err: err,
				user: user,
			};
			next();
		})(req, res, next);
	};
}
