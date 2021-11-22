import { to } from 'await-to-js';
import jwt from 'jsonwebtoken';
import Url from 'url';

import { discourseLogin } from '../resources/auth/sso/sso.discourse.service';
import { getObjectById } from '../resources/tool/data.repository';
import { updateRedirectURL } from '../resources/user/user.service';

const eventLogController = require('../resources/eventlog/eventlog.controller');

const signToken = (user, expiresIn = 604800) => {
	return jwt.sign({ data: user }, process.env.JWTSecret, {
		algorithm: 'HS256',
		expiresIn,
	});
};

const loginAndSignToken = (req, res, next) => {
	req.login(req.auth.user, async err => {
		if (err) {
			return next(err);
		}

		let redirect = '/';
		let returnPage = null;
		let queryStringParsed = null;
		if (req.param.returnpage) {
			returnPage = Url.parse(req.param.returnpage);
			redirect = returnPage.path;
			queryStringParsed = queryString.parse(returnPage.query);
		}

		let [, profile] = await to(getObjectById(req.user.id));
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
};

export { loginAndSignToken };
