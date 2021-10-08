import express from 'express';
import { ROLES } from '../user/user.roles';
import passport from 'passport';
import { utils } from '../auth';
import { Collections } from '../collections/collections.model';
import { Data } from '../tool/data.model';
import { MessagesModel } from '../message/message.model';
import { UserModel } from '../user/user.model';
import helper from '../utilities/helper.util';
import _ from 'lodash';
import escape from 'escape-html';
import {
	getCollectionObjects,
	getCollectionsAdmin,
	getCollections,
	sendEmailNotifications,
	generateCollectionEmailSubject,
} from './collections.repository';
import inputSanitizer from '../utilities/inputSanitizer';
import urlValidator from '../utilities/urlValidator';
import { filtersService } from '../filters/dependency';

const router = express.Router();

// @router   GET /api/v1/collections/getList
// @desc     Returns List of Collections
// @access   Private
router.get('/getList', passport.authenticate('jwt'), async (req, res) => {
	let role = req.user.role;

	if (role === ROLES.Admin) {
		await getCollectionsAdmin(req)
			.then(data => {
				return res.json({ success: true, data });
			})
			.catch(err => {
				return res.json({ success: false, err });
			});
	} else if (role === ROLES.Creator) {
		await getCollections(req)
			.then(data => {
				return res.json({ success: true, data });
			})
			.catch(err => {
				return res.json({ success: false, err });
			});
	}
});

// @router   GET /api/v1/collections/{collectionID}
// @desc     Returns collection based on id
// @access   Public
router.get('/:collectionID', async (req, res) => {
	var q = Collections.aggregate([
		{ $match: { $and: [{ id: parseInt(req.params.collectionID) }] } },

		{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
	]);
	q.exec((err, data) => {
		if (err) return res.json({ success: false, error: err });

		if (_.isEmpty(data)) return res.status(404).send(`Collection not found for Id: ${escape(req.params.collectionID)}`);

		data[0].persons = helper.hidePrivateProfileDetails(data[0].persons);
		return res.json({ success: true, data: data });
	});
});

// @router   GET /api/v1/collections/relatedobjects/{collectionID}
// @desc     Returns related resources for collection based on id
// @access   Public
router.get('/relatedobjects/:collectionID', async (req, res) => {
	await getCollectionObjects(req)
		.then(data => {
			return res.json({ success: true, data });
		})
		.catch(err => {
			return res.json({ success: false, err });
		});
});

// @router   GET /api/v1/collections/entityid/{entityID}
// @desc     Returns collections that contant the entity id
// @access   Public
router.get('/entityid/:entityID', async (req, res) => {
	let entityID = req.params.entityID;
	let dataVersions = await Data.find({ pid: entityID }, { _id: 0, datasetid: 1 });
	let dataVersionsArray = dataVersions.map(a => a.datasetid);
	dataVersionsArray.push(entityID);

	var q = Collections.aggregate([
		{
			$match: {
				$and: [
					{
						relatedObjects: {
							$elemMatch: {
								$or: [
									{
										objectId: { $in: dataVersionsArray },
									},
									{
										pid: entityID,
									},
								],
							},
						},
					},
					{ publicflag: true },
					{ activeflag: 'active' },
				],
			},
		},
		{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
		{
			$project: { _id: 1, id: 1, name: 1, description: 1, imageLink: 1, relatedObjects: 1, 'persons.firstname': 1, 'persons.lastname': 1 },
		},
	]);

	q.exec((err, data) => {
		if (err) return res.json({ success: false, error: err });
		return res.json({ success: true, data: data });
	});
});

// @router   PUT /api/v1/collections/edit/{id}
// @desc     Edit Collection
// @access   Private
router.put('/edit/:id', passport.authenticate('jwt'), utils.checkAllowedToAccess('collection'), async (req, res) => {
	let id = req.params.id;
	let { name, description, imageLink, authors, relatedObjects, publicflag, keywords, previousPublicFlag, collectionCreator } = req.body;
	imageLink = urlValidator.validateURL(imageLink);
	let updatedon = Date.now();

	let collectionId = parseInt(id);

	await Collections.findOneAndUpdate(
		{ id: { $eq: collectionId } },
		{
			name: inputSanitizer.removeNonBreakingSpaces(name),
			description: inputSanitizer.removeNonBreakingSpaces(description),
			imageLink,
			authors,
			relatedObjects,
			publicflag,
			keywords,
			updatedon,
		},
		err => {
			if (err) {
				return res.json({ success: false, error: err });
			}
		}
	).then(() => {
		filtersService.optimiseFilters('collection');
		return res.json({ success: true });
	});

	await Collections.find({ id: collectionId }, { publicflag: 1, id: 1, activeflag: 1, authors: 1, name: 1 }).then(async res => {
		if (previousPublicFlag === false && publicflag === true) {
			await sendEmailNotifications(res[0], res[0].activeflag, collectionCreator, true);

			if (res[0].authors) {
				res[0].authors.forEach(async authorId => {
					await createMessage(authorId, res[0], res[0].activeflag, collectionCreator, true);
				});
			}

			await createMessage(0, res[0], res[0].activeflag, collectionCreator, true);
		}
	});
});

// @router   POST /api/v1/collections/add
// @desc     Add Collection
// @access   Private
router.post('/add', passport.authenticate('jwt'), async (req, res) => {
	let collections = new Collections();

	const collectionCreator = req.body.collectionCreator;

	const { name, description, imageLink, authors, relatedObjects, publicflag, keywords } = req.body;

	collections.id = parseInt(Math.random().toString().replace('0.', ''));
	collections.name = inputSanitizer.removeNonBreakingSpaces(name);
	collections.description = inputSanitizer.removeNonBreakingSpaces(description);
	collections.imageLink = imageLink;
	collections.authors = authors;
	collections.relatedObjects = relatedObjects;
	collections.activeflag = 'active';
	collections.publicflag = publicflag;
	collections.keywords = keywords;
	collections.updatedon = Date.now();

	if (collections.authors) {
		collections.authors.forEach(async authorId => {
			await createMessage(authorId, collections, collections.activeflag, collectionCreator);
		});
	}

	await createMessage(0, collections, collections.activeflag, collectionCreator);

	await sendEmailNotifications(collections, collections.activeflag, collectionCreator);

	collections.save(err => {
		if (err) {
			return res.json({ success: false, error: err });
		} else {
			return res.json({ success: true, id: collections.id });
		}
	});
});

// @router   PUT /api/v1/collections/status/{id}
// @desc     Edit Collection
// @access   Private
router.put('/status/:id', passport.authenticate('jwt'), utils.checkAllowedToAccess('collection'), async (req, res) => {
	const collectionId = parseInt(req.params.id);
	let { activeflag } = req.body;
	activeflag = activeflag.toString();

	Collections.findOneAndUpdate({ id: collectionId }, { activeflag }, err => {
		if (err) {
			return res.json({ success: false, error: err });
		}
	}).then(() => {
		filtersService.optimiseFilters('collection');
		return res.json({ success: true });
	});
});

// @router   DELETE /api/v1/collections/delete/{id}
// @desc     Delete Collection
// @access   Private
router.delete('/delete/:id', passport.authenticate('jwt'), utils.checkAllowedToAccess('collection'), async (req, res) => {
	const id = parseInt(req.params.id);
	Collections.findOneAndRemove({ id }, err => {
		if (err) return res.send(err);
		return res.json({ success: true });
	});
});

// eslint-disable-next-line no-undef
module.exports = router;

async function createMessage(authorId, collections, activeflag, collectionCreator, isEdit) {
	let message = new MessagesModel();

	const messageRecipients = await UserModel.find({ $or: [{ role: 'Admin' }, { id: { $in: collections.authors } }] });
	async function saveMessage() {
		message.messageID = parseInt(Math.random().toString().replace('0.', ''));
		message.messageTo = authorId;
		message.messageObjectID = collections.id;
		message.messageSent = Date.now();
		message.isRead = false;
		await message.save();
	}

	if (authorId === 0) {
		message.messageType = 'added collection';
		message.messageDescription = generateCollectionEmailSubject('Admin', collections.publicflag, collections.name, false, isEdit);
		saveMessage();
	}

	for (let messageRecipient of messageRecipients) {
		if (activeflag === 'active' && authorId === messageRecipient.id) {
			message.messageType = 'added collection';
			message.messageDescription = generateCollectionEmailSubject(
				'Creator',
				collections.publicflag,
				collections.name,
				authorId === collectionCreator.id ? true : false,
				isEdit
			);
			saveMessage();
		}
	}
}
