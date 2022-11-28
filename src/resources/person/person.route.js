import express from 'express';
import { Data } from '../tool/data.model';
import { utils } from '../auth';
import passport from 'passport';
import { getAllTools } from '../tool/data.repository';
import { UserModel } from '../user/user.model';
import hubspotConnector from '../../services/hubspot/hubspot';
import helper from '../utilities/helper.util';
import { isEmpty } from 'lodash';
import { logger } from '../utilities/logger';
const urlValidator = require('../utilities/urlValidator');
const inputSanitizer = require('../utilities/inputSanitizer');
const logCategory = 'Person API';

const router = express.Router();

router.put('/', passport.authenticate('jwt'), utils.checkIsUser(), async (req, res) => {
	try {
		let {
			id,
			firstname,
			lastname,
			email,
			bio,
			showBio,
			showLink,
			showOrcid,
			feedback,
			news,
			terms,
			sector,
			showSector,
			organisation,
			showOrganisation,
			tags,
			showDomain,
			profileComplete,
		} = req.body;
		const type = 'person';
		let link = urlValidator.validateURL(inputSanitizer.removeNonBreakingSpaces(req.body.link));
		let orcid = req.body.orcid !== '' ? urlValidator.validateOrcidURL(inputSanitizer.removeNonBreakingSpaces(req.body.orcid)) : '';
		(firstname = inputSanitizer.removeNonBreakingSpaces(firstname)),
			(lastname = inputSanitizer.removeNonBreakingSpaces(lastname)),
			(bio = inputSanitizer.removeNonBreakingSpaces(bio));
		sector = inputSanitizer.removeNonBreakingSpaces(sector);
		organisation = inputSanitizer.removeNonBreakingSpaces(organisation);
		tags.topics = inputSanitizer.removeNonBreakingSpaces(tags.topics);

		const userId = parseInt(id);

		await Data.findOneAndUpdate(
		{ id: userId },
		{
			firstname: firstname,
			lastname: lastname,
			type: type,
			bio: bio,
			showBio: showBio,
			link: link,
			showLink: showLink,
			orcid: orcid,
			showOrcid: showOrcid,
			terms: terms,
			sector: sector,
			showSector: showSector,
			organisation: organisation,
			showOrganisation: showOrganisation,
			tags: tags,
			showDomain: showDomain,
			profileComplete: profileComplete,
		}
	);

		const user = await UserModel.findOneAndUpdate({ id: userId }, { $set: { firstname, lastname, email, feedback, news } }, { new: true });

		// Sync contact in Hubspot
		hubspotConnector.syncContact({ ...user.toObject(), orcid, sector, organisation });

		return res.status(200).json({
			status: 'success',
			data: user,
		});
	} catch (err) {
		// Return error response if something goes wrong
		logger.logError(err, logCategory);
		return res.status(500).json({
			success: false,
			message: 'An error occurred attempting to update the user record',
		});
	}
});

// @router   GET /api/v1/person/unsubscribe/:userObjectId
// @desc     Unsubscribe a single user from email notifications without challenging authentication
// @access   Public
// router.put('/unsubscribe/:userObjectId', async (req, res) => {
// 	const userId = req.params.userObjectId;
// 	// 1. Use _id param issued by MongoDb as unique reference to find user entry
// 	await UserModel.findOne({ _id: userId })
// 		.then(async user => {
// 			// 2. Find person entry using numeric id and update email notifications to false
// 			await Data.findOneAndUpdate(
// 				{ id: user.id },
// 				{
// 					emailNotifications: false,
// 				}
// 			).then(() => {
// 				// 3a. Return success message
// 				return res.json({
// 					success: true,
// 					msg: "You've been successfully unsubscribed from all emails. You can change this setting via your account.",
// 				});
// 			});
// 		})
// 		.catch(() => {
// 			// 3b. Return generic failure message in all cases without disclosing reason or data structure
// 			return res.status(500).send({ success: false, msg: 'A problem occurred unsubscribing from email notifications.' });
// 		});
// });

// @router   PATCH /api/v1/person/profileComplete/:id
// @desc     Set profileComplete to true
// @access   Private
router.patch('/profileComplete/:id', passport.authenticate('jwt'), utils.checkIsUser(), async (req, res) => {
	const id = req.params.id;
	await Data.findOneAndUpdate({ id }, { profileComplete: true })
		.then(response => {
			return res.json({ success: true, response });
		})
		.catch(err => {
			return res.json({ success: false, error: err.message });
		});
});

// @router   GET /api/v1/person/:id
// @desc     Get person info based on personID
router.get('/:id', async (req, res) => {
	if (req.params.id === 'null') {
		return res.json({ data: null });
	}
	let person = await Data.findOne({ id: parseInt(req.params.id) })
		.populate([{ path: 'tools' }, { path: 'reviews' }])
		.catch(err => {
			return res.json({ success: false, error: err });
		});

	if (isEmpty(person)) {
		return res.status(404).send(`Person not found for Id: ${escape(req.params.id)}`);
	} else {
		person = helper.hidePrivateProfileDetails([person])[0];
		return res.json({ person });
	}
});

// @router   GET /api/v1/person/profile/:id
// @desc     Get person info for their account
router.get('/profile/:id', async (req, res) => {
	try {
		let person = await Data.findOne({ id: parseInt(req.params.id) })
			.populate([{ path: 'tools' }, { path: 'reviews' }, { path: 'user', select: 'feedback news' }])
			.lean();
		const { feedback, news } = person.user;
		person = { ...person, feedback, news };
		let data = [person];
		return res.json({ success: true, data: data });
	} catch (err) {
		process.stdout.write(`PERSON - GET PROFILE : ${err.message}\n`);
		return res.json({ success: false, error: err.message });
	}
});

// @router   GET /api/v1/person
// @desc     Get paper for an author
// @access   Private
router.get('/', async (req, res) => {
	let personArray = [];
	req.params.type = 'person';
	await getAllTools(req)
		.then(data => {
			data.map(personObj => {
				personArray.push({
					id: personObj.id,
					type: personObj.type,
					firstname: personObj.firstname,
					lastname: personObj.lastname,
					bio: personObj.bio,
					sociallinks: personObj.sociallinks,
					company: personObj.company,
					link: personObj.link,
					orcid: personObj.orcid,
					activeflag: personObj.activeflag,
					createdAt: personObj.createdAt,
					updatedAt: personObj.updatedAt,
					__v: personObj.__v,
					emailNotifications: personObj.emailNotifications,
					terms: personObj.terms,
					counter: personObj.counter,
					sector: personObj.sector,
					organisation: personObj.organisation,
					showOrganisation: personObj.showOrganisation,
				});
			});
			return res.json({ success: true, data: personArray });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

module.exports = router;
