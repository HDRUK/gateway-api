import express from 'express';
import CohortController from './cohort.controller';
import { cohortService } from './dependency';
import { logger } from '../utilities/logger';

const router = express.Router();
const cohortController = new CohortController(cohortService);
const logCategory = 'cohort';

// @route   GET /api/v1/cohorts/id
// @desc    Returns a cohort based on cohort ID provided
// @access  Public
router.get('/:id', logger.logRequestMiddleware({ logCategory, action: 'Viewed cohort data' }), (req, res) =>
	cohortController.getCohort(req, res)
);

// @route   GET /api/v1/cohorts
// @desc    Returns a collection of cohorts based on supplied query parameters
// @access  Public
router.get('/', logger.logRequestMiddleware({ logCategory, action: 'Viewed cohorts data' }), (req, res) =>
	cohortController.getCohorts(req, res)
);

router.post('/', (req, res) => cohortController.addCohort(req, res));

module.exports = router;
