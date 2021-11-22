import express from 'express';
import passport from 'passport';
import { authUtils } from '../../utils';
import { ROLES } from '../user/user.roles';
import { MessagesModel } from './message.model';

const messageController = require('../message/message.controller');

// by default route has access to its own, allows access to parent param
const router = express.Router({ mergeParams: true });

router.get('/numberofunread/admin/:personID', passport.authenticate('jwt'), authUtils.checkIsInRole(ROLES.Admin), async (req, res) => {
	var idString = '';
	let countUnreadMessages = 0;
	if (req.params.personID) {
		idString = parseInt(req.params.personID);
	}

	var m = MessagesModel.aggregate([
		{ $match: { $and: [{ $or: [{ messageTo: idString }, { messageTo: 0 }] }] } },
		{ $sort: { createdDate: -1 } },
		{ $lookup: { from: 'tools', localField: 'messageObjectID', foreignField: 'id', as: 'tool' } },
	]).limit(50);
	m.exec((err, data) => {
		if (err) {
			return res.json({ success: false, error: err });
		} else {
			Array.prototype.forEach.call(data, element => {
				if (element.isRead === 'false') {
					countUnreadMessages++;
				}
			});
			return res.json({ countUnreadMessages });
		}
	});
});

router.get('/numberofunread/:personID', passport.authenticate('jwt'), authUtils.checkIsInRole(ROLES.Creator), async (req, res) => {
	var idString = '';
	let countUnreadMessages = 0;
	if (req.params.personID) {
		idString = parseInt(req.params.personID);
	}

	if (req.query.id) {
		idString = parseInt(req.query.id);
	}
	var m = MessagesModel.aggregate([
		{ $match: { $and: [{ messageTo: idString }] } },
		{ $sort: { createdDate: -1 } },
		{ $lookup: { from: 'tools', localField: 'messageObjectID', foreignField: 'id', as: 'tool' } },
	]).limit(50);
	m.exec((err, data) => {
		if (err) {
			return res.json({ success: false, error: err });
		} else {
			Array.prototype.forEach.call(data, element => {
				if (element.isRead === 'false') {
					countUnreadMessages++;
				}
			});
			return res.json({ countUnreadMessages });
		}
	});
});

router.get('/:personID', passport.authenticate('jwt'), authUtils.checkIsInRole(ROLES.Creator), async (req, res) => {
	var idString = '';

	if (req.params.personID) {
		idString = parseInt(req.params.personID);
	}
	var m = MessagesModel.aggregate([
		{ $match: { $and: [{ messageTo: idString }] } },
		{ $sort: { createdDate: -1 } },
		{ $lookup: { from: 'tools', localField: 'messageObjectID', foreignField: 'id', as: 'tool' } },
		{ $lookup: { from: 'course', localField: 'messageObjectID', foreignField: 'id', as: 'course' } },
	]).limit(50);
	m.exec((err, data) => {
		if (err) return res.json({ success: false, error: err });
		return res.json({ success: true, newData: data });
	});
});

/**
 * {get} /messages Messages
 *
 * Return list of messages
 */
router.get('/admin/:personID', passport.authenticate('jwt'), authUtils.checkIsInRole(ROLES.Admin), async (req, res) => {
	var idString = '';

	if (req.params.personID) {
		idString = parseInt(req.params.personID);
	}

	var m = MessagesModel.aggregate([
		{ $match: { $and: [{ $or: [{ messageTo: idString }, { messageTo: 0 }] }] } },
		{ $sort: { createdDate: -1 } },
		{ $lookup: { from: 'tools', localField: 'messageObjectID', foreignField: 'id', as: 'tool' } },
		{ $lookup: { from: 'course', localField: 'messageObjectID', foreignField: 'id', as: 'course' } },
	]).limit(50);
	m.exec((err, data) => {
		if (err) return res.json({ success: false, error: err });
		return res.json({ success: true, newData: data });
	});
});

router.post('/markasread', passport.authenticate('jwt'), authUtils.checkIsInRole(ROLES.Admin, ROLES.Creator), async (req, res) => {
	const messageIds = req.body;

	MessagesModel.updateMany({ messageID: { $in: messageIds } }, { isRead: true }, err => {
		if (err) return res.json({ success: false, error: err });
		return res.json({ success: true });
	});
});

// @route   POST api/messages
// @desc    POST A message
// @access  Private
router.post('/', passport.authenticate('jwt'), authUtils.checkIsInRole(ROLES.Admin, ROLES.Creator), messageController.createMessage);

// @route   DELETE api/messages/:id
// @desc    DELETE Delete a message
// @access  Private
router.delete('/:id', passport.authenticate('jwt'), authUtils.checkIsInRole(ROLES.Admin, ROLES.Creator), messageController.deleteMessage);

// @route   PUT api/messages
// @desc    PUT Update a message
// @access  Private
router.put('/', passport.authenticate('jwt'), authUtils.checkIsInRole(ROLES.Admin, ROLES.Creator), messageController.updateMessage);

// @route   GET api/messages/unread/count
// @desc    GET the number of unread messages for a user
// @access  Private
router.get('/unread/count', passport.authenticate('jwt'), messageController.getUnreadMessageCount);

module.exports = router;
