// import { getDBTeamMembers } from './team.database';

import TeamService from './team.service';
import teamV3Util from '../../utilities/team.v3.util';
import constants from '../../utilities/constants.util';

class TeamController extends TeamService {
    constructor() {
        super();
    }

    async getTeamMembers(req, res) {
        const teamId = req.params.teamid;
        const users = req.user;
        const currentUserId = req.user._id;

        const team = await this.getMembersByTeamId(teamId);

		this.checkUserAuthorization(currentUserId, '', team, users);

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

        this.checkUserAuthorization(currentUserId, 'manager', team, userObj);

        let { members = [], users = [] } = team;

        this.checkIfLastManager(members, deleteUserId);

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

    checkUserAuthorization(currUserId, permission, team, users) {
        let authorised = teamV3Util.checkTeamV3Permissions(permission, team.toObject(), currUserId);
        if (!authorised) {
			authorised = teamV3Util.checkIfAdmin(users, [constants.roleTypes.ADMIN_DATASET]);
		}
        if (!authorised) {
            throw new Error(`Not enough permissions. User is not authorized to perform this action.`);
		}
    }

    checkIfLastManager(members, deleteUserId) {
		let managerCount = members.filter(mem => mem.roles.includes('manager') && mem.memberid.toString() !== deleteUserId).length;
		if (managerCount === 0) {
            throw new Error(`You cannot delete the last manager in the team.`);
		}
    }

}

module.exports = new TeamController();