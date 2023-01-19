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
        const user = req.user;
        const currentUserId = req.user._id;

        const team = await this.getMembersByTeamId(teamId);

		let authorised = teamV3Util.checkTeamV3Permissions('', team.toObject(), currentUserId);
        if (!authorised) {
			authorised = teamV3Util.checkIfAdmin(user, [constants.roleTypes.ADMIN_DATASET]);
		}
        if (!authorised) {
			return res.status(401).json({ success: false });
		}

        let members = teamV3Util.formatTeamMembers(team);
console.log(members);
        res.status(200).json({
            members,
        });
    }

}

module.exports = new TeamController();