import HttpExceptions from '../exceptions/HttpExceptions';
import teamV3Util from '../resources/utilities/team.v3.util';

const checkAccessToTeamMiddleware = (arrayAllowedPermissions) => (req, res, next) => {
    const teamId = req.params.teamid || '';
    const currentUserId = req.user._id || '';

    if (!teamId || !currentUserId) {
        throw new HttpExceptions('One or more required parameters missing', 400);
    }

    req.allowPerms = arrayAllowedPermissions;

    next();
}

export { checkAccessToTeamMiddleware };