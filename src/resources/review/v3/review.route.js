import express from 'express';
import passport from 'passport';
import { ROLES } from '../../user/user.roles';
import { utils } from '../../auth';

const ReviewController = require('./review.controller');

const router = express.Router();

// @router   GET /api/v1/reviews/admin/pending
// @desc     get reviews in pending for user.role = Admin
// @access   Public
router.get('/admin/pending', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Admin), (req, res) => ReviewController.handleReviewsAdminPending(req, res));

// @router   GET /api/v1/reviews/pending
// @desc     get reviews in pending for user.role = Creator by reviewerID
// @access   Public
router.get('/pending', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Creator), (req, res) => ReviewController.handleReviewsCreatorPending(req, res));

// @router   GET /api/v1/reviews
// @desc     find reviews by reviewID
// @access   Public
router.get('/', async (req, res) => ReviewController.handleReviewsByReviewId(req, res));

module.exports = router;
