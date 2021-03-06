import passport from 'passport';
import passportLinkedin from 'passport-linkedin-oauth2';
import { to } from 'await-to-js';

import { getUserByProviderId } from '../../user/user.repository';
import { getObjectById } from '../../tool/data.repository';
import { updateRedirectURL } from '../../user/user.service';
import { createUser } from '../../user/user.service';
import { signToken } from '../utils';
import { ROLES } from '../../user/user.roles';
import queryString from 'query-string';
import Url from 'url';
import { discourseLogin } from '../sso/sso.discourse.service';

const eventLogController = require('../../eventlog/eventlog.controller');
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

	app.get('/auth/linkedin/callback', (req, res, next) => {
		passport.authenticate('linkedin', (err, user, info) => {
			if (err || !user) {
				//loginError
				if (err === 'loginError') return res.status(200).redirect(process.env.homeURL + '/loginerror');

				// failureRedirect
				var redirect = '/';
				let returnPage = null;

				if (req.param.returnpage) {
					returnPage = Url.parse(req.param.returnpage);
					redirect = returnPage.path;
					delete req.param.returnpage;
				}

				let redirectUrl = process.env.homeURL + redirect;

				return res.status(200).redirect(redirectUrl);
			}

			req.login(user, async err => {
				if (err) {
					return next(err);
				}

				var redirect = '/';

				let returnPage = null;
				let queryStringParsed = null;
				if (req.param.returnpage) {
					returnPage = Url.parse(req.param.returnpage);
					redirect = returnPage.path;
					queryStringParsed = queryString.parse(returnPage.query);
				}

				let [profileErr, profile] = await to(getObjectById(req.user.id));

				if (!profile) {
					await to(updateRedirectURL({ id: req.user.id, redirectURL: redirect }));
					return res.redirect(process.env.homeURL + '/completeRegistration/' + req.user.id);
				}

				if (req.param.returnpage) {
					delete req.param.returnpage;
				}

				let redirectUrl = process.env.homeURL + redirect;

				if (queryStringParsed && queryStringParsed.sso && queryStringParsed.sig) {
					try {
						redirectUrl = discourseLogin(queryStringParsed.sso, queryStringParsed.sig, req.user);
					} catch (err) {
						console.error(err.message);
						return res.status(500).send('Error authenticating the user.');
					}
				}

				//Build event object for user login and log it to DB
				let eventObj = {
					userId: req.user.id,
					event: `user_login_${req.user.provider}`,
					timestamp: Date.now(),
				};
				await eventLogController.logEvent(eventObj);

				return res
					.status(200)
					.cookie('jwt', signToken({ _id: req.user._id, id: req.user.id, timeStamp: Date.now() }), {
						httpOnly: true,
						secure: process.env.api_url ? true : false,
					})
					.redirect(redirectUrl);
			});
		})(req, res, next);
	});

	return app;
};

export { strategy };
