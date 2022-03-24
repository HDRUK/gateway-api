import { MessagesModel } from './message.model';
import _ from 'lodash';
import { TopicModel } from '../topic/topic.model';
import mongoose from 'mongoose';
import { UserModel } from '../user/user.model';
import emailGenerator from '../utilities/emailGenerator.util';
import teamController from '../team/team.controller';
import { Data as ToolModel } from '../tool/data.model';
import constants from '../utilities/constants.util';
import { dataRequestService } from '../datarequest/dependency';
import { activityLogService } from '../activitylog/dependency';

const topicController = require('../topic/topic.controller');

module.exports = {
	// POST /api/v1/messages
	createMessage: async (req, res) => {
		try {
			const { _id: createdBy, firstname, lastname } = req.user;
			let { messageType = 'message', topic = '', messageDescription, relatedObjectIds, firstMessage } = req.body;
			let topicObj = {};
			let team, publisher, userType;
			// 1. If the message type is 'message' and topic id is empty
			if (messageType === 'message') {
				// 2. Find the related object(s) in MongoDb and include team data to update topic recipients in case teams have changed
				const tools = await ToolModel.find()
					.where('_id')
					.in(relatedObjectIds)
					.populate({
						path: 'publisher',
						populate: {
							path: 'team',
							select: 'members notifications',
							populate: {
								path: 'users',
							},
						},
					});
				// 3. Return undefined if no object(s) exists
				if (_.isEmpty(tools)) return undefined;

				// 4. Get recipients for new message
				({ publisher = '' } = tools[0]);

				if (_.isEmpty(publisher)) {
					console.error(`No publisher associated to this dataset`);
					return res.status(500).json({ success: false, message: 'No publisher associated to this dataset' });
				}
				// 5. get team
				({ team = [] } = publisher);
				if (_.isEmpty(team)) {
					console.error(`No team associated to publisher, cannot message`);
					return res.status(500).json({ success: false, message: 'No team associated to publisher, cannot message' });
				}
				// 6. Set user type (if found in team, they are custodian)
				userType = teamController.checkTeamPermissions('', team.toObject(), req.user._id)
					? constants.userTypes.CUSTODIAN
					: constants.userTypes.APPLICANT;
				if (_.isEmpty(topic)) {
					// 7. Create new topic
					topicObj = await topicController.buildTopic({ createdBy, relatedObjectIds });
					// 8. If topic was not successfully created, throw error response
					if (!topicObj) return res.status(500).json({ success: false, message: 'Could not save topic to database.' });
					// 9. Pass new topic Id
					topic = topicObj._id;
				} else {
					// 10. Find the existing topic
					topicObj = await topicController.findTopic(topic, createdBy);
					// 11. Return not found if it was not found
					if (!topicObj) {
						return res.status(404).json({ success: false, message: 'The topic specified could not be found' });
					}
					topicObj.recipients = await topicController.buildRecipients(team, topicObj.createdBy);
					await topicObj.save();
				}
				// 12. Update linkage to a matching presubmission DAR application
				if (!topicObj.linkedDataAccessApplication) {
					const accessRecord = await dataRequestService.linkRelatedApplicationByMessageContext(
						topicObj._id,
						req.user.id,
						topicObj.datasets.map(dataset => dataset.datasetId),
						constants.applicationStatuses.INPROGRESS
					);
					if (accessRecord) {
						topicObj.linkedDataAccessApplication = accessRecord._id;
						await topicObj.save();
					}
				}
			}
			// 13. Create new message
			const message = await MessagesModel.create({
				messageID: parseInt(Math.random().toString().replace('0.', '')),
				messageObjectID: parseInt(Math.random().toString().replace('0.', '')),
				messageDescription,
				firstMessage,
				topic,
				createdBy,
				messageType,
				readBy: [new mongoose.Types.ObjectId(createdBy)],
				...(userType && { userType }),
			});
			// 14. Return 500 error if message was not successfully created
			if (!message) return res.status(500).json({ success: false, message: 'Could not save message to database.' });

			// 15. Prepare to send email if a new message has been created
			if (messageType === 'message') {
				let optIn, subscribedEmails;
				// 16. Find recipients who have opted in to email updates and exclude the requesting user
				let messageRecipients = await UserModel.find({ _id: { $in: topicObj.recipients } });

				if (!_.isEmpty(team) || !_.isNil(team)) {
					// 17. team all users for notificationType + generic email
					// Retrieve notifications for the team based on type return {notificationType, subscribedEmails, optIn}
					let teamNotifications = teamController.getTeamNotificationByType(team, constants.teamNotificationTypes.DATAACCESSREQUEST);
					// only deconstruct if team notifications object returns - safeguard code
					if (!_.isEmpty(teamNotifications)) {
						// Get teamNotification emails if optIn true
						({ optIn = false, subscribedEmails = [] } = teamNotifications);
						// check subscribedEmails and optIn send back emails or blank []
						let teamNotificationEmails = teamController.getTeamNotificationEmails(optIn, subscribedEmails);
						// get users from team.members with notification type and optedIn only
						const subscribedMembersByType = teamController.filterMembersByNoticationTypesOptIn(
							[...team.members],
							[constants.teamNotificationTypes.DATAACCESSREQUEST]
						);
						if (!_.isEmpty(subscribedMembersByType)) {
							// build cleaner array of memberIds from subscribedMembersByType
							const memberIds = [...subscribedMembersByType.map(m => m.memberid.toString()), topicObj.createdBy.toString()];
							// returns array of objects [{email: 'email@email.com '}] for members in subscribed emails users is list of full user object
							const { memberEmails } = teamController.getMemberDetails([...memberIds], [...messageRecipients]);
							messageRecipients = [...teamNotificationEmails, ...memberEmails];
						} else {
							// only if not membersByType but has a team email setup
							messageRecipients = [...messageRecipients, ...teamNotificationEmails];
						}
					}
				}

				// Create object to pass through email data
				let options = {
					firstMessage,
					firstname,
					lastname,
					messageDescription,
					openMessagesLink: process.env.homeURL + '/search?search=&tab=Datasets&openUserMessages=true',
				};
				// Create email body content
				let html = emailGenerator.generateMessageNotification(options);

				// 18. Send email
				emailGenerator.sendEmail(
					messageRecipients,
					constants.hdrukEmail,
					`You have received a new message on the HDR UK Innovation Gateway`,
					html,
					false
				);
			}
			// 19. Return successful response with message data
			const messageObj = message.toObject();
			messageObj.createdByName = { firstname, lastname };
			messageObj.createdBy = { _id: createdBy, firstname, lastname };

			// 20. Update activity log if there is a linked DAR
			if (topicObj.linkedDataAccessApplication) {
				activityLogService.logActivity(constants.activityLogEvents.data_access_request.PRESUBMISSION_MESSAGE, {
					messages: [messageObj],
					applicationId: topicObj.linkedDataAccessApplication,
					publisher: publisher.name,
				});
			}

			return res.status(201).json({ success: true, messageObj });
		} catch (err) {
			console.error(err.message);
			return res.status(500).json(err.message);
		}
	},
	// DELETE /api/v1/messages/:id
	deleteMessage: async (req, res) => {
		try {
			const { id } = req.params;
			const { _id: userId } = req.user;
			// 1. Return not found error if id not passed
			if (!id) return res.status(404).json({ success: false, message: 'Message Id not found.' });
			// 2. Get message by Id from MongoDb
			const message = await MessagesModel.findOne({ _id: id });
			// 3. Return not found if message not found
			if (!message) {
				return res.status(404).json({ success: false, message: 'Message not found.' });
			}
			// 4. Check that the message was created by requesting user otherwise return unathorised
			if (message.createdBy.toString() !== userId.toString()) {
				return res.status(401).json({ success: false, message: 'Not authorised to delete this message' });
			}
			// 5. Delete message by id
			await MessagesModel.remove({ _id: id });
			// 6. Check attached topic for other messages to avoid orphaning topic documents
			const messagesCount = await MessagesModel.count({ topic: message.topic });
			// 7. If no other messages remain then delete the topic
			if (!messagesCount) {
				await TopicModel.remove({ _id: new mongoose.Types.ObjectId(message.topic) });
			}
			// 8. Return successful response
			return res.status(204).json({ success: true });
		} catch (err) {
			console.error(err.message);
			return res.status(500).json(err.message);
		}
	},
	// PUT /api/v1/messages
	updateMessage: async (req, res) => {
		try {
			let { _id: userId } = req.user;
			let { messageId, isRead, messageDescription = '', messageType = '' } = req.body;
			// 1. Return not found error if id not passed
			if (!messageId) return res.status(404).json({ success: false, message: 'Message Id not found.' });
			// 2. Get message by object id
			const message = await MessagesModel.findOne({ _id: messageId });
			// 3. Return not found if message not found
			if (!message) {
				return res.status(404).json({ success: false, message: 'Message not found.' });
			}
			// 4. Update message params - readBy is an array of users who have read the message
			if (isRead && !message.readBy.includes(userId.toString())) {
				message.readBy.push(userId);
			}
			if (isRead) {
				message.isRead = isRead;
			}
			if (!_.isEmpty(messageDescription)) {
				message.messageDescription = messageDescription;
			}
			if (!_.isEmpty(messageType)) {
				message.messageType = messageType;
			}
			// 5. Save message to Mongo
			await message.save();
			// 6. Return success no content
			return res.status(204).json({ success: true });
		} catch (err) {
			console.error(err.message);
			return res.status(500).json(err.message);
		}
	},
	// GET api/v1/messages/unread/count
	getUnreadMessageCount: async (req, res) => {
		try {
			let { _id: userId } = req.user;
			let unreadMessageCount = 0;

			// 1. Find all active topics the user is a member of
			const topics = await TopicModel.find({
				recipients: { $elemMatch: { $eq: userId } },
				status: 'active',
			});
			// 2. Iterate through each topic and aggregate unread messages
			topics.forEach(topic => {
				topic.topicMessages.forEach(message => {
					if (!message.readBy.includes(userId)) {
						unreadMessageCount++;
					}
				});
			});
			// 3. Return the number of unread messages
			return res.status(200).json({ success: true, count: unreadMessageCount });
		} catch (err) {
			console.error(err.message);
			return res.status(500).json(err.message);
		}
	},
};
