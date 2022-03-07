import express from 'express';
import * as Sentry from '@sentry/node';
import hubspotConnector from './hubspot';
const router = express.Router();
const readEnv = process.env.NODE_ENV || 'prod';

// @router   POST /api/v1/hubspot/sync
// @desc     Performs a two-way sync of contact details including communication opt in preferences between HubSpot and the Gateway database
// @access   Public (key required)
router.post('/sync', async (req, res) => {
	try {
		// Check to see if header is in json format
		let parsedBody = {};
		if (req.header('content-type') === 'application/json') {
			parsedBody = req.body;
		} else {
			parsedBody = JSON.parse(req.body);
		}
		// Check for key
		if (parsedBody.key !== process.env.HUBSPOT_SYNC_KEY) {
			return res.status(400).json({ success: false, error: 'Sync could not be started' });
		}
		// Throw error if parsing failed
		if (parsedBody.error === true) {
			throw new Error('Sync parsing error');
		}
		// Run sync job
		hubspotConnector.syncAllContacts();
		// Return response indicating job has started (do not await async import)
		return res.status(200).json({ success: true, message: 'Sync started' });
	} catch (err) {
		if (readEnv === 'test' || readEnv === 'prod') {
			Sentry.captureException(err);
		}
		console.error(err.message);
		return res.status(500).json({ success: false, message: 'Sync failed' });
	}
});

module.exports = router;
