import HttpExceptions from '../exceptions/HttpExceptions';
import teamV3Util from '../resources/utilities/team.v3.util';

const checkAccessToTeamMiddleware = (arrayAllowedPermissions) => (req, res, next) => {
    const teamId = req.params.teamid || '';
    const currentUserId = req.user._id || '';
    const userTeams = req.user.teams || [];

    if (!teamId || !currentUserId) {
        throw new HttpExceptions('One or more required parameters missing', 400);
    }

    const currentUserRoles = teamV3Util.getAllRolesForApproverUser(userTeams, teamId, currentUserId);
    teamV3Util.checkingUserAuthorization(arrayAllowedPermissions, currentUserRoles);

    next();
}

export { checkAccessToTeamMiddleware };