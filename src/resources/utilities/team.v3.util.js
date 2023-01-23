import _, { isEmpty, has, isNull } from 'lodash';
import constants from './constants.util';
// import emailGenerator from '../../utilities/emailGenerator.util';
import emailGenerator from './emailGenerator.util';
import notificationBuilder from './notificationBuilder';

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
	// console.log(users);
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

const createTeamNotifications = async (type, context, team, user, publisherId) => {
	let teamName;
	if (type !== 'TeamAdded') {
		teamName = getTeamName(team);
	}
	let options = {};
	let html = '';

	switch (type) {
		case constants.notificationTypes.MEMBERREMOVED:
			// 1. Get user removed
			const { removedUser } = context;
			// 2. Create user notifications
			notificationBuilder.triggerNotificationMessage(
				[removedUser.id],
				`You have been removed from the team ${teamName}`,
				'team unlinked',
				teamName
			);
			// 3. Create email
			options = {
				teamName,
			};
			html = emailGenerator.generateRemovedFromTeam(options);
			await emailGenerator.sendEmail([removedUser], constants.hdrukEmail, `You have been removed from the team ${teamName}`, html, false);
			break;
		case constants.notificationTypes.MEMBERADDED:
			// 1. Get users added
			const { newUsers } = context;
			const newUserIds = newUsers.map(user => user.id);
			// 2. Create user notifications
			notificationBuilder.triggerNotificationMessage(
				newUserIds,
				`You have been added to the team ${teamName} on the HDR UK Innovation Gateway`,
				'team',
				teamName
			);
			// 3. Create email for reviewers
			options = {
				teamName,
				role: constants.roleTypes.REVIEWER,
			};
			html = emailGenerator.generateAddedToTeam(options);
			await emailGenerator.sendEmail(
				newUsers,
				constants.hdrukEmail,
				`You have been added as a reviewer to the team ${teamName} on the HDR UK Innovation Gateway`,
				html,
				false
			);
			// 4. Create email for managers
			options = {
				teamName,
				role: constants.roleTypes.MANAGER,
			};
			html = emailGenerator.generateAddedToTeam(options);
			await emailGenerator.sendEmail(
				newUsers,
				constants.hdrukEmail,
				`You have been added as a manager to the team ${teamName} on the HDR UK Innovation Gateway`,
				html,
				false
			);
			break;
		case constants.notificationTypes.TEAMADDED:
			const { recipients } = context;
			const recipientIds = recipients.map(recipient => recipient.id);
			//1. Create notifications
			notificationBuilder.triggerNotificationMessage(
				recipientIds,
				`You have been assigned as a team manger to the team ${team}`,
				'team added',
				team,
				publisherId
			);
			//2. Create email
			options = {
				team,
			};
			html = emailGenerator.generateNewTeamManagers(options);
			await emailGenerator.sendEmail(
				recipients,
				constants.hdrukEmail,
				`You have been assigned as a team manger to the team ${team}`,
				html,
				false
			);
			break;
		case constants.notificationTypes.MEMBERROLECHANGED:
			break;
	}
};

/**
 * Extract the name of a team from MongoDb object
 *
 * @param {object} team The team object containing its name or linked object containing name e.g. publisher
 */
const getTeamName = team => {
	if (has(team, 'publisher') && !isNull(team.publisher)) {
		let {
			publisher: { name },
		} = team;
		return name;
	} else {
		return 'No team name';
	}
};

export default {
    checkTeamV3Permissions,
    checkIfAdmin,
    formatTeamMembers,
    createTeamNotifications,
    getTeamName,
}