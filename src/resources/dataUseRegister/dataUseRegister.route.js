import express from 'express';

import passport from 'passport';
import { logger } from '../utilities/logger';
import { dataUseRegisterService } from './dependency';
import { activityLogService } from '../activitylog/dependency';
import DataUseRegisterController from './dataUseRegister.controller';
import { validateUpdateRequest, authorizeUpdate, validateUploadRequest, authorizeUpload } from '../../middlewares';

const router = express.Router();
const dataUseRegisterController = new DataUseRegisterController(dataUseRegisterService, activityLogService);
const logCategory = 'dataUseRegister';

router.get('/search', logger.logRequestMiddleware({ logCategory, action: 'Search uploaded data uses' }), (req, res) =>
	dataUseRegisterController.searchDataUseRegisters(req, res)
);

// @route   GET /api/v2/data-use-registers/id
// @desc    Returns a dataUseRegister based on dataUseRegister ID provided
// @access  Public
router.get('/:id', logger.logRequestMiddleware({ logCategory, action: 'Viewed dataUseRegister data' }), (req, res) =>
	dataUseRegisterController.getDataUseRegister(req, res)
);

// @route   GET /api/v2/data-use-registers
// @desc    Returns a collection of dataUseRegisters based on supplied query parameters
// @access  Public
router.get(
	'/',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Viewed dataUseRegisters data' }),
	(req, res) => dataUseRegisterController.getDataUseRegisters(req, res)
);

// @route   PATCH /api/v2/data-use-registers/counter
// @desc    Updates the data use register counter for page views
// @access  Public
router.patch('/counter', logger.logRequestMiddleware({ logCategory, action: 'Data use counter update' }), (req, res) =>
	dataUseRegisterController.updateDataUseRegisterCounter(req, res)
);

// @route   PATCH /api/v2/data-use-registers/id
// @desc    Update the content of the data user register based on dataUseRegister ID provided
// @access  Public
router.patch(
	'/:id',
	passport.authenticate('jwt'),
	validateUpdateRequest,
	authorizeUpdate,
	logger.logRequestMiddleware({ logCategory, action: 'Updated dataUseRegister data' }),
	(req, res) => dataUseRegisterController.updateDataUseRegister(req, res)
);

// @route   POST /api/v2/data-use-registers/check
// @desc    Check the submitted data uses for duplicates and returns links to Gatway entities (datasets, users)
// @access  Public
router.post('/check', passport.authenticate('jwt'), logger.logRequestMiddleware({ logCategory, action: 'Check data uses' }), (req, res) =>
	dataUseRegisterController.checkDataUseRegister(req, res)
);

// @route   POST /api/v2/data-use-registers/upload
// @desc    Accepts a bulk upload of data uses with built-in duplicate checking and rejection
// @access  Public
router.post(
	'/upload',
	passport.authenticate('jwt'),
	validateUploadRequest,
	authorizeUpload,
	logger.logRequestMiddleware({ logCategory, action: 'Bulk uploaded data uses' }),
	(req, res) => dataUseRegisterController.uploadDataUseRegisters(req, res)
);

module.exports = router;
