import _, { isEmpty, has, includes, isNull, filter, some } from 'lodash';
import constants from './constants.util';
// import emailGenerator from '../../utilities/emailGenerator.util';
import emailGenerator from './emailGenerator.util';
import notificationBuilder from './notificationBuilder';
import HttpExceptions from '../../exceptions/HttpExceptions';
import { TeamModel } from '../team/team.model';

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
				id,
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

const checkUserAuthorization = (currUserId, permission, team, users) => {
	let authorised = checkTeamV3Permissions(permission, team, currUserId);
	if (!authorised) {
		authorised = checkIfAdmin(users, [constants.roleTypes.ADMIN_DATASET]);
	}
	if (!authorised) {
		throw new HttpExceptions(`Not enough permissions. User is not authorized to perform this action.`, 403);
	}

	return true;
};

const checkingUserAuthorization = (arrayRolesAllow, arrayCurrentUserRoles) => {
	let arrayRolesAllowLength = arrayRolesAllow.length;
	if (!arrayRolesAllowLength) {
		return true;
	}
	const allow = arrayCurrentUserRoles.filter(element => arrayRolesAllow.includes(element)).length;

	if (!allow) {
		throw new HttpExceptions(`Not enough permissions. User is not authorized to perform this action.`, 403);
	}

	return true;
};

const checkIfLastManager = (members, deleteUserId) => {
	let managerCount = members.filter(mem => mem.roles.includes('manager') && mem.memberid.toString() !== deleteUserId).length;
	if (managerCount === 0) {
		throw new HttpExceptions(`You cannot delete the last manager in the team.`);
	}
};

const checkIfExistAdminRole = (members, roles) => {
	let checkingMemberRoles;
	let checkingMembers = members.map(member => {
		checkingMemberRoles = member.roles.filter(role => roles.includes(role)).length;
		if (checkingMemberRoles) {
			return member.memberid;
		}
	});

	const filteredArray = _.compact(checkingMembers).length;

	if (!filteredArray) {
		throw new HttpExceptions(`The user requested for deletion is not a member of this team.`);
	}

	return true;
};

const getAllRolesForApproverUser = (team, teamId, userId) => {
	let arrayRoles = [];

	team.map(publisher => {
		if (publisher && publisher.type === constants.teamTypes.ADMIN) {
			publisher.members.map(member => {
				if (member.memberid.toString() === userId.toString()) {
					arrayRoles = [...arrayRoles, ...member.roles];
				}
			});
		}

		if (publisher && publisher.type === 'publisher' && publisher.publisher._id.toString() === teamId.toString()) {
			publisher.members.map(member => {
				if (member.memberid.toString() === userId.toString()) {
					arrayRoles = [...arrayRoles, ...member.roles];
				}
			});
		}
	});

	return [...new Set(arrayRoles)];
};

const listOfRolesAllowed = (userRoles, rolesAcceptedByRoles) => {
	let allowedRoles = [];

	userRoles.map(uRole => {
		if (rolesAcceptedByRoles[uRole]) {
			rolesAcceptedByRoles[uRole].forEach(element => allowedRoles.push(element));
		}
	});

	return [...new Set(allowedRoles)];
};

const checkAllowNewRoles = (userUpdateRoles, allowedRoles) => {
	userUpdateRoles.forEach(uRole => {
		if (!allowedRoles.includes(uRole)) {
			throw new HttpExceptions(`Adding the \'${uRole}\' role is not allowed`, 403);
		}
	});

	return true;
};

const checkUserRolesByTeam = (arrayCheckRoles, team, userId) => {
	if (has(team, 'members')) {
		let { members } = team;

		let userMember = members.find(el => el.memberid.toString() === userId.toString());

		if (userMember) {
			let { roles = [] } = userMember;

			if (arrayCheckRoles.length === 0) {
				return true;
			}

			const check = roles.filter(element => arrayCheckRoles.includes(element)).length;
			if (check) {
				return true;
			}

			if (roles.includes(constants.roleMemberTeam.CUST_DAR_MANAGER)) {
				return true;
			}
		}
	}

	return false;
};

const formatTeamNotifications = team => {
	let { notifications = [] } = team;
	if (!isEmpty(notifications)) {
		return [...notifications].reduce((arr, notification) => {
			let teamNotificationEmails = [];
			let { notificationType = '', optIn = false, subscribedEmails = [] } = notification;

			if (!isEmpty(subscribedEmails)) teamNotificationEmails = [...subscribedEmails].map(email => ({ value: email, error: '' }));
			else teamNotificationEmails = [{ value: '', error: '' }];

			let formattedNotification = {
				notificationType,
				optIn,
				subscribedEmails: teamNotificationEmails,
			};

			arr = [...arr, formattedNotification];

			return arr;
		}, []);
	} else {
		return [];
	}
};

const findMissingOptIns = (memberNotifications, teamNotifications) => {
	return [...memberNotifications].reduce((neededOptIns, memberNotification) => {
		let { notificationType: memberNotificationType, optIn: memberOptIn } = memberNotification;
		// find the matching notification type within the teams notification
		let teamNotification =
			[...teamNotifications].find(teamNotification => teamNotification.notificationType === memberNotificationType) || {};
		// if the team has the same notification type test
		if (!isEmpty(teamNotification)) {
			let { notificationType, optIn: teamOptIn, subscribedEmails } = teamNotification;
			// if both are turned off build and return new error
			if ((!teamOptIn && !memberOptIn) || (!memberOptIn && subscribedEmails.length <= 0)) {
				neededOptIns = {
					...neededOptIns,
					[`${notificationType}`]: `Notifications must be enabled for ${constants.teamNotificationTypesHuman[notificationType]}`,
				};
			}
		}
		return neededOptIns;
	}, {});
};

const filterMembersByNoticationTypes = (members, notificationTypes) => {
	return filter(members, member => {
		return some(member.notifications, notification => {
			return includes(notificationTypes, notification.notificationType);
		});
	});
};

const getMemberDetails = (memberIds = [], users = []) => {
	if (!isEmpty(memberIds) && !isEmpty(users)) {
		return [...users].reduce(
			(arr, user) => {
				let { email, id, _id } = user;
				if (memberIds.includes(_id.toString())) {
					arr['memberEmails'].push({ email });
					arr['userIds'].push({ id });
				}
				return {
					memberEmails: arr['memberEmails'],
					userIds: arr['userIds'],
				};
			},
			{ memberEmails: [], userIds: [] }
		);
	}
	return [];
};

export default {
	checkTeamV3Permissions,
	checkIfAdmin,
	formatTeamMembers,
	createTeamNotifications,
	getTeamName,
	checkUserAuthorization,
	checkingUserAuthorization,
	checkIfLastManager,
	checkIfExistAdminRole,
	getAllRolesForApproverUser,
	listOfRolesAllowed,
	checkAllowNewRoles,
	checkUserRolesByTeam,
	formatTeamNotifications,
	findMissingOptIns,
	filterMembersByNoticationTypes,
	getMemberDetails,
};
