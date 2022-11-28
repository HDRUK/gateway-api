import express from 'express';
import passport from 'passport';

const ReviewController = require('./review.controller');

const router = express.Router();

// @router   GET /api/v3/reviews/:reviewId?
// @desc     get all reviews or find reviews by reviewId
// @access   Private
router.get('/:reviewId?', passport.authenticate('jwt'), (req, res) => ReviewController.handleReviews(req, res));

module.exports = router;
