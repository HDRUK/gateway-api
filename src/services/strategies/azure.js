import passport from 'passport';
import passportAzure from 'passport-azure-ad-oauth2';
import { to } from 'await-to-js';
import jwt from 'jsonwebtoken';

import { getUserByProviderId } from '../../resources/user/user.repository';
import { createUser } from '../../resources/user/user.service';
import { ROLES } from '../../resources/user/user.roles';

const passportAzureStrategy = passportAzure.Strategy;

export default class AzureStrategy {
	constructor() {
		const strategyOptions = {
			clientID: process.env.AZURE_SSO_CLIENT_ID,
			clientSecret: process.env.AZURE_SSO_CLIENT_SECRET,
			callbackURL: `/auth/azure/callback`,
			proxy: true,
		};

		passport.use('azure_ad_oauth2', new passportAzureStrategy(strategyOptions, this.verifyCallback));
	}

	verifyCallback = async (accessToken, refreshToken, params, profile, done) => {
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

	initialise = (req, res, next) => {
		passport.authenticate('azure_ad_oauth2')(req, res, next);
	};

	callback = (req, res, next) => {
		passport.authenticate('azure_ad_oauth2', (err, user) => {
			req.auth = {
				err: err,
				user: user,
			};
			next();
		})(req, res, next);
	};
}
