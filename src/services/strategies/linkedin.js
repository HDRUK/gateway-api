import passport from 'passport';
import passportLinkedin from 'passport-linkedin-oauth2';
import { to } from 'await-to-js';

import { getUserByProviderId } from '../../resources/user/user.repository';
import { createUser } from '../../resources/user/user.service';
import { ROLES } from '../../resources/user/user.roles';

const passportLinkedinStrategy = passportLinkedin.OAuth2Strategy;

export default class LinkedinStrategy {
	constructor() {
		const strategyOptions = {
			clientID: process.env.linkedinClientID,
			clientSecret: process.env.linkedinClientSecret,
			callbackURL: `/auth/linkedin/callback`,
			proxy: true,
		};

		passport.use(new passportLinkedinStrategy(strategyOptions, this.verifyCallback));
	}

	verifyCallback = async (accessToken, refreshToken, profile, done) => {
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

	initialise = (req, res, next) => {
		passport.authenticate('linkedin', {
			scope: ['r_emailaddress', 'r_liteprofile'],
		})(req, res, next);
	};

	callback = (req, res, next) => {
		passport.authenticate('linkedin', (err, user) => {
			req.auth = {
				err: err,
				user: user,
			};
			next();
		})(req, res, next);
	};
}
