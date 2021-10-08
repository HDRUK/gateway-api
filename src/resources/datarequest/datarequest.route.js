import express from 'express';
import passport from 'passport';
import _ from 'lodash';
import multer from 'multer';
import { param } from 'express-validator';

import { logger } from '../utilities/logger';
import DataRequestController from './datarequest.controller';
import AmendmentController from './amendment/amendment.controller';
import { dataRequestService, workflowService, amendmentService, topicService, messageService, activityLogService } from './dependency';
import { dataUseRegisterService } from '../dataUseRegister/dependency';

const fs = require('fs');
const path = './tmp';
const storage = multer.diskStorage({
	destination: function (req, file, cb) {
		if (!fs.existsSync(path)) {
			fs.mkdirSync(path);
		}
		cb(null, path);
	},
});
const multerMid = multer({ storage: storage });
const logCategory = 'Data Access Request';
const dataRequestController = new DataRequestController(
	dataRequestService,
	workflowService,
	amendmentService,
	topicService,
	messageService,
	activityLogService,
	dataUseRegisterService
);
const amendmentController = new AmendmentController(amendmentService, dataRequestService, activityLogService);
const router = express.Router();

// @route   GET api/v1/data-access-request
// @desc    GET Access requests for user
// @access  Private - Applicant (Gateway User) and Custodian Manager/Reviewer
router.get(
	'/',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Viewed personal Data Access Request dashboard' }),
	(req, res) => dataRequestController.getAccessRequestsByUser(req, res)
);

// @route   GET api/v1/data-access-request/:id
// @desc    GET a single data access request by Id
// @access  Private - Applicant (Gateway User) and Custodian Manager/Reviewer
router.get(
	'/:id',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Opened a Data Access Request application' }),
	(req, res) => dataRequestController.getAccessRequestById(req, res)
);

// @route   GET api/v1/data-access-request/datasets/:datasetIds
// @desc    GET Access request with multiple datasets for user
// @access  Private - Applicant (Gateway User) and Custodian Manager/Reviewer
router.get(
	'/datasets/:datasetIds',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Opened a Data Access Request application via multiple datasets' }),
	(req, res) => dataRequestController.getAccessRequestByUserAndMultipleDatasets(req, res)
);

// @route   POST api/v1/data-access-request/:id/clone
// @desc    Clone an existing application forms answers into a new one potentially for a different custodian
// @access  Private - Applicant
router.post(
	'/:id/clone',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Cloning a Data Access Request application' }),
	(req, res) => dataRequestController.cloneApplication(req, res)
);

// @route   POST api/v1/data-access-request/:id
// @desc    Submit request record
// @access  Private - Applicant (Gateway User)
router.post(
	'/:id',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Submitting a Data Access Request application' }),
	(req, res) => dataRequestController.submitAccessRequestById(req, res)
);

// @route   PATCH api/v1/data-access-request/:id
// @desc    Update application passing single object to update database entry with specified key
// @access  Private - Applicant (Gateway User)
router.patch(
	'/:id',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Updating a single question answer in a Data Access Request application' }),
	(req, res) => dataRequestController.updateAccessRequestDataElement(req, res)
);

// @route   DELETE api/v1/data-access-request/:id
// @desc    Delete an application in a presubmissioin
// @access  Private - Applicant
router.delete(
	'/:id',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Deleting a presubmission Data Access Request application' }),
	(req, res) => dataRequestController.deleteDraftAccessRequest(req, res)
);

// @route   POST api/v1/data-access-request/:id/upload
// @desc    POST application files to scan bucket
// @access  Private - Applicant (Gateway User / Custodian Manager)
router.post(
	'/:id/upload',
	passport.authenticate('jwt'),
	multerMid.array('assets'),
	logger.logRequestMiddleware({ logCategory, action: 'Uploading a file to a Data Access Request application' }),
	(req, res) => dataRequestController.uploadFiles(req, res)
);

// @route   PUT api/v1/data-access-request/:id/assignworkflow
// @desc    Update access request workflow
// @access  Private - Custodian Manager
router.put(
	'/:id/assignworkflow',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Assigning a workflow to a Data Access Request application' }),
	(req, res) => dataRequestController.assignWorkflow(req, res)
);

// @route   GET api/v1/data-access-request/:id/file/:fileId
// @desc    GET
// @access  Private
router.get(
	'/:id/file/:fileId',
	param('id').customSanitizer(value => {
		return value;
	}),
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Requested an uploaded file from a Data Access Request application' }),
	(req, res) => dataRequestController.getFile(req, res)
);

// @route   GET api/v1/data-access-request/:id/file/:fileId/status
// @desc    GET Status of a file
// @access  Private
router.get(
	'/:id/file/:fileId/status',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Requested the status of an uploaded file to a Data Access Request application' }),
	(req, res) => dataRequestController.getFileStatus(req, res)
);

// @route   PUT api/v1/data-access-request/:id/deletefile
// @desc    Update access request deleting a file by Id
// @access  Private - Applicant (Gateway User)
router.put(
	'/:id/deletefile',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Deleting an uploaded file from a Data Access Request application' }),
	(req, res) => dataRequestController.updateAccessRequestDeleteFile(req, res)
);

// @route   POST api/v1/data-access-request/:id/updatefilestatus
// @desc    Update the status of a file.
// @access  Private
router.post(
	'/:id/file/:fileId/status',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Updating the status of an uploaded file to a Data Access Request application' }),
	(req, res) => dataRequestController.updateFileStatus(req, res)
);

// @route   POST api/v1/data-access-request/:id/email
// @desc    Mail a Data Access Request information in presubmission
// @access  Private - Applicant
router.post(
	'/:id/email',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Emailing a presubmission Data Access Request application to the requesting user' }),
	(req, res) => dataRequestController.mailDataAccessRequestInfoById(req, res)
);

// @route   POST api/v1/data-access-request/:id/notify
// @desc    External facing endpoint to trigger notifications for Data Access Request workflows
// @access  Private
router.post(
	'/:id/notify',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({
		logCategory,
		action: 'Notifying any outstanding or upcoming SLA breaches for review phases against a Data Access Request application',
	}),
	(req, res) => dataRequestController.notifyAccessRequestById(req, res)
);

// @route   POST api/v1/data-access-request/:id/actions
// @desc    Perform an action on a presubmitted application form e.g. add/remove repeatable section
// @access  Private - Applicant
router.post(
	'/:id/actions',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Performing a user triggered action on a Data Access Request application' }),
	(req, res) => dataRequestController.performAction(req, res)
);

// @route   PUT api/v1/data-access-request/:id
// @desc    Update request record by Id for status changes
// @access  Private - Custodian Manager and Applicant (Gateway User)
router.put(
	'/:id',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Updating the status of a Data Access Request application' }),
	(req, res) => dataRequestController.updateAccessRequestById(req, res)
);

// @route   PUT api/v1/data-access-request/:id/stepoverride
// @desc    Update access request with current step overriden (manager ends current phase regardless of votes cast)
// @access  Private - Custodian Manager
router.put(
	'/:id/stepoverride',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Overriding a workflow phase for a Data Access Request application' }),
	(req, res) => dataRequestController.updateAccessRequestStepOverride(req, res)
);

// @route   PUT api/v1/data-access-request/:id/vote
// @desc    Update access request with user vote
// @access  Private - Custodian Reviewer/Manager
router.put(
	'/:id/vote',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Voting against a review phase for a Data Access Request application' }),
	(req, res) => dataRequestController.updateAccessRequestReviewVote(req, res)
);

// @route   PUT api/v1/data-access-request/:id/startreview
// @desc    Update access request with review started
// @access  Private - Custodian Manager
router.put(
	'/:id/startreview',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Starting the review process for a Data Access Request application' }),
	(req, res) => dataRequestController.updateAccessRequestStartReview(req, res)
);

// @route   POST api/v1/data-access-request/:id/amendments
// @desc    Create or remove amendments from DAR
// @access  Private - Custodian Reviewer/Manager
router.post(
	'/:id/amendments',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Creating or removing an amendment against a Data Access Request application' }),
	(req, res) => amendmentController.setAmendment(req, res)
);

// @route   POST api/v1/data-access-request/:id/requestAmendments
// @desc    Submit a batch of requested amendments back to the form applicant(s)
// @access  Private - Manager
router.post(
	'/:id/requestAmendments',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Requesting a batch of amendments to a Data Access Request application' }),
	(req, res) => amendmentController.requestAmendments(req, res)
);

// @route   PUT api/v1/data-access-request/:id/share
// @desc    Update share flag for application
// @access  Private - Applicant
router.put(
	'/:id/share',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Update share flag for application' }),
	(req, res) => dataRequestController.updateSharedDARFlag(req, res)
);

// @route   GET api/v1/data-access-request/:id/messages
// @desc    Get messages or notes for application
// @access  Private - Applicant/Custodian Reviewer/Manager
router.get(
	'/:id/messages',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Get messages or notes for application' }),
	(req, res) => dataRequestController.getMessages(req, res)
);

// @route   POST api/v1/data-access-request/:id/messages
// @desc    Submitting a message or note
// @access  Private - Applicant/Custodian Reviewer/Manager
router.post(
	'/:id/messages',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Submitting a message or note' }),
	(req, res) => dataRequestController.submitMessage(req, res)
);

// @route   POST api/v1/data-access-request/:id/amend
// @desc    Trigger amendment action on a data access request application, creating a new major version unlocked for editing
// @access  Private - Applicant
router.post(
	'/:id/amend',
	passport.authenticate('jwt'),
	logger.logRequestMiddleware({ logCategory, action: 'Triggering an amendment to a Data Access Request application' }),
	(req, res) => dataRequestController.createAmendment(req, res)
);

module.exports = router;
