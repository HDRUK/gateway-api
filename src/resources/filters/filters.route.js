import express from 'express';
import FiltersController from './filters.controller';
import { filtersService } from './dependency';

const router = express.Router();
const filtersController = new FiltersController(filtersService);

// @route   GET /api/v2/filters/id
// @desc    Returns a filter selection based on filter ID provided
// @access  Public
router.get('/:id', (req, res) => filtersController.getFilters(req, res));

module.exports = router;