import express from 'express';
import ProjectController from '../project.controller';
import { projectService } from '../dependency';
import { resultLimit } from '../../../config/middleware';

const router = express.Router();
const projectController = new ProjectController(projectService);

// @route   GET /api/v2/projects/id
// @desc    Returns a project based on identifier provided
// @access  Public
router.get('/:id', (req, res) => projectController.getProject(req, res));

// @route   GET /api/v2/projects
// @desc    Returns a collection of projects based on supplied query parameters
// @access  Public
router.get('/', (req, res, next) => resultLimit(req, res, next, 100), (req, res) => projectController.getProjects(req, res));

module.exports = router;
