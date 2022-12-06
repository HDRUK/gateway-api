import express from 'express';
import passport from 'passport';
const MessageController = require('./message.controller');
const router = express.Router();

// @router   GET /api/v3/messages/:personId
// @desc     get messages by person id for users.role === Admin or users.role === Creator
// @access   Private
router.get('/:personId', passport.authenticate('jwt'), (req, res) => MessageController.getMessagesByPersonId(req, res));

// @router   GET /api/v3/messages/:personId/unread/count
// @desc     count number of unread messages by person id for users.role === Admin or users.role === Creator
// @access   Private
router.get('/:personId/unread/count', passport.authenticate('jwt'), (req, res) => MessageController.getCountUnreadMessagesByPersonId(req, res));

// @route   GET api/v3/messages/unread/count
// @desc    GET the number of unread messages for a user
// @access  Private
router.get('/unread/count', passport.authenticate('jwt'), (req, res) => MessageController.getUnreadMessageCount(req, res));

// @route   POST api/v3/messages
// @desc    POST a message - create an message/enquire; user need to be Admin or Creator
// @access  Private
router.post('/', passport.authenticate('jwt'), (req, res) => MessageController.createMessage(req, res));

// @route   PUT api/messages
// @desc    PUT Update a message; user need to be Admin or Creator
// @access  Private
router.put('/', passport.authenticate('jwt'), (req, res) => MessageController.updateMessage(req, res));

// @route   DELETE api/v3/messages/:id
// @desc    DELETE Delete a message; user need to be Admin or Creator
// @access  Private
router.delete('/:id', passport.authenticate('jwt'), (req, res) => MessageController.deleteMessage(req, res));

module.exports = router;