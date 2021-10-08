import express from 'express';
import PaperController from '../paper.controller';
import { paperService } from '../dependency';
import { resultLimit } from '../../../config/middleware';

const router = express.Router();
const paperController = new PaperController(paperService);

// @route   GET /api/v2/papers/id
// @desc    Returns a paper based on identifier provided
// @access  Public
router.get('/:id', (req, res) => paperController.getPaper(req, res));

// @route   GET /api/v2/papers
// @desc    Returns a collection of papers based on supplied query parameters
// @access  Public
router.get('/', (req, res, next) => resultLimit(req, res, next, 100), (req, res) => paperController.getPapers(req, res));

module.exports = router;
