import _ from 'lodash';
import mongoose from 'mongoose';

import { activityLogService } from '../../activitylog/dependency';
import constants from '../../utilities/constants.util';
import { Data as ToolModel } from '../../tool/data.model';
import { dataRequestService } from '../../datarequest/dependency';
import { MessagesModel } from './../message.model';
import { ROLES } from '../../user/user.roles';
import { TopicModel } from '../../topic/topic.model';
import teamController from '../../team/team.controller';
import { sendNotification, sendPubSubMessage } from './notification';

const topicController = require('../../topic/topic.controller');


class MessageController
{
    limitRows = 50;
    roles = [ROLES.Admin.toLowerCase(), ROLES.Creator.toLowerCase()];

    constructor() {}

    async createMessage(req, res) {
        try {
            const { _id: createdBy, firstname, lastname, isServiceAccount = false } = req.user;
            let { messageType = 'message', topic = '', messageDescription, relatedObjectIds, firstMessage } = req.body;
            let topicObj = {};
            let team, publisher, userType;
            let tools = {};

            const userRole = req.user.role.toLowerCase();

            if (!this.checkUserRole(userRole)) {
                process.stdout.write(`MESSAGE - createMessage : the user role is not Admin or Creator`);
                res.status(500).json(`An error occurred - user does not have the right roles`);
            }

            // 1. If the message type is 'message' and topic id is empty
            if (messageType === 'message') {
                // 2. Find the related object(s) in MongoDb and include team data to update topic recipients in case teams have changed
                tools = await ToolModel.find()
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

                if (_.isEmpty(tools)) return undefined;

                ({ publisher = '' } = tools[0]);

                if (_.isEmpty(publisher)) {
                    process.stdout.write(`No publisher associated to this dataset\n`);
                    return res.status(500).json({ success: false, message: 'No publisher associated to this dataset' });
                }

                ({ team = [] } = publisher);
                if (_.isEmpty(team)) {
                    process.stdout.write(`No team associated to publisher, cannot message\n`);
                    return res.status(500).json({ success: false, message: 'No team associated to publisher, cannot message' });
                }

                userType = teamController.checkTeamPermissions('', team.toObject(), req.user._id)
                    ? constants.userTypes.CUSTODIAN
                    : constants.userTypes.APPLICANT;
                if (_.isEmpty(topic)) {
                    topicObj = await topicController.buildTopic({ createdBy, relatedObjectIds });
                    if (!topicObj) return res.status(500).json({ success: false, message: 'Could not save topic to database.' });
                    topic = topicObj._id;
                } else {
                    topicObj = await topicController.findTopic(topic, createdBy);
                    if (!topicObj) return res.status(404).json({ success: false, message: 'The topic specified could not be found' });
                    topicObj.recipients = await topicController.buildRecipients(team, topicObj.createdBy);
                    await topicObj.save();
                }

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

            if (!message) return res.status(500).json({ success: false, message: 'Could not save message to database.' });

            if (messageType === 'message') {
                await sendNotification(topicObj, team);
                await sendPubSubMessage(tools, topicObj._id, message, req.body.firstMessage, isServiceAccount);
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
            process.stdout.write(`MESSAGE - createMessage : ${err.message}\n`);
            return res.status(500).json(err.message);
        }
    }

    async getCountUnreadMessagesByPersonId(req, res) {
        const personId = parseInt(req.params.personId) || '';
        const userRole = req.user.role.toLowerCase();

        if (!this.checkUserRole(userRole)) {
            process.stdout.write(`MESSAGE - getCountUnreadMessagesByPersonId : the user role is not Admin or Creator`);
            res.status(500).json(`An error occurred - user does not have the right roles`);
        }

        const pipeline = this.messagesAggregatePipelineCount(userRole, personId);
        const messages = await this.getDataMessages(pipeline);

        const countUnreadMessages = messages.filter(element => element.isRead !== 'false').length;

        return res.status(200).json({ countUnreadMessages });
    }

    async getMessagesByPersonId(req, res) {
        const personId = parseInt(req.params.personId) || '';
        const userRole = req.user.role.toLowerCase();

        if (!this.checkUserRole(userRole)) {
            process.stdout.write(`MESSAGE - getMessagesByPersonId : the user role is not Admin or Creator`);
            res.status(500).json(`An error occurred - user does not have the right roles`);
        }

        const pipeline = this.messagesAggregatePipelineByPersonId(userRole, personId);
        const messages = await this.getDataMessages(pipeline);

        return res.status(200).json({ success: true, data: messages });
    }

    async getUnreadMessageCount(req, res){
        try {
            const { _id: userId } = req.user;
            let unreadMessageCount = 0;

            const topics = await TopicModel.find({
                recipients: { $elemMatch: { $eq: userId } },
                status: 'active',
            });

            topics.forEach(topic => {
                topic.topicMessages.forEach(message => {
                    if (!message.readBy.includes(userId)) {
                        unreadMessageCount++;
                    }
                });
            });

            return res.status(200).json({ success: true, count: unreadMessageCount });
        } catch (err) {
            process.stdout.write(`MESSAGE - getUnreadMessageCount : ${err.message}\n`);
            return res.status(500).json({ message: err.message });
        }
    }

    async deleteMessage(req, res) {
        try {
            const { id } = req.params;
            const { _id: userId } = req.user;

            const userRole = req.user.role.toLowerCase();

            if (!this.checkUserRole(userRole)) {
                process.stdout.write(`MESSAGE - createMessage : the user role is not Admin or Creator`);
                res.status(500).json(`An error occurred - user does not have the right roles`);
            }

            if (!id) { 
                return res.status(404).json({ success: false, message: 'Message Id not found.' });
            }

            const message = await MessagesModel.findOne({ _id: id });

            if (!message) {
                return res.status(404).json({ success: false, message: 'Message not found for ${id}' });
            }

            if (message.createdBy.toString() !== userId.toString()) {
                return res.status(401).json({ success: false, message: 'Not authorised to delete this message' });
            }

            await MessagesModel.remove({ _id: id });

            const messagesCount = await MessagesModel.count({ topic: message.topic });

            if (!messagesCount) {
                await TopicModel.remove({ _id: new mongoose.Types.ObjectId(message.topic) });
            }

            return res.status(204).json({ success: true });
        } catch (err) {
            process.stdout.write(`MESSAGE - deleteMessage : ${err.message}\n`);
            return res.status(500).json({ message: err.message });
        }
    }

    async updateMessage(req, res) {
        try {
            let { _id: userId } = req.user;
            let { messageId, isRead, messageDescription = '', messageType = '' } = req.body;

            const userRole = req.user.role.toLowerCase();

            if (!this.checkUserRole(userRole)) {
                process.stdout.write(`MESSAGE - createMessage : the user role is not Admin or Creator`);
                res.status(500).json(`An error occurred - user does not have the right roles`);
            }

            if (!messageId) return res.status(404).json({ success: false, message: 'Message Id not found.' });

            const message = await MessagesModel.findOne({ _id: messageId });

            if (!message) {
                return res.status(404).json({ success: false, message: 'Message not found.' });
            }

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

            await message.save();

            return res.status(204).json({ success: true });
        } catch (err) {
            process.stdout.write(`MESSAGE - updateMessage : ${err.message}\n`);
            return res.status(500).json({ message: err.message });
        }
    }

    async getDataMessages(pipeline) {
        try {
            const statement = MessagesModel.aggregate(pipeline).limit(this.limitRows);
            return await statement.exec();
        } catch (err) {
            process.stdout.write(`MessageController.getDataReviews : ${err.message}`);
            throw new Error(`An error occurred : ${err.message}`);
        }
    }

    messagesAggregatePipelineByPersonId(role, personId) {
        let query = [];

        if (role === ROLES.Admin.toLowerCase()) {
            query.push({ "$match": { "$and": [{ "$or": [{ "messageTo": personId }, { "messageTo": 0 }] }] } });
        }

        if (role === ROLES.Creator.toLowerCase()) {
            query.push({ "$match": { "$and": [{ "messageTo": personId }] } });
        }

        query.push({ "$sort": { "createdDate": -1 } });
        query.push({ "$lookup": { "from": "tools", "localField": "messageObjectID", "foreignField": "id", "as": "tool" } });
        query.push({ "$lookup": { "from": "course", "localField": "messageObjectID", "foreignField": "id", "as": "course" } });

        return query;
    }

    messagesAggregatePipelineCount(role, personId) {
        let query = [];

        if (role === ROLES.Admin.toLowerCase()) {
            query.push({ "$match": { "$and": [{ "$or": [{ "messageTo": personId }, { "messageTo": 0 }] }] } });
        }

        if (role === ROLES.Creator.toLowerCase()) {
            query.push({ "$match": { "$and": [{ "messageTo": personId }] } });
        }

        query.push({ "$sort": { "createdDate": -1 } });
        query.push({ "$lookup": { "from": "tools", "localField": "messageObjectID", "foreignField": "id", "as": "tool" } });

        return query;
    }

    checkUserRole(userRole) {
        return (this.roles.indexOf(userRole) > -1)
    }
}

module.exports = new MessageController();