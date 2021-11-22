import passport from 'passport';
import passportJWT from 'passport-jwt';
import { to } from 'await-to-js';
import _ from 'lodash';

import { getUserById } from '../../resources/user/user.repository';
import { authUtils } from '../../utils';

const passportJWTStrategy = passportJWT.Strategy;

export default class JWTStrategy {
	constructor() {
		const strategyOptions = {
			jwtFromRequest: this.extractJWT,
			secretOrKey: process.env.JWTSecret,
			passReqToCallback: true,
		};

		passport.use(new passportJWTStrategy(strategyOptions, this.verifyCallback));
	}

	extractJWT = req => {
		// 1. Default extract jwt from request cookie
		let {
			cookies: { jwt = '' },
		} = req;
		if (!_.isEmpty(jwt)) {
			// 2. Return jwt if found in cookie
			return jwt;
		}
		// 2. Fallback/external integration extracts jwt from authorization header
		let {
			headers: { authorization = '' },
		} = req;
		// If token contains bearer type, strip it and return jwt
		if (authorization.split(' ')[0] === 'Bearer') {
			jwt = authorization.split(' ')[1];
		}
		return jwt;
	};

	verifyCallback = async (req, jwtPayload, cb) => {
		if (typeof jwtPayload.data === 'string') {
			jwtPayload.data = JSON.parse(jwtPayload.data);
		}
		const [err, user] = await to(getUserById(jwtPayload.data._id));

		if (err) {
			return cb(err);
		}
		req.user = user;
		return cb(null, user);
	};

	login = (req, user) => {
		return new Promise((resolve, reject) => {
			req.login(user, { session: false }, err => {
				if (err) {
					return reject(err);
				}

				return resolve(authUtils.signToken(user));
			});
		});
	};
}
