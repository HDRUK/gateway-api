// import { getDBTeamMembers } from './team.database';

import _, { isEmpty, has, difference, includes, isNull } from 'lodash';
import TeamService from './team.service';
import teamV3Util from '../../utilities/team.v3.util';
import constants from '../../utilities/constants.util';
import HttpExceptions from '../../../exceptions/HttpExceptions';
import { UserModel } from '../../user/user.model';
import { TeamModel } from '../team.model';
import { LoggingService } from '../../../services';
import emailGenerator from '../../utilities/emailGenerator.util';
import notificationBuilder from '../../utilities/notificationBuilder';

class TeamController extends TeamService {
    _logger;

    constructor() {
        super();

        this._logger = new LoggingService();
    }

    async getTeamMembers(req, res) {
        const teamId = req.params.teamid;
        const currentUserId = req.user._id;
        const allowPerms = req.allowPerms || [];

        await this.checkUserAuth(teamId, currentUserId, allowPerms);

        const team = await this.getMembersByTeamId(teamId);

        let members = teamV3Util.formatTeamMembers(team);

        this.sendLogInGoogle({
            action: 'getTeamMembers',
            input: {
                teamId,
                currentUserId,
            },
            output: members,
        });

        res.status(200).json({
            members,
        });    
    }

    async deleteTeamMember(req, res) {
        const teamId = req.params.teamid;
        const deleteUserId = req.params.memberid;
        const userObj = req.user;
        const currentUserId = req.user._id;
        const allowPerms = req.allowPerms || [];

        await this.checkUserAuth(teamId, currentUserId, allowPerms);

        const team = await this.getTeamByTeamId(teamId);

        let { members = [], users = [] } = team;

        let updatedMembers = [...members].filter(mem => mem.memberid.toString() !== deleteUserId.toString());
        if (members.length === updatedMembers.length) {
            throw new Error(`The user requested for deletion is not a member of this team.`);
        }

        teamV3Util.checkIfExistAdminRole(
            updatedMembers, 
            [
                constants.roleMemberTeam.CUST_TEAM_ADMIN, 
                constants.roleMemberTeam.CUST_DAR_MANAGER, 
                constants.roleMemberTeam.CUST_MD_MANAGER
            ]
        );
            
        this.sendLogInGoogle({
            action: 'deleteTeamMember',
            input: {
                teamId,
                memberid: deleteUserId,
                currentUserId,
            },
            output: 'success'
        });

        team.members = updatedMembers;
        try {
            team.save(function (err, result) {
                if (err) {
                    throw new HttpExceptions(err.message);
                } else {
                    let removedUser = users.find(user => user._id.toString() === deleteUserId.toString());
                    teamV3Util.createTeamNotifications(constants.notificationTypes.MEMBERREMOVED, { removedUser }, team, userObj);
        
                    return res.status(204).json({
                        success: true,
                    });        
                }
            });   

        } catch (e) {
            throw new HttpExceptions(e.message);
        }
    }

    async addTeamMember(req, res) {
        const teamId = req.params.teamid;
        const currentUserId = req.user._id;
        const { memberId, roles = [] } = req.body;
        const allowPerms = req.allowPerms || [];

        await this.checkUserAuth(teamId, currentUserId, allowPerms);

        const team = await this.getTeamByTeamId(teamId);

        let { members } = team;

        let checkIfExistMember = members.find(item => item.memberid.toString() === memberId.toString());
        if (checkIfExistMember) {
            throw new HttpExceptions(`Member already exists`, 409);
        }

        let newMembers = {
            roles: roles,
            memberid: memberId,
            notifications: []
        };

        this.sendLogInGoogle({
            action: 'addTeamMember',
            input: {
                teamId,
                currentUserId,
                body: req.body,
            },
            output: users,
        });

        team.members = team.members.concat(newMembers);
        team.save(async err => {
            if (err) {
                throw new HttpExceptions(err.message);
            } else {
                let newUsers = await UserModel.find({ _id: memberId });
                teamV3Util.createTeamNotifications(constants.notificationTypes.MEMBERADDED, { newUsers }, team, req.user);
                const updatedTeam = await this.getMembersByTeamId(teamId);
                let users = teamV3Util.formatTeamMembers(updatedTeam);

                return res.status(201).json({
                    success: true,
                    members: users,
                });
            }
        });
    }

    async updateTeamMember(req, res) {
        const teamId = req.params.teamid;
        const updateUserId = req.params.memberid;
        const currentUserId = req.user._id;
        const { roles = [] } = req.body;
        const allowPerms = req.allowPerms || [];

        const currUserRoles = await this.checkUserAuth(teamId, currentUserId, allowPerms);

        const team = await this.getTeamByTeamId(teamId);

        let { members } = team;

        let checkIfExistMember = members.find(item => item.memberid.toString() === updateUserId.toString());
        if (!checkIfExistMember) {
            throw new HttpExceptions(`The member does not exist in the team`, 409);
        }

        const approvedRoles = teamV3Util.listOfRolesAllowed(currUserRoles, constants.rolesAcceptedByRoles);
        teamV3Util.checkAllowNewRoles(roles, approvedRoles);

        team.members.map(member => {
            if (member.memberid.toString() === updateUserId.toString()) {
                member.roles = roles;
            }
        });

        try {
            team.save(async err => {
                if (err) {
                    throw new HttpExceptions(err.message);
                } else {
                    let updatedTeam = await this.getMembersByTeamId(teamId);
                    let users = teamV3Util.formatTeamMembers(updatedTeam);

                    this.sendLogInGoogle({
                        action: 'updateTeamMember',
                        input: {
                            teamId,
                            memberid: updateUserId,
                            currentUserId,
                            body: req.body,
                        },
                        output: users,
                    });

                    return res.json({
                        success: true,
                        members: users,
                    });
                }
            });    
        } catch (e) {
            throw new HttpExceptions(e.message);
        } 
    }

    async getTeamNotifications(req, res) {
        const teamId = req.params.teamid;
        const currentUserId = req.user._id;
        const allowPerms = req.allowPerms || [];

        try {
            await this.checkUserAuth(teamId, currentUserId, allowPerms);

            const team = await this.getTeamByTeamIdSimple(teamId);
            if (!team) {
                throw new HttpExceptions(e.message, 404);
            }

            const {
                user: { _id },
            } = req;

            let { members } = team;
            let authorised = false;

            if (members) {
                authorised = members.some(el => el.memberid.toString() === _id.toString());
            }

            if (!authorised) {
                throw new HttpExceptions(`You must provide valid authentication credentials to access this resource.`, 401);
            }

            let member = [...members].find(el => el.memberid.toString() === _id.toString());

            const teamNotifications = teamV3Util.formatTeamNotifications(team);

            let notifications = {
                memberNotifications: member.notifications ? member.notifications : [],
                teamNotifications,
            };

            return res.status(200).json(notifications);
        } catch (err) {
            process.stdout.write(err.message);
            throw new HttpExceptions(`An error occurred retrieving team notifications : ${err.message}`, 500);
        }
    }

    async updateNotifications(req, res) {
        const teamId = req.params.teamid;
        const currentUserId = req.user._id;
        const allowPerms = req.allowPerms || [];
        try {
            await this.checkUserAuth(teamId, currentUserId, allowPerms);

            const team = await this.getTeamByTeamId(teamId);

            const {
                user: { _id },
                body: data,
            } = req;
    
            let { members, users, notifications } = team;
            let authorised = false;
    
            if (members) {
                authorised = [...members].some(el => el.memberid.toString() === _id.toString());
            }

            if (!authorised) return res.status(401).json({ success: false });

            let member = [...members].find(el => el.memberid.toString() === _id.toString());
    
            let isManager = true;
    
            let { memberNotifications = [], teamNotifications = [] } = data;
    
            let missingOptIns = {};
    
            if (!isEmpty(memberNotifications) && !isEmpty(teamNotifications)) {
                missingOptIns = teamV3Util.findMissingOptIns(memberNotifications, teamNotifications);
            }
    
            if (!isEmpty(missingOptIns)) return res.status(400).json({ success: false, message: missingOptIns });
    
            if (isManager) {
                const optedOutTeamNotifications = Object.values([...teamNotifications]).filter(notification => !notification.optIn) || [];
                if (!isEmpty(optedOutTeamNotifications)) {
                    optedOutTeamNotifications.forEach(teamNotification => {
                        let { notificationType } = teamNotification;
                        members.forEach(member => {
                            let { notifications = [] } = member;
                            if (!isEmpty(notifications)) {
                                let notificationIndex = notifications.findIndex(n => n.notificationType.toUpperCase() === notificationType.toUpperCase());
                                if (!notifications[notificationIndex].optIn) {
                                    notifications[notificationIndex].optIn = true;
                                    notifications[notificationIndex].message = constants.teamNotificationMessages[notificationType.toUpperCase()];
                                }
                            }
                            member.notifications = notifications;
                        });
                    });
                }
    
                if (!isEmpty(notifications)) {
                    let manager = [...users].find(user => user._id.toString() === member.memberid.toString());
    
                    [...notifications].forEach(dbNotification => {
                        let { notificationType } = dbNotification;
                        const notificationPayload =
                            [...teamNotifications].find(n => n.notificationType.toUpperCase() === notificationType.toUpperCase()) || {};
                        if (!isEmpty(notificationPayload)) {
                            let { subscribedEmails: dbSubscribedEmails, optIn: dbOptIn } = dbNotification;
                            let { subscribedEmails: payLoadSubscribedEmails, optIn: payLoadOptIn } = notificationPayload;
                            const removedEmails = difference([...dbSubscribedEmails], [...payLoadSubscribedEmails]) || [];
                            const addedEmails = difference([...payLoadSubscribedEmails], [...dbSubscribedEmails]) || [];
                            const subscribedMembersByType = teamV3Util.filterMembersByNoticationTypes([...members], [notificationType]);
                            if (!isEmpty(subscribedMembersByType)) {
                                const memberIds = [...subscribedMembersByType].map(m => m.memberid.toString());
                                const { memberEmails, userIds } = teamV3Util.getMemberDetails([...memberIds], [...users]);
                                let html = '';
                                let options = {
                                    managerName: `${manager.firstname} ${manager.lastname}`,
                                    notificationRemoved: false,
                                    disabled: false,
                                    header: '',
                                    emailAddresses: [],
                                };
                                if (!isEmpty(removedEmails) || (dbOptIn && !payLoadOptIn)) {
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
                                    html = emailGenerator.generateTeamNotificationEmail(options);
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

                                if (!isEmpty(addedEmails) || (!dbOptIn && payLoadOptIn)) {
                                    options = {
                                        ...options,
                                        notificationRemoved: false,
                                        header: `A manager for ${team.publisher ? team.publisher.name : 'a team'} has ${
                                            !dbOptIn && payLoadOptIn ? 'enabled all' : 'added a'
                                        } generic team email address(es)`,
                                        emailAddresses: payLoadSubscribedEmails,
                                        publisherId: team.publisher._id.toString(),
                                    };
                                    html = emailGenerator.generateTeamNotificationEmail(options);
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
                team.notifications = teamNotifications;
            }
            member.notifications = memberNotifications;
            await team.save();
            return res.status(201).json(team);
        } catch (err) {
            process.stdout.write(err.message);
            throw new HttpExceptions(`An error occurred updating notification messages : ${err.message}`, 500);
        }
    }

    async updateNotificationMessages(req, res) {
        const teamId = req.params.teamid;
        const currentUserId = req.user._id;
        const allowPerms = req.allowPerms || [];

        try {
            await this.checkUserAuth(teamId, currentUserId, allowPerms);

            const {
                user: { _id },
            } = req;
            await TeamModel.update(
                { _id: teamId },
                { $set: { 'members.$[m].notifications.$[].message': '' } },
                { arrayFilters: [{ 'm.memberid': _id }], multi: true }
            )
                .then(resp => {
                    return res.status(201).json();
                })
                .catch(err => {
                    process.stdout.write(err.message);
                    throw new HttpExceptions(`An error occurred updating notification messages : ${err.message}`, 500);
                });
        } catch (err) {
            process.stdout.write(err.message);
            throw new HttpExceptions(`An error occurred updating notification messages : ${err.message}`, 500);
        }
    }

    async checkUserAuth(teamId, userId, allowPerms) {
        const currUserRolesFromTeamPublisher = await this.getPermsByUserIdFromTeamPublisher(teamId, userId);
        const currUserRolesFromTeamAdmin = await this.getPermsByUserIdFromTeamAdmin(userId);
        const currUserRolesExists = [...currUserRolesFromTeamPublisher, ...currUserRolesFromTeamAdmin];
        const currUserRolesUnique = [...new Set(currUserRolesExists)];
        teamV3Util.checkingUserAuthorization(allowPerms, currUserRolesUnique);

        return currUserRolesUnique;
    }

    sendLogInGoogle(message) {
        const loggingEnabled = parseInt(process.env.LOGGING_LOG_ENABLED) || 0;
        if (loggingEnabled) {
            this._logger.sendDataInLogging(JSON.stringify(message), 'INFO');
        }
    }
}

module.exports = new TeamController();
