import express from 'express';
import ToolController from './tool.controller';
import { toolService } from './dependency';
import { resultLimit } from '../../../config/middleware';

const router = express.Router();
const toolController = new ToolController(toolService);

// @route   GET /api/v2/tools/id
// @desc    Returns a tool based on identifier provided
// @access  Public
router.get('/:id', (req, res) => toolController.getTool(req, res));

// @route   GET /api/v2/tools
// @desc    Returns a collection of tools based on supplied query parameters
// @access  Public
router.get('/', (req, res, next) => resultLimit(req, res, next, 100), (req, res) => toolController.getTools(req, res));

module.exports = router;
