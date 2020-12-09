import _ from 'lodash';
import { TeamModel } from './team.model';
import { UserModel } from '../user/user.model';
import emailGenerator from '../utilities/emailGenerator.util';

const notificationBuilder = require('../utilities/notificationBuilder');

const hdrukEmail = `enquiry@healthdatagateway.org`;
const notificationTypes = {
	MEMBERADDED: 'MemberAdded',
	MEMBERREMOVED: 'MemberRemoved',
	MEMBERROLECHANGED: 'MemberRoleChanged',
};
const roleTypes = {
	MANAGER: 'manager',
	REVIEWER: 'reviewer',
};

// GET api/v1/teams/:id
const getTeamById = async (req, res) => {
	try {
		// 1. Get the team from the database
		const team = await TeamModel.findOne({ _id: req.params.id });
		if (!team) {
			return res.status(404).json({ success: false });
		}
		// 2. Check the current user is a member of the team
		let { _id } = req.user;
		let { members } = team;
		let authorised = false;
		if (members) {
			authorised = members.some(
				(el) => el.memberid.toString() === _id.toString()
			);
		}
		// 3. If not return unauthorised
		if (!authorised) {
			return res.status(401).json({ success: false });
		}
		// 4. Return team
		return res.status(200).json({ success: true, team });
	} catch (err) {
		console.error(err.message);
		return res.status(500).json(err);
	}
};

// GET api/v1/teams/:id/members
const getTeamMembers = async (req, res) => {
	try {
		// 1. Get the team from the database
		const team = await TeamModel.findOne({ _id: req.params.id }).populate({
			path: 'users',
			populate: {
				path: 'additionalInfo',
				select: 'organisation bio showOrganisation showBio',
			},
		});
		if (!team) {
			return res.status(404).json({ success: false });
		}
		// 2. Check the current user is a member of the team
		let authorised = checkTeamPermissions('', team.toObject(), req.user._id);
		// 3. If not return unauthorised
		if (!authorised) {
			return res.status(401).json({ success: false });
		}
		// 4. Format response to include user info
		let users = formatTeamUsers(team);
		// 5. Return team members
		return res.status(200).json({ success: true, members: users });
	} catch (err) {
		console.error(err.message);
		return res.status(500).json(err);
	}
};

const formatTeamUsers = (team) => {
	let { users = [] } = team;
		users = users.map((user) => {
			let {
				firstname,
				lastname,
				id,
				_id,
				email,
				additionalInfo: { organisation, bio, showOrganisation, showBio },
			} = user;
			let userMember = team.members.find(
				(el) => el.memberid.toString() === user._id.toString()
			);
			let { roles = [] } = userMember;
			return {
				firstname,
				lastname,
				id,
				_id,
				email,
				roles,
				organisation: showOrganisation ? organisation : '',
				bio: showBio ? bio : '',
			};
		});
	return users;
};

/**
 * Adds a single or multiple team members to a team
 *
 * @param {array} members Array containing single or multiple team member objects
 */
const addTeamMembers = async (req, res) => {
	try {
		// 1. Deconstruct route values from request
		let { id } = req.params;
		let { members: newMembers = [] } = req.body;
		if (!id) {
			return res.status(400).json({
				success: false,
				message: 'You must supply a valid team identifier',
			});
		}
		// 2. Find team by Id passed
		const team = await TeamModel.findOne({ _id: id }).populate([
			{ path: 'users' },
			{ path: 'publisher', select: 'name' },
		]);
		// 3. Return 404 if no team found matching Id
		if (!team) {
			return res.status(404).json({
				success: false,
			});
		}
		// 4. Ensure the user has permissions to perform this operation
		let authorised = checkTeamPermissions(
			'manager',
			team.toObject(),
			req.user._id
		);
		// 5. If not return unauthorised
		if (!authorised) {
			return res.status(401).json({ success: false });
		}
		// 6. Filter out any existing members to avoid duplication
		let teamObj = team.toObject();
		newMembers = [...newMembers].filter(
			(newMem) =>
				!teamObj.members.some(
					(mem) => newMem.memberid.toString() === mem.memberid.toString()
				)
		);
		
		// 8. Add members to MongoDb collection using model validation
		team.members = [...team.members, ...newMembers];
		// 9. Save members handling error callback if validation fails
		team.save(async (err) => {
			if (err) {
				console.error(err);
				return res.status(400).json({
					success: false,
					message: err.message,
				});
			} else {
				// 10. Issue notification to added members
				let newMemberIds = newMembers.map((mem) => mem.memberid);
				let newUsers = await UserModel.find({ _id: newMemberIds });
				createNotifications(
					notificationTypes.MEMBERADDED,
					{ newUsers },
					team,
					req.user
				);
				// 11. Get updated team users including bio data
				const updatedTeam = await TeamModel.findOne({ _id: req.params.id }).populate({
					path: 'users',
					populate: {
						path: 'additionalInfo',
						select: 'organisation bio',
					},
				});
				let users = formatTeamUsers(updatedTeam);
				// 12. Return successful response payload
				return res.status(201).json({
					success: true,
					members: users,
				});
			}
		});
	} catch (err) {
		console.error(err.message);
		return res.status(400).json({
			success: false,
			message: 'You must supply a valid team identifier',
		});
	}
};

/**
 * Updates a single team members within a team
 *
 * @param {string} role New role to assign to the team member
 */
const updateTeamMember = async (req, res) => {};

/**
 * Deletes a team member from a team
 *
 * @param {objectId} memberid MongoDb userId for the member to be removed
 */
const deleteTeamMember = async (req, res) => {
	try {
		// 1. Deconstruct route values from request
		let { id, memberid } = req.params;
		if (!memberid || !id) {
			return res.status(400).json({
				success: false,
				message: 'You must supply a valid team and member identifier',
			});
		}
		// 2. Find team by Id passed
		const team = await TeamModel.findOne({ _id: id }).populate([
			{ path: 'users' },
			{ path: 'publisher', select: 'name' },
		]);
		// 3. Return 404 if no team found matching Id
		if (!team) {
			return res.status(404).json({
				success: false,
			});
		}
		// 4. Ensure the user has permissions to perform this operation
		let authorised = checkTeamPermissions(
			'manager',
			team.toObject(),
			req.user._id
		);
		// 5. If not return unauthorised
		if (!authorised) {
			return res.status(401).json({ success: false });
		}
		// 6. Ensure at least one manager will remain if this member is deleted
		let { members = [], users = [] } = team;
		let managerCount = members.filter(
			(mem) =>
				mem.roles.includes('manager') &&
				mem.memberid.toString() !== req.user._id.toString()
		).length;
		if (managerCount === 0) {
			return res.status(400).json({
				success: false,
				message: 'You cannot delete the last manager in the team',
			});
		}
		// 7. Filter out removed member
		let updatedMembers = [...members].filter(
			(mem) => mem.memberid.toString() !== memberid.toString()
		);
		if (members.length === updatedMembers.length) {
			return res.status(400).json({
				success: false,
				message: 'The user requested for deletion is not a member of this team',
			});
		}
		// 8. Update team model
		team.members = updatedMembers;
		team.save(function (err) {
			if (err) {
				console.error(err);
				return res.status(400).json({
					success: false,
					message: err.message,
				});
			} else {
				// 9. Issue notification to removed member
				let removedUser = users.find(
					(user) => user._id.toString() === memberid.toString()
				);
				createNotifications(
					notificationTypes.MEMBERREMOVED,
					{ removedUser },
					team,
					req.user
				);
				// 10. Return success response
				return res.status(204).json({
					success: true,
				});
			}
		});
	} catch (err) {
		console.error(err.message);
		return res.status(500).json({
			success: false,
			message: 'An error occurred deleting the team member',
		});
	}
};

/**
 * Check a users permission levels for a team
 *
 * @param {enum} role The role required for the action
 * @param {object} team The team object containing its members
 * @param {objectId} userId The userId to check the permissions for
 */
const checkTeamPermissions = (role, team, userId) => {
	// 1. Ensure the team has associated members defined
	if (_.has(team, 'members')) {
		// 2. Extract team members
		let { members } = team;
		// 3. Find the current user
		let userMember = members.find(
			(el) => el.memberid.toString() === userId.toString()
		);
		// 4. If the user was found check they hold the minimum required role
		if (userMember) {
			let { roles = [] } = userMember;
			if (
				roles.includes(role) ||
				roles.includes(roleTypes.MANAGER) ||
				role === ''
			) {
				return true;
			}
		}
	}
	return false;
};

const getTeamMembersByRole = (team, role) => {
	// Destructure members array and populated users array (populate 'users' must be included in the original Mongo query)
	let { members = [], users = [] } = team;
	// Get all userIds for role within team
	let userIds = members
		.filter((mem) => mem.roles.includes(role))
		.map((mem) => mem.memberid.toString());
	// return all user records for role
	return users.filter((user) => userIds.includes(user._id.toString()));
};

/**
 * Extract the name of a team from MongoDb object
 *
 * @param {object} team The team object containing its name or linked object containing name e.g. publisher
 */
const getTeamName = (team) => {
	let teamObj = team.toObject();
	if (_.has(teamObj, 'publisher') && !_.isNull(teamObj.publisher)) {
		let {
			publisher: { name },
		} = teamObj;
		return name;
	} else {
		return 'No team name';
	}
};

const createNotifications = async (type, context, team, user) => {
	const teamName = getTeamName(team);
	let options = {};
	let html = '';

	switch (type) {
		case notificationTypes.MEMBERREMOVED:
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
			emailGenerator.sendEmail(
				[removedUser],
				hdrukEmail,
				`You have been removed from the team ${teamName}`,
				html,
				false
			);
			break;
		case notificationTypes.MEMBERADDED:
			// 1. Get users added
			const { newUsers } = context;
			const newUserIds = newUsers.map((user) => user.id);
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
				role: roleTypes.REVIEWER,
			};
			html = emailGenerator.generateAddedToTeam(options);
			emailGenerator.sendEmail(
				newUsers,
				hdrukEmail,
				`You have been added as a reviewer to the team ${teamName} on the HDR UK Innovation Gateway`,
				html,
				false
			);
			// 4. Create email for managers
			options = {
				teamName,
				role: roleTypes.MANAGER,
			};
			html = emailGenerator.generateAddedToTeam(options);
			emailGenerator.sendEmail(
				newUsers,
				hdrukEmail,
				`You have been added as a manager to the team ${teamName} on the HDR UK Innovation Gateway`,
				html,
				false
			);
			break;
		case notificationTypes.MEMBERROLECHANGED:
			break;
	}
};

export default {
	getTeamById: getTeamById,
	getTeamMembers: getTeamMembers,
	addTeamMembers: addTeamMembers,
	updateTeamMember: updateTeamMember,
	deleteTeamMember: deleteTeamMember,
	checkTeamPermissions: checkTeamPermissions,
	getTeamMembersByRole: getTeamMembersByRole,
	createNotifications: createNotifications,
	roleTypes: roleTypes,
};
