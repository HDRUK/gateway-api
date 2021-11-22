import Url from 'url';
import axios from 'axios';
import { to } from 'await-to-js';
import queryString from 'query-string';
import { v4 as uuidv4 } from 'uuid';
import jwt from 'jsonwebtoken';

import { getUserByProviderId } from '../../resources/user/user.repository';
import { createUser } from '../../resources/user/user.service';
import { ROLES } from '../../resources/user/user.roles';
import helper from '../../resources/utilities/helper.util';

export default class NHSMailStrategy {
	constructor() {
		this.strategyOptions = {
			stateString: uuidv4(),
			nonceString: uuidv4(),
			baseAuthUrl: process.env.NHSMAIL_SSO_BASE_URL,
			clientID: process.env.NHSMAIL_SSO_CLIENT_ID,
			clientSecret: process.env.NHSMAIL_SSO_CLIENT_SECRET,
		};
	}

	// Custom logic for verifying the user credentials
	verifyCallback = async (accessToken, refreshToken, idToken) => {
		if (!jwt.decode(idToken).nonce || jwt.decode(idToken).nonce !== nonceString) {
			throw new Error('The nonce value is missing or the value in the returned ID token does not match that sent in the initial request');
		}

		const { data: profile } = await axios({
			url: baseAuthUrl + '/userinfo',
			method: 'get',
			headers: {
				Authorization: `Bearer ${accessToken}`,
			},
		});

		if (!profile || !profile.nhs_number) {
			throw new Error('Profile information missing from user-info, re-directing to /loginerror');
		}

		let [err, user] = await to(getUserByProviderId(profile.nhs_number));
		if (err || user) {
			return [err, user];
		}

		const [createdError, createdUser] = await to(
			createUser({
				provider: 'nhslogin',
				providerId: profile.nhs_number,
				firstname: profile.given_name,
				lastname: helper.toTitleCase(profile.family_name),
				password: null,
				email: profile.email,
				role: ROLES.Creator,
			})
		);

		return [createdError, createdUser];
	};

	initialise = (req, res, next) => {
		// Initialise the request by redirecting to the appropriate sign in page
		res.redirect(
			`${this.strategyOptions.baseAuthUrl}/authorize?` +
				queryString.stringify({
					client_id: this.strategyOptions.clientID,
					redirect_uri: Url.format({
						protocol: req.protocol,
						host: req.get('host'),
						pathname: '/auth/nhsmail/callback',
					}),
					response_type: 'code',
					scope: 'openid',
					nonce: this.strategyOptions.nonceString,
					state: this.strategyOptions.stateString,
				})
		);
	};

	callback = async (req, res, next) => {
		try {
			// Confirm the state string
			if (req.query.state !== stateString) {
				throw new Error('The response state parameter does not match the state of the initial request');
			}

			// Make a request to the token endpoint using the authorisation code
			const {
				data: { access_token, refresh_token, id_token },
			} = await axios.post(
				`${this.strategyOptions.baseAuthUrl}/token`,
				queryString.stringify({
					code: req.query.code,
					client_id: this.strategyOptions.clientID,
					redirect_uri: Url.format({
						protocol: req.protocol,
						host: req.get('host'),
						pathname: new URL(req.originalUrl).pathname,
					}),
					grant_type: 'authorization_code',
					client_assertion_type: 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
					client_assertion: createAndSignBearerToken({
						clientId: this.strategyOptions.clientID,
						audience: `${this.strategyOptions.baseAuthUrl}/token`,
						privateKey: this.strategyOptions.clientSecret,
					}),
				})
			);

			// Perform a check on the credentials
			const [err, user] = await verifyCallback(access_token, refresh_token, id_token);
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
