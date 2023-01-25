// import { getDBTeamMembers } from './team.database';

import TeamService from './team.service';
import teamV3Util from '../../utilities/team.v3.util';
import constants from '../../utilities/constants.util';
import HttpExceptions from '../../../exceptions/HttpExceptions';
import { UserModel } from '../../user/user.model';
import { TeamModel } from '../team.model';

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
                    throw new Error(err.message);
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
        const { memberid, roles = [] } = req.body;

        const team = await this.getTeamByTeamId(teamId);

        teamV3Util.checkUserAuthorization(currentUserId, 'manager', team, userObj);

        let { members } = team;

        let checkIfExistMember = members.find(item => item.memberid.toString() === memberid.toString());
        if (checkIfExistMember) {
            throw new HttpExceptions(`Member already exists`, 409);
        }

        let newMembers = {
            roles: roles,
            memberid: memberid,
            notifications: []
        };

        team.members = team.members.concat(newMembers);
		team.save(async err => {
			if (err) {
				throw new Error(err.message);
			} else {
				let newUsers = await UserModel.find({ _id: memberid });
				teamV3Util.createTeamNotifications(constants.notificationTypes.MEMBERADDED, { newUsers }, team, req.user);
				// 11. Get updated team users including bio data
				const updatedTeam = await this.getMembersByTeamId(teamId);
				let users = teamV3Util.formatTeamMembers(updatedTeam);
				// 12. Return successful response payload
				return res.status(201).json({
					success: true,
					members: users,
				});
			}
		});
    }
}

module.exports = new TeamController();
