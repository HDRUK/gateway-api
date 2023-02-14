// import { getDBTeamMembers } from './team.database';

import TeamService from './team.service';
import teamV3Util from '../../utilities/team.v3.util';
import constants from '../../utilities/constants.util';
import HttpExceptions from '../../../exceptions/HttpExceptions';
import { UserModel } from '../../user/user.model';
import { LoggingService } from '../../../services';

class TeamController extends TeamService {
    _logger;

    constructor() {
        super();

        this._logger = new LoggingService();
    }

    async getTeamMembers(req, res) {
        const teamId = req.params.teamid;
        const currentUserId = req.user._id;
        const allowPerms = req.allowPerms || [];

        await this.checkUserAuth(teamId, currentUserId, allowPerms);

        const team = await this.getMembersByTeamId(teamId);

        let members = teamV3Util.formatTeamMembers(team);

        this.sendLogInGoogle({
            action: 'getTeamMembers',
            input: {
                teamId,
                currentUserId,
            },
            output: members,
        });

        res.status(200).json({
            members,
        });    
    }

    async deleteTeamMember(req, res) {
        const teamId = req.params.teamid;
        const deleteUserId = req.params.memberid;
        const userObj = req.user;
        const currentUserId = req.user._id;
        const allowPerms = req.allowPerms || [];

        await this.checkUserAuth(teamId, currentUserId, allowPerms);

        const team = await this.getTeamByTeamId(teamId);

        let { members = [], users = [] } = team;

        let updatedMembers = [...members].filter(mem => mem.memberid.toString() !== deleteUserId.toString());
        if (members.length === updatedMembers.length) {
            throw new Error(`The user requested for deletion is not a member of this team.`);
        }

        teamV3Util.checkIfExistAdminRole(
            updatedMembers, 
            [
                constants.roleMemberTeam.CUST_TEAM_ADMIN, 
                constants.roleMemberTeam.CUST_DAR_MANAGER, 
                constants.roleMemberTeam.CUST_MD_MANAGER
            ]
        );
            
        this.sendLogInGoogle({
            action: 'deleteTeamMember',
            input: {
                teamId,
                memberid: deleteUserId,
                currentUserId,
            },
            output: 'success'
        });

        team.members = updatedMembers;
        try {
            team.save(function (err, result) {
                if (err) {
                    throw new HttpExceptions(err.message);
                } else {
                    let removedUser = users.find(user => user._id.toString() === deleteUserId.toString());
                    teamV3Util.createTeamNotifications(constants.notificationTypes.MEMBERREMOVED, { removedUser }, team, userObj);
        
                    return res.status(204).json({
                        success: true,
                    });        
                }
            });   

        } catch (e) {
            throw new HttpExceptions(e.message);
        }
    }

    async addTeamMember(req, res) {
        const teamId = req.params.teamid;
        const currentUserId = req.user._id;
        const { memberId, roles = [] } = req.body;
        const allowPerms = req.allowPerms || [];

        await this.checkUserAuth(teamId, currentUserId, allowPerms);

        const team = await this.getTeamByTeamId(teamId);

        let { members } = team;

        let checkIfExistMember = members.find(item => item.memberid.toString() === memberId.toString());
        if (checkIfExistMember) {
            throw new HttpExceptions(`Member already exists`, 409);
        }

        let newMembers = {
            roles: roles,
            memberid: memberId,
            notifications: []
        };

        this.sendLogInGoogle({
            action: 'addTeamMember',
            input: {
                teamId,
                currentUserId,
                body: req.body,
            },
            output: users,
        });

        team.members = team.members.concat(newMembers);
        team.save(async err => {
            if (err) {
                throw new HttpExceptions(err.message);
            } else {
                let newUsers = await UserModel.find({ _id: memberId });
                teamV3Util.createTeamNotifications(constants.notificationTypes.MEMBERADDED, { newUsers }, team, req.user);
                const updatedTeam = await this.getMembersByTeamId(teamId);
                let users = teamV3Util.formatTeamMembers(updatedTeam);

                return res.status(201).json({
                    success: true,
                    members: users,
                });
            }
        });
    }

    async updateTeamMember(req, res) {
        const teamId = req.params.teamid;
        const updateUserId = req.params.memberid;
        const currentUserId = req.user._id;
        const { roles = [] } = req.body;
        const allowPerms = req.allowPerms || [];

        const currUserRoles = await this.checkUserAuth(teamId, currentUserId, allowPerms);

        const team = await this.getTeamByTeamId(teamId);

        let { members } = team;

        let checkIfExistMember = members.find(item => item.memberid.toString() === updateUserId.toString());
        if (!checkIfExistMember) {
            throw new HttpExceptions(`The member does not exist in the team`, 409);
        }

        const approvedRoles = teamV3Util.listOfRolesAllowed(currUserRoles, constants.rolesAcceptedByRoles);
        teamV3Util.checkAllowNewRoles(roles, approvedRoles);

        team.members.map(member => {
            if (member.memberid.toString() === updateUserId.toString()) {
                member.roles = roles;
            }
        });

        try {
            team.save(async err => {
                if (err) {
                    throw new HttpExceptions(err.message);
                } else {
                    let updatedTeam = await this.getMembersByTeamId(teamId);
                    let users = teamV3Util.formatTeamMembers(updatedTeam);

                    this.sendLogInGoogle({
                        action: 'updateTeamMember',
                        input: {
                            teamId,
                            memberid: updateUserId,
                            currentUserId,
                            body: req.body,
                        },
                        output: users,
                    });

                    return res.json({
                        success: true,
                        members: users,
                    });
                }
            });    
        } catch (e) {
            throw new HttpExceptions(e.message);
        } 
    }

    async checkUserAuth(teamId, userId, allowPerms) {
        const currUserRolesFromTeamPublisher = await this.getPermsByUserIdFromTeamPublisher(teamId, userId);
        const currUserRolesFromTeamAdmin = await this.getPermsByUserIdFromTeamAdmin(userId);
        const currUserRolesExists = [...currUserRolesFromTeamPublisher, ...currUserRolesFromTeamAdmin];
        const currUserRolesUnique = [...new Set(currUserRolesExists)];
        teamV3Util.checkingUserAuthorization(allowPerms, currUserRolesUnique);

        return currUserRolesUnique;
    }

    sendLogInGoogle(message) {
        const loggingEnabled = parseInt(process.env.LOGGING_LOG_ENABLED) || 0;
        if (loggingEnabled) {
            this._logger.sendDataInLogging(JSON.stringify(message), 'INFO');
        }
    }
}

module.exports = new TeamController();
