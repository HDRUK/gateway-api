import express from 'express';
import passport from 'passport';

const TeamController = require('./team.controller');

const router = express.Router();

// @route   GET api/v3/teams/:teamid/members
// @desc    GET all team members for team
// @access  Private
router.get('/:teamid/members', passport.authenticate('jwt'), (req, res) => TeamController.getTeamMembers(req, res));

// @route   DELETE api/v3/teams/:teamid/members/memberid
// @desc    DELETE team member from the team
// @access  Private
router.delete('/:teamid/members/:memberid', passport.authenticate('jwt'), (req, res) => TeamController.deleteTeamMember(req, res));

// test
// memberid: 628f9e65b089fa694655d168

module.exports = router;