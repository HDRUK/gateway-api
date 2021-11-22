import passport from 'passport';
import passportOrcid from 'passport-orcid';
import { to } from 'await-to-js';

import { getUserByProviderId } from '../../resources/user/user.repository';
import { createUser } from '../../resources/user/user.service';
import { ROLES } from '../../resources/user/user.roles';

const passportOrcidStrategy = passportOrcid.Strategy;

export default class OrcidStrategy {
	constructor() {
		let strategyOptions = {
			clientID: process.env.ORCID_SSO_CLIENT_ID,
			clientSecret: process.env.ORCID_SSO_CLIENT_SECRET,
			callbackURL: `/auth/orcid/callback`,
			scope: `/authenticate`,
			proxy: true,
		};
		if (process.env.ORCID_SSO_ENV) {
			strategyOptions.sandbox = process.env.ORCID_SSO_ENV;
		}

		passport.use('orcid', new passportOrcidStrategy(strategyOptions, this.verifyCallback));
	}

	verifyCallback = async (accessToken, refreshToken, params, profile, done) => {
		if (!params.orcid || params.orcid === '') return done('loginError');

		let [err, user] = await to(getUserByProviderId(params.orcid));
		if (err || user) {
			return done(err, user);
		}

		const [createdError, createdUser] = await to(
			createUser({
				provider: 'orcid',
				providerId: params.orcid,
				firstname: params.name.split(' ')[0],
				lastname: params.name.split(' ')[1],
				password: null,
				email: '',
				role: ROLES.Creator,
			})
		);

		return done(createdError, createdUser);
	};

	orcid = (req, res, next) => {
		passport.authenticate('orcid')(req, res, next);
	};

	orcidCallback = (req, res, next) => {
		passport.authenticate('orcid', (err, user) => {
			req.auth = {
				err: err,
				user: user,
			};
			next();
		})(req, res, next);
	};
}
