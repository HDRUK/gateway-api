import express from 'express';
import { to } from 'await-to-js';
import { login } from '../auth/strategies/jwt';
import { updateUser } from '../user/user.service';
import { createPerson } from '../person/person.service';
import { getUserByUserId } from '../user/user.repository';
import { registerDiscourseUser } from '../discourse/discourse.service';
import hubspotConnector from '../../services/hubspot/hubspot';
const urlValidator = require('../utilities/urlValidator');
const eventLogController = require('../eventlog/eventlog.controller');
const router = express.Router();

// @router   Get /auth/register
// @desc     Pulls user details to complete registration
// @access   Public
router.get('/:personID', async (req, res) => {
	const [err, user] = await to(getUserByUserId(req.params.personID));

	if (err) return res.json({ success: false, error: err });
	return res.json({ success: true, data: user });
});

// @router   POST /auth/register
// @desc     Register user
// @access   Public
router.post('/', async (req, res) => {
	const {
		id,
		firstname,
		lastname,
		email,
		bio,
		showBio,
		showLink,
		showOrcid,
		redirectURL: redirectURLis = '',
		sector,
		showSector,
		organisation,
		feedback,
		news,
		terms,
		tags,
		showDomain,
		showOrganisation,
		profileComplete,
	} = req.body;
	let link = urlValidator.validateURL(req.body.link);
	let orcid = urlValidator.validateOrcidURL(req.body.orcid);
	let discourseUsername,
		discourseKey = '';

	if (!/\b[a-zA-Z0-9-_.]+\@[a-zA-Z0-9-_]+\.\w+(?:\.\w+)?\b/.test(email)) {
		return res.status(500).json({ success: false, data: 'Enter a valid email address.' });
	}

	// 1. Update existing user record created during login
	let [, user] = await to(
		updateUser({
			id,
			firstname,
			lastname,
			email,
			discourseKey,
			discourseUsername,
			feedback,
			news,
		})
	);

	// 2. Create person entry in tools
	await to(
		createPerson({
			id,
			firstname,
			lastname,
			bio,
			showBio,
			link,
			showLink,
			orcid,
			showOrcid,
			terms,
			sector,
			showSector,
			organisation,
			tags,
			showDomain,
			showOrganisation,
			profileComplete,
		})
	);

	// 3. Create Discourse user with SSO enabled and generate API key
	await registerDiscourseUser({
		id,
		firstname,
		lastname,
		email,
	});

	// 4. Sync contact in Hubspot
	hubspotConnector.syncContact({ ...user.toObject(), orcid, sector, organisation });

	const [loginErr, token] = await to(login(req, user));

	if (loginErr) {
		process.stdout.write(`Authentication error\n`);
		return res.status(500).json({ success: false, data: 'Authentication error!' });
	}

	// 5. Build event object for user registered and log it to DB
	let eventObj = {
		userId: req.user.id,
		event: `user_registered_${req.user.provider}`,
		timestamp: Date.now(),
	};
	await eventLogController.logEvent(eventObj);

	return res
		.status(200)
		.cookie('jwt', token, {
			httpOnly: true,
			secure: process.env.api_url ? true : false,
		})
		.json({ success: false, data: redirectURLis });
});

module.exports = router;
