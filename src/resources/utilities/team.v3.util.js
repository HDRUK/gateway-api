import _, { isEmpty, has } from 'lodash';
import constants from './constants.util';

/**
 * Check a users permission levels for a team
 *
 * @param {enum} role The role required for the action
 * @param {object} team The team object containing its members
 * @param {objectId} userId The userId to check the permissions for
 */
const checkTeamV3Permissions = (role, team, userId) => {
	if (has(team, 'members')) {
		let { members } = team;
		let userMember = members.find(el => el.memberid.toString() === userId.toString());

        if (userMember) {
			let { roles = [] } = userMember;
			if (roles.includes(role) || roles.includes(constants.roleTypes.MANAGER) || role === '') {
				return true;
			}
		}
	}
	return false;
};

/**
 * Check if admin
 *
 * @param {object} user The user object
 * @param {array} adminRoles The adminRoles to check
 */
const checkIfAdmin = (user, adminRoles) => {
	let { teams } = user;
	if (teams) {
		teams = teams.map(team => {
			let { publisher, type, members } = team;
			let member = members.find(member => {
				return member.memberid.toString() === user._id.toString();
			});
			let { roles } = member;
			return { ...publisher, type, roles };
		});
	}
	const isAdmin = teams.filter(team => team.type === constants.teamTypes.ADMIN);
	if (!isEmpty(isAdmin)) {
		if (isAdmin[0].roles.some(role => adminRoles.includes(role))) {
			return true;
		}
	}

	return false;
};

/**
 * format output team members
 *
 * @param {object} team The team object
 */
const formatTeamMembers = team => {
	let { users = [] } = team;
	users = users.map(user => {
		if (user.id) {
			let {
				firstname,
				lastname,
				id,
				_id,
				email,
				additionalInfo: { organisation, bio, showOrganisation, showBio },
			} = user;
			let userMember = team.members.find(el => el.memberid.toString() === user._id.toString());
			let { roles = [] } = userMember;
			return {
				firstname,
				lastname,
				userId: _id.toString(),
				email,
				roles,
				organisation: showOrganisation ? organisation : '',
				bio: showBio ? bio : '',
			};
		}
	});
	return users.filter(user => {
		return user;
	});
};

export default {
    checkTeamV3Permissions,
    checkIfAdmin,
    formatTeamMembers,
}