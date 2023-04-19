import express from 'express';
import passport from 'passport';
import { utils } from '../auth';
import { ROLES } from '../user/user.roles';
import { Data } from '../tool/data.model';
import { Collections } from '../collections/collections.model';
import { MessagesModel } from '../message/message.model';
import { createDiscourseTopic } from '../discourse/discourse.service';
import { UserModel } from '../user/user.model';
import emailGenerator from '../utilities/emailGenerator.util';
import helper from '../utilities/helper.util';

const router = express.Router();
const hdrukEmail = `enquiry@healthdatagateway.org`;

/**
 * {delete} /api/v1/accounts
 *
 * Return list of tools, this can be with filters or/and search criteria. This will also include pagination on results.
 * The free word search criteria can be improved on with node modules that specialize with searching i.e. js-search
 */
router.delete('/', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Admin, ROLES.Creator), async (req, res) => {
	const { id } = req.body;
	Data.findOneAndDelete({ id: { $eq: id } }, err => {
		if (err) return res.send(err);
		return res.json({ success: true });
	});
});

/**
 * {get} /api/v1/accounts/admin
 *
 * Return list of tools, this can be with filters or/and search criteria. This will also include pagination on results.
 * The free word search criteria can be improved on with node modules that specialize with searching i.e. js-search
 */
router.get('/admin', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Admin), async (req, res) => {
	var result;
	//var startIndex = 0;
	//var maxResults = 25;
	var typeString = '';

	/* if (req.query.startIndex) {
		startIndex = req.query.startIndex;
	}
	if (req.query.maxResults) {
		maxResults = req.query.maxResults;
	} */
	if (req.query.type) {
		typeString = req.query.type;
	}

	var q = Data.aggregate([
		{ $match: { $and: [{ type: typeString }] } },
		{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
		{ $sort: { updatedAt: -1 } },
	]); //.skip(parseInt(startIndex)).limit(parseInt(maxResults));
	q.exec((err, data) => {
		if (err) return res.json({ success: false, error: err });
		result = res.json({ success: true, data: data });
	});

	return result;
});

/**
 * {get} /api/v1/accounts
 *
 * Return list of tools, this can be with filters or/and search criteria. This will also include pagination on results.
 * The free word search criteria can be improved on with node modules that specialize with searching i.e. js-search
 */
router.get('/', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Admin, ROLES.Creator), async (req, res) => {
	var result;
	var startIndex = 0;
	var maxResults = 25;
	var typeString = '';
	var idString = '';

	if (req.query.startIndex) {
		startIndex = req.query.startIndex;
	}
	if (req.query.maxResults) {
		maxResults = req.query.maxResults;
	}
	if (req.query.type) {
		typeString = req.query.type;
	}
	if (req.query.id) {
		idString = req.query.id;
	}

	var q = Data.aggregate([
		{ $match: { $and: [{ type: typeString }, { authors: parseInt(idString) }] } },
		{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
		{ $sort: { updatedAt: -1 } },
	]); //.skip(parseInt(startIndex)).limit(parseInt(maxResults));
	q.exec((err, data) => {
		if (err) return res.json({ success: false, error: err });
		result = res.json({ success: true, data: data });
	});
	return result;
});

/**
 * {get} /api/v1/accounts/collections
 *
 * Returns list of collections.
 */
router.get('/collections', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Admin, ROLES.Creator), async (req, res) => {
	var result;
	var startIndex = 0;
	var maxResults = 25;
	var idString = '';

	if (req.query.startIndex) {
		startIndex = req.query.startIndex;
	}
	if (req.query.maxResults) {
		maxResults = req.query.maxResults;
	}
	if (req.query.id) {
		idString = req.query.id;
	}

	var q = Collections.aggregate([
		{ $match: { $and: [{ authors: parseInt(idString) }] } },
		{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
		{ $sort: { updatedAt: -1 } },
	]); //.skip(parseInt(startIndex)).limit(parseInt(maxResults));
	q.exec((err, data) => {
		if (err) return res.json({ success: false, error: err });

		data.map(dat => {
			dat.persons = helper.hidePrivateProfileDetails(dat.persons);
		});
		result = res.json({ success: true, data: data });
	});
	return result;
});

/**
 * {put} /api/v1/accounts/status
 *
 * Return list of tools, this can be with filters or/and search criteria. This will also include pagination on results.
 * The free word search criteria can be improved on with node modules that specialize with searching i.e. js-search
 */
router.put('/status', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Admin), async (req, res) => {
	const { id, activeflag } = req.body;
	try {
		await Data.findOneAndUpdate({ id: { $eq: id } }, { $set: { activeflag: activeflag } });
		const tool = await Data.findOne({ id: { $eq: id } });

		if (!tool) {
			return res.status(400).json({ success: false, error: 'Tool not found' });
		}

		if (tool.authors) {
			tool.authors.forEach(async authorId => {
				await createMessage(authorId, id, tool.name, tool.type, activeflag);
			});
		}
		await createMessage(0, id, tool.name, tool.type, activeflag);

		if (!tool.discourseTopicId && tool.activeflag === 'active') {
			await createDiscourseTopic(tool);
		}

		// Send email notifications to all admins and authors who have opted in
		await sendEmailNotifications(tool, activeflag);

		return res.json({ success: true });
	} catch (err) {
		process.stdout.write(`ACCOUNT - status : ${err.message}\n`);
		return res.status(500).json({ success: false, error: err });
	}
});

module.exports = router;

async function createMessage(authorId, toolId, toolName, toolType, activeflag) {
	let message = new MessagesModel();
	const toolLink = process.env.homeURL + '/tool/' + toolId;

	if (activeflag === 'active') {
		message.messageType = 'approved';
		message.messageDescription = `Your ${toolType} ${toolName} has been approved and is now live ${toolLink}`;
	} else if (activeflag === 'archive') {
		message.messageType = 'rejected';
		message.messageDescription = `Your ${toolType} ${toolName} has been rejected ${toolLink}`;
	}
	message.messageID = parseInt(Math.random().toString().replace('0.', ''));
	message.messageTo = authorId;
	message.messageObjectID = toolId;
	message.messageSent = Date.now();
	message.isRead = false;
	await message.save();
}

async function sendEmailNotifications(tool, activeflag) {
	let subject;
	let html;
	// 1. Generate URL for linking tool in email
	const toolLink = process.env.homeURL + '/tool/' + tool.id + '/' + tool.name;

	// 2. Build HTML for email
	if (activeflag === 'active') {
		subject = `Your ${tool.type} ${tool.name} has been approved and is now live`;
		html = `Your ${tool.type} ${tool.name} has been approved and is now live <br /><br />  ${toolLink}`;
	} else if (activeflag === 'archive') {
		subject = `Your ${tool.type} ${tool.name} has been rejected`;
		html = `Your ${tool.type} ${tool.name} has been rejected <br /><br />  ${toolLink}`;
	}

	// 3. Query Db for all admins or authors of the tool who have opted in to email updates
	var q = UserModel.aggregate([
		// Find all users who are admins or authors of this tool
		{ $match: { $or: [{ role: 'Admin' }, { id: { $in: tool.authors } }] } },
		// Perform lookup to check opt in/out flag in tools schema
		{ $lookup: { from: 'tools', localField: 'id', foreignField: 'id', as: 'tool' } },
		// Filter out any user who has opted out of email notifications
		{ $match: { 'tool.emailNotifications': true } },
		// Reduce response payload size to required fields
		{ $project: { _id: 1, firstname: 1, lastname: 1, email: 1, role: 1, 'tool.emailNotifications': 1 } },
	]);

	// 4. Use the returned array of email recipients to generate and send emails with SendGrid
	q.exec((err, emailRecipients) => {
		if (err) {
			return new Error({ success: false, error: err });
		}
		emailGenerator.sendEmail(emailRecipients, `${hdrukEmail}`, subject, html, false);
	});
}
