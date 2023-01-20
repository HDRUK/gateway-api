import express from 'express';
import passport from 'passport';

const TeamController = require('./team.controller');

const router = express.Router();

// @route   GET api/v3/teams/:teamid/members
// @desc    GET all team members for team
// @access  Private
router.get('/:teamid/members', passport.authenticate('jwt'), (req, res) => TeamController.getTeamMembers(req, res));

module.exports = router;