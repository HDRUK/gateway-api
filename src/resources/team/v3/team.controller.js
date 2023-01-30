// import { getDBTeamMembers } from './team.database';

import TeamService from './team.service';
import teamV3Util from '../../utilities/team.v3.util';
import constants from '../../utilities/constants.util';
import HttpExceptions from '../../../exceptions/HttpExceptions';
import { UserModel } from '../../user/user.model';

class TeamController extends TeamService {
    constructor() {
        super();
    }

    async getTeamMembers(req, res) {
        const teamId = req.params.teamid;
        const users = req.user;
        const currentUserId = req.user._id;

        const team = await this.getMembersByTeamId(teamId);

		teamV3Util.checkUserAuthorization(currentUserId, '', team, users);

        let members = teamV3Util.formatTeamMembers(team);

        res.status(200).json({
            members,
        });
    }

    async deleteTeamMember(req, res) {
        const teamId = req.params.teamid;
        const deleteUserId = req.params.memberid;
        const userObj = req.user;
        const currentUserId = req.user._id;

        const team = await this.getTeamByTeamId(teamId);

        teamV3Util.checkUserAuthorization(currentUserId, 'manager', team, userObj);

        let { members = [], users = [] } = team;

        teamV3Util.checkIfLastManager(members, deleteUserId);

        let updatedMembers = [...members].filter(mem => mem.memberid.toString() !== deleteUserId.toString());
        if (members.length === updatedMembers.length) {
            throw new Error(`The user requested for deletion is not a member of this team.`);
        }

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
            throw new Error(e.message);
        }
    }

    async addTeamMember(req, res) {
        const teamId = req.params.teamid;
        const userObj = req.user;
        const currentUserId = req.user._id;
        const { memberId, roles = [] } = req.body;

        const team = await this.getTeamByTeamId(teamId);

        teamV3Util.checkUserAuthorization(currentUserId, 'manager', team, userObj);

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
        const userObj = req.user;
        const userTeams = userObj.teams || [];
        const currentUserId = req.user._id;
        const { roles = [] } = req.body;

        const team = await this.getTeamByTeamId(teamId);

        let { members } = team;

        let checkIfExistMember = members.find(item => item.memberid.toString() === updateUserId.toString());
        if (!checkIfExistMember) {
            throw new HttpExceptions(`The member does not exist in the team`, 409);
        }

        const approverUserRoles = teamV3Util.getAllRolesForApproverUser(userTeams, teamId, currentUserId);
        const approvedRoles = teamV3Util.listOfRolesAllowed(approverUserRoles, constants.rolesAcceptedByRoles);
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
}

module.exports = new TeamController();
