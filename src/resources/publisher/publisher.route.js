import express from 'express';
import passport from 'passport';

const publisherController = require('./publisher.controller');

const router = express.Router();

// @route   GET api/publishers/:id
// @desc    GET A publishers by :id
// @access  Public
router.get('/:id', publisherController.getPublisherById);

// @route   GET api/publishers/:id/datasets
// @desc    GET all datasets owned by publisher
// @access  Private
router.get('/:id/datasets', passport.authenticate('jwt'), publisherController.getPublisherDatasets);

// @route   GET api/publishers/:id/dataaccessrequests
// @desc    GET all data access requests to a publisher
// @access  Private
router.get('/:id/dataaccessrequests', passport.authenticate('jwt'), publisherController.getPublisherDataAccessRequests);

// @route   GET api/publishers/:id/workflows
// @desc    GET workflows for publisher
// @access  Private
router.get('/:id/workflows', passport.authenticate('jwt'), publisherController.getPublisherWorkflows);

module.exports = router;
