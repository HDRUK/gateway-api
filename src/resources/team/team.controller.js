import { isEmpty, has, difference, includes, isNull, filter, some } from 'lodash';
import { TeamModel } from './team.model';
import { UserModel } from '../user/user.model';
import { PublisherModel } from '../publisher/publisher.model';
import { Data } from '../tool/data.model';
import emailGenerator from '../utilities/emailGenerator.util';
import notificationBuilder from '../utilities/notificationBuilder';
import constants from '../utilities/constants.util';
import { filtersService } from '../filters/dependency';
import axios from 'axios';
const inputSanitizer = require('../utilities/inputSanitizer');
const { ObjectId } = require('mongodb');

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
			authorised = members.some(el => el.memberid.toString() === _id.toString());
		}
		// 3. If not return unauthorised
		if (!authorised) {
			return res.status(401).json({ success: false });
		}
		// 4. Return team
		return res.status(200).json({ success: true, team });
	} catch (err) {
		console.error(err.message);
		return res.status(500).json(err.message);
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
		// 3. If not check if the current user is an admin
		if (!authorised) {
			authorised = checkIfAdmin(req.user, [constants.roleTypes.ADMIN_DATASET]);
		}
		// 4. If not return unauthorised
		if (!authorised) {
			return res.status(401).json({ success: false });
		}
		// 5. Format response to include user info
		let users = formatTeamUsers(team);
		// 6. Return team members
		return res.status(200).json({ success: true, members: users });
	} catch (err) {
		console.error(err.message);
		return res.status(500).json(err.message);
	}
};

const formatTeamUsers = team => {
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
				_id,
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
		const team = await TeamModel.findOne({ _id: id }).populate([{ path: 'users' }, { path: 'publisher', select: 'name' }]);
		// 3. Return 404 if no team found matching Id
		if (!team) {
			return res.status(404).json({
				success: false,
			});
		}
		// 4. Ensure the user has permissions to perform this operation
		let authorised = checkTeamPermissions('manager', team.toObject(), req.user._id);
		// 5. If not check if the current user is an admin
		if (!authorised) {
			authorised = checkIfAdmin(req.user, [constants.roleTypes.ADMIN_DATASET]);
		}
		// 6. If not return unauthorised
		if (!authorised) {
			return res.status(401).json({ success: false });
		}
		// 7. Filter out any existing members to avoid duplication
		let teamObj = team.toObject();
		newMembers = [...newMembers].filter(newMem => !teamObj.members.some(mem => newMem.memberid.toString() === mem.memberid.toString()));

		// 8. Add members to MongoDb collection using model validation
		team.members = [...team.members, ...newMembers];
		// 9. Save members handling error callback if validation fails
		team.save(async err => {
			if (err) {
				console.error(err.message);
				return res.status(400).json({
					success: false,
					message: err.message,
				});
			} else {
				// 10. Issue notification to added members
				let newMemberIds = newMembers.map(mem => mem.memberid);
				let newUsers = await UserModel.find({ _id: newMemberIds });
				createNotifications(constants.notificationTypes.MEMBERADDED, { newUsers }, team, req.user);
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
 * GET api/v1/teams/:id/notifications
 *
 * @desc Get team notifications by :id
 */
const getTeamNotifications = async (req, res) => {
	try {
		const team = await TeamModel.findOne({ _id: req.params.id });
		if (!team) {
			return res.status(404).json({ success: false });
		}
		// 2. Check the current user is a member of the team
		const {
			user: { _id },
		} = req;

		let { members } = team;
		let authorised = false;
		// 3. check if member is inside the team of members
		if (members) {
			authorised = members.some(el => el.memberid.toString() === _id.toString());
		}
		// 4. If not return unauthorised
		if (!authorised) return res.status(401).json({ success: false });

		// 5. get member details
		let member = [...members].find(el => el.memberid.toString() === _id.toString());

		// 6. format teamNotifications for FE
		const teamNotifications = formatTeamNotifications(team);
		// 7. return optimal payload needed for FE containing memberNotifications and teamNotifications
		let notifications = {
			memberNotifications: member.notifications ? member.notifications : [],
			teamNotifications,
		};
		// 8. return 200 success
		return res.status(200).json(notifications);
	} catch (err) {
		console.error(err.message);
		return res.status(500).json({
			success: false,
			message: 'An error occurred retrieving team notifications',
		});
	}
};

/**
 * PUT api/v1/team/:id/notifications
 *
 * @desc Update Team notification preferences
 *
 */
const updateNotifications = async (req, res) => {
	try {
		// 1. Get the team from the database include user documents for each member
		const team = await TeamModel.findOne({ _id: req.params.id }).populate([{ path: 'users' }, { path: 'publisher', select: 'name' }]);

		if (!team) {
			return res.status(404).json({ success: false });
		}
		// 2. Check the current user is a member of the team
		const {
			user: { _id },
			body: data,
		} = req;

		let { members, users, notifications } = team;
		let authorised = false;

		if (members) {
			authorised = [...members].some(el => el.memberid.toString() === _id.toString());
		}
		// 3. If not return unauthorised
		if (!authorised) return res.status(401).json({ success: false });
		// 4. get member details
		let member = [...members].find(el => el.memberid.toString() === _id.toString());
		// 5. get member roles and notifications
		let { roles = [] } = member;

		// 6. get user role
		let isManager = roles.includes('manager');

		// 7. req data from FE
		let { memberNotifications = [], teamNotifications = [] } = data;

		// 8. commonality = can only turn off personal notification for each type if team has subscribed emails for desired type **As of M2 DAR**
		let missingOptIns = {};

		// 9. if member has notifications - backend check to ensure optIn is true if team notifications opted out for member
		if (!isEmpty(memberNotifications) && !isEmpty(teamNotifications)) {
			missingOptIns = findMissingOptIns(memberNotifications, teamNotifications);
		}

		// 10. return missingOptIns to FE and do not update
		if (!isEmpty(missingOptIns)) return res.status(400).json({ success: false, message: missingOptIns });

		// 11. if manager updates team notifications, check if we have any team notifications optedOut
		if (isManager) {
			// 1. filter team.notification types that are opted out ie { optIn: false, ... }
			const optedOutTeamNotifications = [...teamNotifications].filter(notification => !notification.optIn) || [];
			// 2. if there are opted out team notifications find members who have these notifications turned off and turn on if any
			if (!isEmpty(optedOutTeamNotifications)) {
				// loop over each notification type that has optOut
				optedOutTeamNotifications.forEach(teamNotification => {
					// get notification type
					let { notificationType } = teamNotification;
					// loop members
					members.forEach(member => {
						// get member notifications
						let { notifications = [] } = member;
						// if notifications exist
						if (!isEmpty(notifications)) {
							// find the notification by notificationType
							let notificationIndex = notifications.findIndex(n => n.notificationType.toUpperCase() === notificationType.toUpperCase());
							// if notificationType exists update optIn and notificationMessage
							if (!notifications[notificationIndex].optIn) {
								notifications[notificationIndex].optIn = true;
								notifications[notificationIndex].message = constants.teamNotificationMessages[notificationType.toUpperCase()];
							}
						}
						// update member notifications
						member.notifications = notifications;
					});
				});
			}

			// compare db / payload notifications for each type and send email ** when more types update email logic only to capture multiple types as it only outs one currently as per design ***
			// check if team has team.notificaitons loop over db notifications array - process emails missing / added for the type if manager has saved
			if (!isEmpty(notifications)) {
				// get manager who has submitted the request
				let manager = [...users].find(user => user._id.toString() === member.memberid.toString());

				[...notifications].forEach(dbNotification => {
					// extract notification type from team.notifications ie dataAccessRequest
					let { notificationType } = dbNotification;
					// find the notificationType in the teamNotifications incoming from FE
					const notificationPayload =
						[...teamNotifications].find(n => n.notificationType.toUpperCase() === notificationType.toUpperCase()) || {};
					// if found process next phase
					if (!isEmpty(notificationPayload)) {
						//  get db subscribedEmails and rename to dbSubscribedEmails
						let { subscribedEmails: dbSubscribedEmails, optIn: dbOptIn } = dbNotification;
						//  get incoming subscribedEmails and rename to payLoadSubscribedEmails
						let { subscribedEmails: payLoadSubscribedEmails, optIn: payLoadOptIn } = notificationPayload;
						// compare team.notifications by notificationType subscribed emails against the incoming payload to get emails that have been removed
						const removedEmails = difference([...dbSubscribedEmails], [...payLoadSubscribedEmails]) || [];
						// compare incoming payload notificationTypes subscribed emails to get emails that have been added against db
						const addedEmails = difference([...payLoadSubscribedEmails], [...dbSubscribedEmails]) || [];
						// get all members who have notifications by the type
						const subscribedMembersByType = filterMembersByNoticationTypes([...members], [notificationType]);
						if (!isEmpty(subscribedMembersByType)) {
							// build cleaner array of memberIds from subscribedMembersByType
							const memberIds = [...subscribedMembersByType].map(m => m.memberid.toString());
							// returns array of objects [{email: 'email@email.com '}] for members in subscribed emails users is list of full user object in team
							const { memberEmails, userIds } = getMemberDetails([...memberIds], [...users]);
							// email options and html template
							let html = '';
							let options = {
								managerName: `${manager.firstname} ${manager.lastname}`,
								notificationRemoved: false,
								disabled: false,
								header: '',
								emailAddresses: [],
							};
							// check if removed emails and send email subscribedEmails or if the emails are turned off
							if (!isEmpty(removedEmails) || (dbOptIn && !payLoadOptIn)) {
								// update the options
								options = {
									...options,
									notificationRemoved: true,
									disabled: !payLoadOptIn ? true : false,
									header: `A manager for ${team.publisher ? team.publisher.name : 'a team'} has ${
										dbOptIn && !payLoadOptIn ? 'disabled all' : 'removed a'
									} generic team email address(es)`,
									emailAddresses: dbOptIn && !payLoadOptIn ? payLoadSubscribedEmails : removedEmails,
									publisherId: team.publisher._id.toString(),
								};
								// get html template
								html = emailGenerator.generateTeamNotificationEmail(options);
								// send email
								emailGenerator.sendEmail(
									memberEmails,
									constants.hdrukEmail,
									`A manager for ${team.publisher ? team.publisher.name : 'a team'} has ${
										dbOptIn && !payLoadOptIn ? 'disabled all' : 'removed a'
									} generic team email address(es)`,
									html,
									true
								);

								notificationBuilder.triggerNotificationMessage(
									[...userIds],
									`A manager for ${team.publisher ? team.publisher.name : 'a team'} has ${
										dbOptIn && !payLoadOptIn ? 'disabled all' : 'removed a'
									} generic team email address(es)`,
									'team',
									team.publisher ? team.publisher.name : 'Undefined'
								);
							}
							// check if added emails and send email to subscribedEmails or if the dbOpt is false but the manager is turning back on team notifications
							if (!isEmpty(addedEmails) || (!dbOptIn && payLoadOptIn)) {
								// update options
								options = {
									...options,
									notificationRemoved: false,
									header: `A manager for ${team.publisher ? team.publisher.name : 'a team'} has ${
										!dbOptIn && payLoadOptIn ? 'enabled all' : 'added a'
									} generic team email address(es)`,
									emailAddresses: payLoadSubscribedEmails,
									publisherId: team.publisher._id.toString(),
								};
								// get html template
								html = emailGenerator.generateTeamNotificationEmail(options);
								// send email
								emailGenerator.sendEmail(
									memberEmails,
									constants.hdrukEmail,
									`A manager for ${team.publisher ? team.publisher.name : 'a team'} has ${
										!dbOptIn && payLoadOptIn ? 'enabled all' : 'added a'
									} generic team email address(es)`,
									html,
									true
								);

								notificationBuilder.triggerNotificationMessage(
									[...userIds],
									`A manager for ${team.publisher ? team.publisher.name : 'a team'} has ${
										!dbOptIn && payLoadOptIn ? 'enabled all' : 'added a'
									} generic team email address(es)`,
									'team',
									team.publisher ? team.publisher.name : 'Undefined'
								);
							}
						}
					}
				});
			}
			// update team notifications
			team.notifications = teamNotifications;
		}
		// 11. update member notifications
		member.notifications = memberNotifications;
		// 12. save changes to team
		await team.save();
		// 13. return 201 with new team
		return res.status(201).json(team);
	} catch (err) {
		console.error(err.message);
		return res.status(500).json({
			success: false,
			message: 'An error occurred updating team notifications',
		});
	}
};

/**
 * PUT api/v1/team/:id/notification-messages
 *
 * @desc Update Individal User messages against their own notifications
 *
 */
const updateNotificationMessages = async (req, res) => {
	try {
		const {
			user: { _id },
		} = req;
		await TeamModel.update(
			{ _id: req.params.id },
			{ $set: { 'members.$[m].notifications.$[].message': '' } },
			{ arrayFilters: [{ 'm.memberid': _id }], multi: true }
		)
			.then(resp => {
				return res.status(201).json();
			})
			.catch(err => {
				console.log(err);
				res.status(500).json({ success: false, message: err.message });
			});
	} catch (err) {
		console.error(err.message);
		return res.status(500).json({
			success: false,
			message: 'An error occurred updating notification messages',
		});
	}
};

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
		const team = await TeamModel.findOne({ _id: id }).populate([{ path: 'users' }, { path: 'publisher', select: 'name' }]);
		// 3. Return 404 if no team found matching Id
		if (!team) {
			return res.status(404).json({
				success: false,
			});
		}
		// 4. Ensure the user has permissions to perform this operation
		let authorised = checkTeamPermissions('manager', team.toObject(), req.user._id);
		// 5. If not check if the current user is an admin
		if (!authorised) {
			authorised = checkIfAdmin(req.user, [constants.roleTypes.ADMIN_DATASET]);
		}
		// 6. If not return unauthorised
		if (!authorised) {
			return res.status(401).json({ success: false });
		}
		// 7. Ensure at least one manager will remain if this member is deleted
		let { members = [], users = [] } = team;
		let managerCount = members.filter(mem => mem.roles.includes('manager') && mem.memberid.toString() !== req.user._id.toString()).length;
		if (managerCount === 0) {
			return res.status(400).json({
				success: false,
				message: 'You cannot delete the last manager in the team',
			});
		}
		// 8. Filter out removed member
		let updatedMembers = [...members].filter(mem => mem.memberid.toString() !== memberid.toString());
		if (members.length === updatedMembers.length) {
			return res.status(400).json({
				success: false,
				message: 'The user requested for deletion is not a member of this team',
			});
		}
		// 9. Update team model
		team.members = updatedMembers;
		team.save(function (err) {
			if (err) {
				console.error(err.message);
				return res.status(400).json({
					success: false,
					message: err.message,
				});
			} else {
				// 9. Issue notification to removed member
				let removedUser = users.find(user => user._id.toString() === memberid.toString());
				createNotifications(constants.notificationTypes.MEMBERREMOVED, { removedUser }, team, req.user);
				// 10. Return success response
				return res.status(204).json({
					success: true,
				});
			}
		});
	} catch (err) {
		console.error(err.message);
		res.status(500).json({ status: 'error', message: err.message });
	}
};

/**
 * GET api/v1/teams
 *
 * @desc Get the list of all publisher teams
 *
 */
const getTeamsList = async (req, res) => {
	try {
		// 1. Check the current user is a member of the HDR admin team
		const hdrAdminTeam = await TeamModel.findOne({ type: 'admin' }).lean();

		const hdrAdminTeamMember = hdrAdminTeam.members.filter(member => member.memberid.toString() === req.user._id.toString());

		// 2. If not return unauthorised
		if (isEmpty(hdrAdminTeamMember)) {
			return res.status(401).json({ success: false, message: 'Unauthorised' });
		}

		// 3. Get the publisher teams from the database
		const teams = await TeamModel.find(
			{ type: 'publisher', active: true },
			{
				_id: 1,
				updatedAt: 1,
				members: 1,
				membersCount: { $size: '$members' },
			}
		)
			.populate('publisher', { name: 1, 'publisherDetails.name': 1, 'publisherDetails.memberOf': 1 })
			.populate('users', { firstname: 1, lastname: 1 })
			.sort({ updatedAt: -1 })
			.lean();

		// 4. Return team
		return res.status(200).json({ success: true, teams });
	} catch (err) {
		console.error(err.message);
		return res.status(500).json(err.message);
	}
};

/**
 * Adds a publisher team
 *
 *
 */
const addTeam = async (req, res) => {
	let mdcFolderId;
	let teamManagerIds = [];
	let recipients = [];
	let folders = [];
	const { name, memberOf, contactPoint, teamManagers } = req.body;

	// 1. Check the current user is a member of the HDR admin team
	const hdrAdminTeam = await TeamModel.findOne({ type: 'admin' }).lean();

	const hdrAdminTeamMember = hdrAdminTeam.members.filter(member => member.memberid.toString() === req.user._id.toString());

	// 2. If not return unauthorised
	if (isEmpty(hdrAdminTeamMember)) {
		return res.status(401).json({ success: false, message: 'Unauthorised' });
	}

	try {
		// 3. log into MDC
		let metadataCatalogueLink = process.env.MDC_BASE_URL || '';
		const loginDetails = {
			username: process.env.MDC_USERNAME || '',
			password: process.env.MDC_PASSWORD || '',
		};

		await axios
			.post(metadataCatalogueLink + '/api/authentication/login', loginDetails, {
				withCredentials: true,
				timeout: 5000,
			})
			.then(async session => {
				axios.defaults.headers.Cookie = session.headers['set-cookie'][0]; // get cookie from request

				const folderLabel = {
					label: name,
				};

				// 4. Get all MDC folders
				await axios
					.get(metadataCatalogueLink + '/api/folders?all=true', {
						withCredentials: true,
						timeout: 60000,
					})
					.then(async res => {
						folders = res.data.items.filter(item => item.label === name);
					});

				// 5. Create new folder on MDC
				await axios
					.post(metadataCatalogueLink + '/api/folders', folderLabel, {
						withCredentials: true,
						timeout: 60000,
					})
					.then(async newFolder => {
						mdcFolderId = newFolder.data.id;

						// 6. Update the newly created folder to be public
						await axios
							.put(`${metadataCatalogueLink}/api/folders/${mdcFolderId}/readByEveryone`, {
								withCredentials: true,
								timeout: 60000,
							})
							.then(async res => {
								console.log(`public flag res: ${res}`);
							})
							.catch(err => {
								console.error('Error when making folder public on the MDC - ' + err.message);
							});
					})
					.catch(err => {
						console.error('Error when trying to create new folder on the MDC - ' + err.message);
					});
			})
			.catch(err => {
				console.error('Error when trying to login to MDC - ' + err.message);
			});

		// 7. Log out of MDC
		await axios.post(metadataCatalogueLink + `/api/authentication/logout`, { withCredentials: true, timeout: 5000 }).catch(err => {
			console.error('Error when trying to logout of the MDC - ' + err.message);
		});

		// 8. If a MDC folder with the name already exists return unsuccessful
		if (!isEmpty(folders)) {
			return res.status(422).json({ success: false, message: 'Duplicate MDC folder name' });
		}

		// 9. Create the publisher
		let publisher = new PublisherModel();

		publisher.name = `${inputSanitizer.removeNonBreakingSpaces(memberOf)} > ${inputSanitizer.removeNonBreakingSpaces(name)}`;
		publisher.publisherDetails = {
			name: inputSanitizer.removeNonBreakingSpaces(name),
			memberOf: inputSanitizer.removeNonBreakingSpaces(memberOf),
			contactPoint: inputSanitizer.removeNonBreakingSpaces(contactPoint),
		};
		publisher.mdcFolderId = mdcFolderId;

		let newPublisher = await publisher.save();
		if (!newPublisher) reject(new Error(`Can't persist publisher object to DB.`));

		let publisherId = newPublisher._id.toString();

		// 10. Create the team
		let team = new TeamModel();

		team._id = ObjectId(publisherId);
		team.type = 'publisher';

		for (let manager of teamManagers) {
			await getManagerInfo(manager.id, teamManagerIds, recipients);
		}

		team.members = teamManagerIds;

		let newTeam = await team.save();
		if (!newTeam) reject(new Error(`Can't persist team object to DB.`));

		// 11. Send email and notification to managers
		await createNotifications(constants.notificationTypes.TEAMADDED, { recipients }, name, req.user, publisherId);

		return res.status(200).json({ success: true });
	} catch (err) {
		console.error(err.message);
		return res.status(500).json({
			success: false,
			message: 'Error',
		});
	}
};

async function getManagerInfo(managerId, teamManagerIds, recipients) {
	let managerInfo = await UserModel.findOne(
		{ id: managerId },
		{
			_id: 1,
			id: 1,
			email: 1,
		}
	).exec();

	teamManagerIds.push({
		roles: ['manager'],
		memberid: ObjectId(managerInfo._id.toString()),
	});

	recipients.push({
		id: managerInfo.id,
		email: managerInfo.email,
	});

	return teamManagerIds;
}

/**
 * PUT api/v1/teams
 *
 * @desc Edit the team
 *
 */
const editTeam = async (req, res) => {
	try {
		// 1. Check the current user is a member of the HDR admin team
		const hdrAdminTeam = await TeamModel.findOne({ type: 'admin' }).lean();
		const hdrAdminTeamMember = hdrAdminTeam.members.filter(member => member.memberid.toString() === req.user._id.toString());

		// 2. If not return unauthorised
		if (isEmpty(hdrAdminTeamMember)) {
			return res.status(401).json({ success: false, message: 'Unauthorised' });
		}

		const id = req.params.id;
		const { name, memberOf, contactPoint } = req.body;
		const existingTeamDetails = await PublisherModel.findOne({ _id: ObjectId(id) }).lean();

		//3. Update Team
		await PublisherModel.findOneAndUpdate(
			{ _id: ObjectId(id) },
			{
				name: `${memberOf} > ${name}`,
				publisherDetails: {
					name,
					memberOf,
					contactPoint,
				},
			},
			err => {
				if (err) {
					return res.status(401).json({ success: false, error: err });
				}
			}
		);

		//4. Did name or member change
		if (existingTeamDetails.publisherDetails.memberOf !== memberOf || existingTeamDetails.publisherDetails.name !== name) {
			//5. Get list of active datasets for that publisher
			const listOfDatasets = await Data.find(
				{ 'datasetv2.summary.publisher.identifier': id, activeflag: 'active' },
				{ datasetid: 1 }
			).lean();

			// 6. log into MDC
			let metadataCatalogueLink = process.env.MDC_BASE_URL || '';
			const loginDetails = {
				username: process.env.MDC_USERNAME || '',
				password: process.env.MDC_PASSWORD || '',
			};

			await axios
				.post(metadataCatalogueLink + '/api/authentication/login', loginDetails, {
					withCredentials: true,
					timeout: 5000,
				})
				.then(async session => {
					axios.defaults.headers.Cookie = session.headers['set-cookie'][0]; // get cookie from request

					for (let dataset of listOfDatasets) {
						// 7. Get the metadata for the dataset
						await axios
							.get(metadataCatalogueLink + `/api/facets/${dataset.datasetid}/metadata?all=true`, {
								withCredentials: true,
								timeout: 60000,
							})
							.then(async res => {
								const foundDataset = res.data;
								// 8. Get the metadata for the dataset

								const memberOfId = foundDataset.items.find(metadata => metadata.key === 'properties/summary/publisher/memberOf');
								const nameId = foundDataset.items.find(metadata => metadata.key === 'properties/summary/publisher/name');
								const v1NameId = foundDataset.items.find(metadata => metadata.key === 'publisher');

								//9. Update memberOf on MDC
								if (!isEmpty(memberOfId)) {
									await axios
										.put(
											metadataCatalogueLink + `/api/facets/${dataset.datasetid}/metadata/${memberOfId.id}`,
											{ value: memberOf },
											{
												withCredentials: true,
												timeout: 60000,
											}
										)
										.catch(err => {
											console.error('Error when trying to update metdata on the MDC - ' + err.message);
										});
								}

								//10. Update name on MDC
								if (!isEmpty(nameId)) {
									await axios
										.put(
											metadataCatalogueLink + `/api/facets/${dataset.datasetid}/metadata/${nameId.id}`,
											{ value: name },
											{
												withCredentials: true,
												timeout: 60000,
											}
										)
										.catch(err => {
											console.error('Error when trying to update metdata on the MDC - ' + err.message);
										});
								}

								//11. Update v1 publisher name on MDC
								if (!isEmpty(v1NameId)) {
									await axios
										.put(
											metadataCatalogueLink + `/api/facets/${dataset.datasetid}/metadata/${v1NameId.id}`,
											{ value: `${memberOf} > ${name}` },
											{
												withCredentials: true,
												timeout: 60000,
											}
										)
										.catch(err => {
											console.error('Error when trying to update metdata on the MDC - ' + err.message);
										});
								}
							})
							.catch(err => {
								console.error('Error when trying to get the metdata from the MDC - ' + err.message);
							});
					}
				})
				.catch(err => {
					console.error('Error when trying to login to MDC - ' + err.message);
				});

			// 12. Log out of MDC
			await axios.post(metadataCatalogueLink + `/api/authentication/logout`, { withCredentials: true, timeout: 5000 }).catch(err => {
				console.error('Error when trying to logout of the MDC - ' + err.message);
			});

			//13. Update datasets if name or member change
			for (let dataset of listOfDatasets) {
				await Data.findOneAndUpdate(
					{ datasetid: dataset.datasetid },
					{
						'datasetfields.publisher': `${memberOf} > ${name}`,
						'datasetv2.summary.publisher.name': name,
						'datasetv2.summary.publisher.memberOf': memberOf,
					},
					err => {
						if (err) {
							return res.status(401).json({ success: false, error: err });
						}
					}
				);
			}

			//14. Update filters
			filtersService.optimiseFilters('dataset');
		}

		return res.status(200).json({ success: true });
	} catch (err) {
		console.error(err.message);
		return res.status(500).json(err.message);
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
	if (has(team, 'members')) {
		// 2. Extract team members
		let { members } = team;
		// 3. Find the current user
		let userMember = members.find(el => el.memberid.toString() === userId.toString());
		// 4. If the user was found check they hold the minimum required role
		if (userMember) {
			let { roles = [] } = userMember;
			if (roles.includes(role) || roles.includes(constants.roleTypes.MANAGER) || role === '') {
				return true;
			}
		}
	}
	return false;
};

const checkIfAdmin = (user, adminRoles) => {
	let { teams } = user.toObject();
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

const getTeamMembersByRole = (team, role) => {
	// Destructure members array and populated users array (populate 'users' must be included in the original Mongo query)
	let { members = [], users = [] } = team;
	// Get all userIds for role within team
	let userIds = members.filter(mem => mem.roles.includes(role) || role === 'All').map(mem => mem.memberid.toString());
	// return all user records for role
	return users.filter(user => userIds.includes(user._id.toString()));
};

/**
 * Extract the name of a team from MongoDb object
 *
 * @param {object} team The team object containing its name or linked object containing name e.g. publisher
 */
const getTeamName = team => {
	let teamObj = team.toObject();
	if (has(teamObj, 'publisher') && !isNull(teamObj.publisher)) {
		let {
			publisher: { name },
		} = teamObj;
		return name;
	} else {
		return 'No team name';
	}
};

/**
 * [Get teams notification by type ]
 *
 * @param   {Object}  team              [team object]
 * @param   {String}  notificationType  [notificationType dataAccessRequest]
 * @return  {Object}                    [return team notification object {notificaitonType, optIn, subscribedEmails }]
 */
const getTeamNotificationByType = (team = {}, notificationType = '') => {
	let teamObj = team.toObject();
	if (has(teamObj, 'notifications') && !isNull(teamObj.notifications) && !isEmpty(notificationType)) {
		let { notifications } = teamObj;
		let notification = [...notifications].find(n => n.notificationType.toUpperCase() === notificationType.toUpperCase());
		if (typeof notification !== 'undefined') return notification;
		else return {};
	} else {
		return {};
	}
};

const findTeamMemberById = (members = [], custodianManager = {}) => {
	if (!isEmpty(members) && !isEmpty(custodianManager))
		return [...members].find(member => member.memberid.toString() === custodianManager._id.toString()) || {};

	return {};
};

const findByNotificationType = (notificaitons = [], notificationType = '') => {
	if (!isEmpty(notificaitons) && !isEmpty(notificationType)) {
		return [...notificaitons].find(notification => notification.notificationType === notificationType) || {};
	}
	return {};
};

/**
 * filterMembersByNoticationTypes *nifty*
 *
 * @param   {Array}  members            [members]
 * @param   {Array}  notificationTypes  [notificationTypes]
 * @return  {Array}                     [return all members with notification types]
 */
const filterMembersByNoticationTypes = (members, notificationTypes) => {
	return filter(members, member => {
		return some(member.notifications, notification => {
			return includes(notificationTypes, notification.notificationType);
		});
	});
};

/**
 * filterMembersByNoticationTypesOptIn *nifty*
 *
 * @param   {Array}  members            [members]
 * @param   {Array}  notificationTypes  [notificationTypes]
 * @return  {Array}                     [return all members with notification types]
 */
const filterMembersByNoticationTypesOptIn = (members, notificationTypes) => {
	return filter(members, member => {
		return some(member.notifications, notification => {
			return includes(notificationTypes, notification.notificationType) && notification.optIn;
		});
	});
};

/**
 * getMemberDetails
 *
 * @param   {Array}  memberIds          [memberIds from team.members]
 * @param   {Array}  users  						[array of user objects that are in the team]
 * @return  {Array}                     [return all emails for memberIds from user aray]
 */
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

const buildOptedInEmailList = (custodianManagers = [], team = {}, notificationType = '') => {
	let { members = [] } = team;
	if (!isEmpty(custodianManagers)) {
		// loop over custodianManagers
		return [...custodianManagers].reduce((acc, custodianManager) => {
			let custodianNotificationObj, member, notifications, optIn;
			// if memebers exist only do the following
			if (!isEmpty(members)) {
				// find member in team.members array
				member = findTeamMemberById(members, custodianManager);
				if (!isEmpty(member)) {
					// deconstruct members
					({ notifications = [] } = member);
					// if the custodian has notifications
					if (!isEmpty(notifications)) {
						// find the notification type in the notifications array
						custodianNotificationObj = findByNotificationType(notifications, notificationType);
						if (!isEmpty(custodianNotificationObj)) {
							({ optIn } = custodianNotificationObj);
							if (optIn) return [...acc, { email: custodianManager.email }];
							else return acc;
						}
					} else {
						// if no notifications found optIn by default (safeguard)
						return [...acc, { email: custodianManager.email }];
					}
				}
			}
		}, []);
	} else {
		return [];
	}
};

/**
 * [Get subscribedEmails from optIn status ]
 *
 * @param   {Boolean}  optIn            	[optIn Status ]
 * @param   {Array}  	 subscribedEmails  	[the list of subscribed emails for notification type]
 * @return  {Array}                    		[formatted array of [{email: email}]]
 */
const getTeamNotificationEmails = (optIn = false, subscribedEmails) => {
	if (optIn && !isEmpty(subscribedEmails)) {
		return [...subscribedEmails].map(email => ({ email }));
	}

	return [];
};

const createNotifications = async (type, context, team, user, publisherId) => {
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
			emailGenerator.sendEmail([removedUser], constants.hdrukEmail, `You have been removed from the team ${teamName}`, html, false);
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
			emailGenerator.sendEmail(
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
			emailGenerator.sendEmail(
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
			emailGenerator.sendEmail(
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

const formatTeamNotifications = team => {
	let { notifications = [] } = team;
	if (!isEmpty(notifications)) {
		// 1. reduce for mapping over team notifications
		return [...notifications].reduce((arr, notification) => {
			let teamNotificationEmails = [];
			let { notificationType = '', optIn = false, subscribedEmails = [] } = notification;
			// 2. check subscribedEmails has length
			if (!isEmpty(subscribedEmails)) teamNotificationEmails = [...subscribedEmails].map(email => ({ value: email, error: '' }));
			else teamNotificationEmails = [{ value: '', error: '' }];

			// 3. return optimal payload for formated notification
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

export default {
	getTeamById: getTeamById,
	getTeamNotificationByType: getTeamNotificationByType,
	getTeamNotificationEmails: getTeamNotificationEmails,
	findTeamMemberById: findTeamMemberById,
	findByNotificationType: findByNotificationType,
	filterMembersByNoticationTypes: filterMembersByNoticationTypes,
	filterMembersByNoticationTypesOptIn: filterMembersByNoticationTypesOptIn,
	buildOptedInEmailList: buildOptedInEmailList,
	getTeamMembers: getTeamMembers,
	getMemberDetails: getMemberDetails,
	getTeamNotifications: getTeamNotifications,
	addTeamMembers: addTeamMembers,
	updateTeamMember: updateTeamMember,
	updateNotifications: updateNotifications,
	updateNotificationMessages: updateNotificationMessages,
	deleteTeamMember: deleteTeamMember,
	checkTeamPermissions: checkTeamPermissions,
	getTeamMembersByRole: getTeamMembersByRole,
	createNotifications: createNotifications,
	getTeamsList: getTeamsList,
	addTeam: addTeam,
	editTeam: editTeam,
};
