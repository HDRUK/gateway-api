import express from 'express';
import passport from 'passport';
import _ from 'lodash';
import multer from 'multer';
import { param } from 'express-validator';
const amendmentController = require('./amendment/amendment.controller');
const datarequestController = require('./datarequest.controller');
const fs = require('fs');
const path = './tmp';
const storage = multer.diskStorage({
  destination: function (req, file, cb) {
    if (!fs.existsSync(path)) {
      fs.mkdirSync(path);
    }
    cb(null, path)
  }
})
const multerMid = multer({ storage: storage });

const router = express.Router();

// @route   GET api/v1/data-access-request
// @desc    GET Access requests for user
// @access  Private - Applicant (Gateway User) and Custodian Manager/Reviewer
router.get('/', passport.authenticate('jwt'), datarequestController.getAccessRequestsByUser);

// @route   GET api/v1/data-access-request/:requestId
// @desc    GET a single data access request by Id
// @access  Private - Applicant (Gateway User) and Custodian Manager/Reviewer
router.get('/:requestId', passport.authenticate('jwt'), datarequestController.getAccessRequestById);

// @route   GET api/v1/data-access-request/dataset/:datasetId
// @desc    GET Access request for user
// @access  Private - Applicant (Gateway User) and Custodian Manager/Reviewer
router.get('/dataset/:dataSetId', passport.authenticate('jwt'), datarequestController.getAccessRequestByUserAndDataset);

// @route   GET api/v1/data-access-request/datasets/:datasetIds
// @desc    GET Access request with multiple datasets for user
// @access  Private - Applicant (Gateway User) and Custodian Manager/Reviewer
router.get('/datasets/:datasetIds', passport.authenticate('jwt'), datarequestController.getAccessRequestByUserAndMultipleDatasets);

// @route   GET api/v1/data-access-request/:id/file/:fileId
// @desc    GET 
// @access  Private
router.get('/:id/file/:fileId', param('id').customSanitizer(value => {return value}), passport.authenticate('jwt'), datarequestController.getFile);

// @route   GET api/v1/data-access-request/:id/file/:fileId/status
// @desc    GET Status of a file 
// @access  Private
router.get('/:id/file/:fileId/status', passport.authenticate('jwt'), datarequestController.getFileStatus);

// @route   PATCH api/v1/data-access-request/:id
// @desc    Update application passing single object to update database entry with specified key
// @access  Private - Applicant (Gateway User)
router.patch('/:id', passport.authenticate('jwt'), datarequestController.updateAccessRequestDataElement);

// @route   PUT api/v1/data-access-request/:id
// @desc    Update request record by Id for status changes
// @access  Private - Custodian Manager and Applicant (Gateway User)
router.put('/:id', passport.authenticate('jwt'), datarequestController.updateAccessRequestById);

// @route   PUT api/v1/data-access-request/:id/assignworkflow
// @desc    Update access request workflow
// @access  Private - Custodian Manager
router.put('/:id/assignworkflow', passport.authenticate('jwt'), datarequestController.assignWorkflow);

// @route   PUT api/v1/data-access-request/:id/vote
// @desc    Update access request with user vote
// @access  Private - Custodian Reviewer/Manager
router.put('/:id/vote', passport.authenticate('jwt'), datarequestController.updateAccessRequestReviewVote);

// @route   PUT api/v1/data-access-request/:id/startreview
// @desc    Update access request with review started
// @access  Private - Custodian Manager
router.put('/:id/startreview', passport.authenticate('jwt'), datarequestController.updateAccessRequestStartReview);

// @route   PUT api/v1/data-access-request/:id/stepoverride
// @desc    Update access request with current step overriden (manager ends current phase regardless of votes cast)
// @access  Private - Custodian Manager
router.put('/:id/stepoverride', passport.authenticate('jwt'), datarequestController.updateAccessRequestStepOverride);

// @route   PUT api/v1/data-access-request/:id/deletefile
// @desc    Update access request deleting a file by Id 
// @access  Private - Applicant (Gateway User)
router.put('/:id/deletefile', passport.authenticate('jwt'), datarequestController.updateAccessRequestDeleteFile);

// @route   POST api/v1/data-access-request/:id/upload
// @desc    POST application files to scan bucket
// @access  Private - Applicant (Gateway User / Custodian Manager) 
router.post('/:id/upload', passport.authenticate('jwt'), multerMid.array('assets'), datarequestController.uploadFiles);

// @route   POST api/v1/data-access-request/:id/amendments
// @desc    Create or remove amendments from DAR
// @access  Private - Custodian Reviewer/Manager
router.post('/:id/amendments', passport.authenticate('jwt'), amendmentController.setAmendment);

// @route   POST api/v1/data-access-request/:id/requestAmendments
// @desc    Submit a batch of requested amendments back to the form applicant(s)
// @access  Private - Manager
router.post('/:id/requestAmendments', passport.authenticate('jwt'), amendmentController.requestAmendments);

// @route   POST api/v1/data-access-request/:id/actions
// @desc    Perform an action on a presubmitted application form e.g. add/remove repeatable section
// @access  Private - Applicant
router.post('/:id/actions', passport.authenticate('jwt'), datarequestController.performAction);

// @route   POST api/v1/data-access-request/:id
// @desc    Submit request record
// @access  Private - Applicant (Gateway User)
router.post('/:id', passport.authenticate('jwt'), datarequestController.submitAccessRequestById);

// @route   POST api/v1/data-access-request/:id/notify
// @desc    External facing endpoint to trigger notifications for Data Access Request workflows
// @access  Private
router.post('/:id/notify', passport.authenticate('jwt'), datarequestController.notifyAccessRequestById);

// @route   POST api/v1/data-access-request/:id/updatefilestatus
// @desc    Update the status of a file.
// @access  Private
router.post('/:id/file/:fileId/status', passport.authenticate('jwt'), datarequestController.updateFileStatus);

// @route   POST api/v1/data-access-request/:id/email
// @desc    Mail a Data Access Request information in presubmission 
// @access  Private - Applicant
router.post('/:id/email', passport.authenticate('jwt'), datarequestController.mailDataAccessRequestInfoById);

module.exports = router;