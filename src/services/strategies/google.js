import passport from 'passport';
import passportGoogle from 'passport-google-oauth';
import { to } from 'await-to-js';

import { getUserByProviderId } from '../../resources/user/user.repository';
import { createUser } from '../../resources/user/user.service';
import { ROLES } from '../../resources/user/user.roles';

const passportGoogleStrategy = passportGoogle.OAuth2Strategy;

export default class GoogleStrategy {
	constructor() {
		const strategyOptions = {
			clientID: process.env.googleClientID,
			clientSecret: process.env.googleClientSecret,
			callbackURL: `/auth/google/callback`,
			proxy: true,
		};

		passport.use(new passportGoogleStrategy(strategyOptions, this.verifyCallback));
	}

	verifyCallback = async (accessToken, refreshToken, profile, done) => {
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

	initialise = (req, res, next) => {
		passport.authenticate('google', {
			scope: ['https://www.googleapis.com/auth/userinfo.profile', 'https://www.googleapis.com/auth/userinfo.email'],
		})(req, res, next);
	};

	callback = (req, res, next) => {
		passport.authenticate('google', (err, user) => {
			req.auth = {
				err: err,
				user: user,
			};
			next();
		})(req, res, next);
	};
}
