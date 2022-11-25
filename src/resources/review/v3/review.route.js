import express from 'express';
import passport from 'passport';

const ReviewController = require('./review.controller');

const router = express.Router();

// @router   GET /api/v1/reviews/:role/pending
// @desc     find reviews in pending based on users.role = Creator / Admin
// @access   Private
router.get('/:role(creator|admin)/pending', passport.authenticate('jwt'), (req, res) => ReviewController.handleReviewsUsersPending(req, res));

// @router   GET /api/v1/reviews
// @desc     find reviews by reviewID
// @access   Public
router.get('/', async (req, res) => ReviewController.handleReviewsByReviewId(req, res));

module.exports = router;
