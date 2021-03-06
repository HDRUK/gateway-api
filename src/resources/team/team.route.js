import express from 'express';
import passport from 'passport';

import teamController from './team.controller';

const router = express.Router();

// @route   GET api/teams/:id
// @desc    GET A team by :id
// @access  Public
router.get('/:id', passport.authenticate('jwt'), teamController.getTeamById);

// @route   GET api/teams/:id/members
// @desc    GET all team members for team
// @access  Private
router.get('/:id/members', passport.authenticate('jwt'), teamController.getTeamMembers);

// @route   POST api/teams/:id/members
// @desc    Add team members
// @access  Private
router.post('/:id/members', passport.authenticate('jwt'), teamController.addTeamMembers);

// @route   PUT api/teams/:id/members
// @desc    Edit a team member
// @access  Private
router.put('/:id/members/:memberid', passport.authenticate('jwt'), teamController.updateTeamMember);

// @route   DELETE api/teams/:id/members
// @desc    Delete a team member
// @access  Private
router.delete('/:id/members/:memberid', passport.authenticate('jwt'), teamController.deleteTeamMember);

module.exports = router;
