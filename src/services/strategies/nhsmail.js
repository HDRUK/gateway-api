import Url from 'url';
import axios from 'axios';
import jwt from 'jsonwebtoken';
import { to } from 'await-to-js';
import { v4 as uuidv4 } from 'uuid';

import { ROLES } from '../../resources/user/user.roles';
import helper from '../../resources/utilities/helper.util';
import { createUser } from '../../resources/user/user.service';
import { getUserByProviderId } from '../../resources/user/user.repository';

export default class NHSMailStrategy {
	constructor() {
		this.strategyOptions = {
			stateString: uuidv4(),
			nonceString: uuidv4(),
			baseAuthUrl: process.env.NHSMAIL_SSO_BASE_URL,
			endpointAuth: '/authorize',
			endpointToken: '/token',
			clientID: process.env.NHSMAIL_SSO_CLIENT_ID,
			clientSecret: process.env.NHSMAIL_SSO_CLIENT_SECRET,
			scopes: 'openid',
			callbackURL: '/auth/nhsmail/callback',
		};
	}

	// Custom logic for verifying the user credentials
	verifyCallback = async idToken => {
		if (!jwt.decode(idToken).nonce || jwt.decode(idToken).nonce !== nonceString) {
			throw new Error('The nonce value is missing or the value in the returned ID token does not match that sent in the initial request');
		}

		const profile = jwt.decode(idToken);

		if (!profile || !profile.some_unique_id) {
			throw new Error('Profile information missing from user-info, re-directing to /loginerror');
		}

		let [err, user] = await to(getUserByProviderId(profile.some_unique_id));
		if (err || user) {
			return [err, user];
		}

		const [createdError, createdUser] = await to(
			createUser({
				provider: 'nhsmail',
				providerId: profile.some_unique_id,
				firstname: profile.given_name,
				lastname: helper.toTitleCase(profile.family_name),
				password: null,
				email: profile.email,
				role: ROLES.Creator,
			})
		);

		return [createdError, createdUser];
	};

	initialise = (req, res) => {
		res.redirect(
			`${this.strategyOptions.baseAuthUrl + this.strategyOptions.endpointAuth}` +
				`?client_id=${this.strategyOptions.clientID}` +
				`&redirect_uri=${Url.format({
					protocol: req.protocol,
					host: req.get('host'),
					pathname: this.strategyOptions.callbackURL,
				})}` +
				`&response_type=code` +
				`&scope=${this.strategyOptions.scopes}` +
				`&nonce=${this.strategyOptions.nonceString}` +
				`&state=${this.strategyOptions.stateString}`
		);
	};

	callback = async (req, res, next) => {
		try {
			if (req.query.state !== stateString) {
				throw new Error('The response state parameter does not match the state of the initial request');
			}

			const {
				data: { id_token },
			} = await axios.post(
				`${this.strategyOptions.baseAuthUrl + this.strategyOptions.endpointToken}` +
					`?code=${req.query.code}` +
					`&client_id=${this.strategyOptions.clientID}` +
					`&redirect_uri=${Url.format({
						protocol: req.protocol,
						host: req.get('host'),
						pathname: new URL(req.originalUrl).pathname,
					})}` +
					`&grant_type=authorization_code`
			);

			const [err, user] = await verifyCallback(id_token);
			req.auth = {
				err: err,
				user: user,
			};
			next();
		} catch (err) {
			process.stdout.write(`${err.message}\n`);
			return res.status(200).redirect(process.env.homeURL + '/loginerror');
		}
	};
}
