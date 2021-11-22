import express from 'express';
import _ from 'lodash';

import { authUtils } from '../utils';
import { getServiceAccountByClientCredentials } from '../resources/user/user.repository';

const router = express.Router();

// @router   POST /oauth/token
// @desc     Issues a JWT for a valid authentication attempt using a user defined grant type
// @access   Public
router.post('/token', async (req, res) => {
	// 1. Deconstruct grant type
	const { grant_type = '' } = req.body;
	// 2. Allow different grant types to be processed
	switch (grant_type) {
		case 'client_credentials':
			// Deconstruct request body to extract client ID, secret
			const { client_id = '', client_secret = '' } = req.body;
			// Ensure client credentials have been passed
			if (_.isEmpty(client_id) || _.isEmpty(client_secret)) {
				return res.status(400).json({
					success: false,
					message: 'Incomplete client credentials were provided for the authorisation attempt',
				});
			}
			// Find an associated service account based on the credentials passed
			const serviceAccount = await getServiceAccountByClientCredentials(client_id, client_secret);
			if (_.isNil(serviceAccount)) {
				return res.status(400).json({
					success: false,
					message: 'Invalid client credentials were provided for the authorisation attempt',
				});
			}
			// Construct JWT for service account
			const token_type = 'jwt',
				expires_in = 900;
			const jwt = authUtils.signToken({ _id: serviceAccount._id, id: serviceAccount.id, timeStamp: Date.now() }, expires_in);
			const access_token = `Bearer ${jwt}`;

			// Return payload
			return res.status(200).json({
				access_token,
				token_type,
				expires_in,
			});
	}
	// Bad request for any other grant type passed
	return res.status(400).json({
		success: false,
		message: 'An invalid grant type has been specified',
	});
});

module.exports = router;
