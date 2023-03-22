import express from 'express';
import passport from 'passport';

import { logger } from '../utilities/logger';
import PublisherController from './publisher.controller';
import { publisherService, workflowService, dataRequestService, amendmentService } from './dependency';
import { userIsTeamManager } from '../auth/utils';
import { utils } from '../auth';

const logCategory = 'Publisher';
const publisherController = new PublisherController(publisherService, workflowService, dataRequestService, amendmentService);

const router = express.Router();

// @route   GET api/publishers/:id
// @desc    GET A publishers by :id
// @access  Public
router.get('/:id', logger.logRequestMiddleware({ logCategory, action: 'Viewed a publishers details' }), (req, res) =>
	publisherController.getPublisher(req, res)
);

// @route   GET api/publishers
// @desc    GET all publishers and their ids
// @access  Public
router.get('/', logger.logRequestMiddleware({ logCategory, action: 'Retrieved a list of publishers and their ids' }), (req, res) =>
	publisherController.getAllPublishersAndIds(res)
);

// @route   GET api/publishers/:id/datasets
// @desc    GET all datasets owned by publisher
// @access  Private
router.get(
	'/:id/datasets',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Viewed datasets for a publisher' }),
	(req, res) => publisherController.getPublisherDatasets(req, res)
);

// @route   GET api/publishers/:id/dataaccessrequests
// @desc    GET all data access requests to a publisher
// @access  Private
router.get(
	'/:id/dataaccessrequests',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Viewed data access requests for a publisher' }),
	(req, res) => publisherController.getPublisherDataAccessRequests(req, res)
);

// @route   GET api/publishers/:id/workflows
// @desc    GET workflows for publisher
// @access  Private
router.get(
	'/:id/workflows',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Viewed workflows for a publisher' }),
	(req, res) => publisherController.getPublisherWorkflows(req, res)
);

// @route   PATCH /api/publishers/:id/dataUseWidget
// @desc	Update data use widget settings (terms and conditions)
// @access  Public
router.patch('/:id/dataUseWidget', passport.authenticate('jwt'), utils.userIsTeamManager(), (req, res) =>
	publisherController.updateDataUseWidget(req, res)
);
router.patch('/dataRequestModalContent/:id', passport.authenticate('jwt'), utils.userIsTeamManager(), (req, res) =>
	publisherController.updateDataRequestModalContent(req, res)
);

router.patch(
	'/:id/questionbank',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Updating question bank enabled / disabled' }),
	(req, res) => publisherController.updateQuestionBank(req, res)
);

module.exports = router;
