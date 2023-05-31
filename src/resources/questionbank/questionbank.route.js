import express from 'express';
import passport from 'passport';
import { isUndefined, isEmpty } from 'lodash';
import QuestionbankController from './questionbank.controller';
import { questionbankService } from './dependency';
import { datarequestschemaService } from './../datarequest/schema/dependency';
import { logger } from '../utilities/logger';
import { isUserMemberOfTeamById, isUserMemberOfTeamByName } from '../auth/utils';
import constants from '../utilities/constants.util';
import HttpExceptions from '../../exceptions/HttpExceptions';

const router = express.Router();
const questionbankController = new QuestionbankController(questionbankService);
const logCategory = 'questionbank';

const validateViewRequest = (req, res, next) => {
	const { publisherId } = req.params;

	if (isUndefined(publisherId)) {
		throw new HttpExceptions(`You must provide a valid publisher Id`, 400);
	}

	next();
};

const authorizeViewRequest = (req, res, next) => {
	const requestingUser = req.user;
	const { publisherId } = req.params;

	const authorised = isUserMemberOfTeamById(requestingUser, publisherId);
	const isAdminUser = requestingUser.teams.map(team => team.type).includes(constants.teamTypes.ADMIN);

	if (!authorised && !isAdminUser) {
		throw new HttpExceptions(`You are not authorised to perform this action`, 401);
	}

	next();
};

const validatePostRequest = (req, res, next) => {
	const { schemaId } = req.params;

	if (isUndefined(schemaId)) {
		throw new HttpExceptions(`You must provide a valid data request schema Id`, 400);
	}

	next();
};

const authorizePostRequest = async (req, res, next) => {
	const requestingUser = req.user;
	const { schemaId } = req.params;

	const dataRequestSchema = await datarequestschemaService.getDatarequestschemaById(schemaId);

	if (isEmpty(dataRequestSchema)) {
		throw new HttpExceptions(`The requested data request schema could not be found`, 404);
	}

	const authorised = isUserMemberOfTeamByName(requestingUser, dataRequestSchema.publisher);
	const isAdminUser = requestingUser.teams.map(team => team.type).includes(constants.teamTypes.ADMIN);

	if (!authorised && !isAdminUser) {
		throw new HttpExceptions(`You are not authorised to perform this action`, 401);
	}

	req.body.dataRequestSchema = dataRequestSchema;

	next();
};

// @route   GET /api/v1/questionbanks/publisherId
// @desc    Returns questionbank info belonging to the publisher
// @access  Public
router.get(
	'/:publisherId',
	passport.authenticate('jwt'),
	validateViewRequest,
	authorizeViewRequest,
	logger.logRequestMiddleware({ logCategory, action: 'Viewed questionbank data' }),
	(req, res) => questionbankController.getQuestionbank(req, res)
);

// @route   POST /api/v1/questionbank/schemaId
// @desc    Activate a draft schema creating a jsonSchema from masterSchema
// @access  Public
router.post('/:schemaId', passport.authenticate('jwt'), validatePostRequest, authorizePostRequest, (req, res) =>
	questionbankController.publishSchema(req, res)
);

// @route   PATCH /api/v2/questionbank/publisherId
// @desc	Revert the draft schema to the previous version
// @access  Public
router.patch('/:publisherId', passport.authenticate('jwt'), validateViewRequest, authorizeViewRequest, (req, res) =>
	questionbankController.revertChanges(req, res)
);

module.exports = router;
