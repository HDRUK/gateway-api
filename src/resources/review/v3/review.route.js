import express from 'express';
import passport from 'passport';

const ReviewController = require('./review.controller');

const router = express.Router();

// @router   GET /api/v3/reviews/:reviewId?
// @desc     get all reviews or find reviews by reviewId
// @access   Private
router.get('/:reviewId?', passport.authenticate('jwt'), (req, res) => ReviewController.getReviews(req, res));


// @router      PATCH /api/v3/reviews/:reviewId
// @bodyParam   {string} activeflag can be: active/approve (approve will be converted in active), reject, archive
// @desc        get all reviews or find reviews by reviewId
// @access      Private
router.patch('/:reviewId', passport.authenticate('jwt'), (req, res) => ReviewController.updateReviews(req, res));

module.exports = router;
