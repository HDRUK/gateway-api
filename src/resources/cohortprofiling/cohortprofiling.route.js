import express from 'express';
import CohortProfilingController from './cohortprofiling.controller';
import { cohortProfilingService } from './dependency';
import { resultLimit } from '../../config/middleware';
import multer from 'multer';

const upload = multer();
const cohortProfilingController = new CohortProfilingController(cohortProfilingService);

const router = express.Router();

// @route   GET api/v1/cohortprofiling/:pid/:tableName:/:variable
// @desc    GET Cohort Profiling by pid, tableName and variable
// @access  Public
router.get('/:pid/:tableName/:variable', (req, res) => cohortProfilingController.getCohortProfilingByVariable(req, res));

// @route   GET api/v1/cohortprofiling
// @desc    Returns a collection of cohort profiling data based on supplied query parameters
// @access  Public
router.get('/', (req, res, next) => resultLimit(req, res, next, 100), (req, res) => cohortProfilingController.getCohortProfiling(req, res));

// @route   POST api/v1/cohortprofiling
// @desc    Consumes a JSON file containing cohort profiling data, transforms it and saves to MongoDB.
// @access  Private
router.post('/', upload.single('file'), (req, res) => cohortProfilingController.saveCohortProfiling(req, res));

module.exports = router;
