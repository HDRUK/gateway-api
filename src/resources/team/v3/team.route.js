import express from 'express';
import passport from 'passport';
import { checkAccessToTeamMiddleware } from '../../../middlewares/checkAccessTeamMiddleware';
import constants from '../../utilities/constants.util';
const TeamController = require('./team.controller');

const router = express.Router();

// @route   GET api/v3/teams/:teamid/members
// @desc    GET all team members for team
// @access  Private
router.get(
    '/:teamid/members', 
    passport.authenticate('jwt'), 
    checkAccessToTeamMiddleware([]), 
    (req, res) => TeamController.getTeamMembers(req, res)
);

// @route   DELETE api/v3/teams/:teamid/members/:memberid
// @desc    DELETE team member from the team
// @access  Private
router.delete(
    '/:teamid/members/:memberid', 
    passport.authenticate('jwt'), 
    checkAccessToTeamMiddleware([
        constants.roleMemberTeam.CUST_TEAM_ADMIN
    ]), 
    (req, res) => TeamController.deleteTeamMember(req, res),
);

// @route   POST api/v3/teams/:teamid/members
// @desc    POST add new team member
// @access  Private
router.post(
    '/:teamid/members', 
    passport.authenticate('jwt'), 
    checkAccessToTeamMiddleware([
        constants.roleMemberTeam.CUST_TEAM_ADMIN, 
        constants.roleMemberTeam.CUST_DAR_MANAGER,
        constants.roleMemberTeam.CUST_MD_MANAGER
    ]), 
    (req, res) => TeamController.addTeamMember(req, res),
);

// @route   PATCH api/v3/teams/:teamid/members/:memberid
// @desc    PATCH add new team member
// @access  Private
router.patch(
    '/:teamid/members/:memberid', 
    passport.authenticate('jwt'), 
    checkAccessToTeamMiddleware([
        constants.roleMemberTeam.CUST_TEAM_ADMIN, 
        constants.roleMemberTeam.CUST_DAR_MANAGER,
        constants.roleMemberTeam.CUST_MD_MANAGER
    ]), 
    (req, res) => TeamController.updateTeamMember(req, res),
);

// @route   GET api/v3/teams/:teamid/notifications
// @desc    Get team notifications
// @access  Private
router.get(
    '/:teamid/notifications', 
    passport.authenticate('jwt'), 
    checkAccessToTeamMiddleware([
        constants.roleMemberTeam.CUST_TEAM_ADMIN
    ]), 
    (req, res) => TeamController.getTeamNotifications(req, res),
);

// @route   PUT api/v3/teams/:teamid/notifications
// @desc    Update notifications
// @access  Private
router.put(
    '/:teamid/notifications', 
    passport.authenticate('jwt'), 
    checkAccessToTeamMiddleware([
        constants.roleMemberTeam.CUST_TEAM_ADMIN
    ]),
    (req, res) => TeamController.updateNotifications(req, res),
);

// @route   PUT api/v3/teams/:teamid/notifications/messages
// @desc    Update notifications
// @access  Private
router.put(
    '/:teamid/notifications/messages', 
    passport.authenticate('jwt'), 
    checkAccessToTeamMiddleware([
        constants.roleMemberTeam.CUST_TEAM_ADMIN
    ]), 
    (req, res) => TeamController.updateNotificationMessages(req, res),
);

module.exports = router;