import _ from 'lodash';
import mongoose from 'mongoose';

import constants from '../../utilities/constants.util';
import emailGenerator from '../../utilities/emailGenerator.util';
import { publishMessageWithRetryToPubSub } from '../../../services/google/PubSubWithRetryService';
import { PublisherModel } from '../../publisher/publisher.model';
import teamController from '../../team/team.controller';
import { UserModel } from '../../user/user.model';

const { ObjectId } = require('mongodb');

export const sendNotification = async (topicObj, team) => {
    let optIn, subscribedEmails, messageCreatorRecipient;
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
                if (topicObj.topicMessages !== undefined) {
                    const memberIds = [...subscribedMembersByType.map(m => m.memberid.toString()), ...topicObj.createdBy._id.toString()];
                    // returns array of objects [{email: 'email@email.com '}] for members in subscribed emails users is list of full user object
                    const { memberEmails } = teamController.getMemberDetails([...memberIds], [...messageRecipients]);
                    messageRecipients = [...teamNotificationEmails, ...memberEmails];
                } else {
                    const memberIds = [...subscribedMembersByType.map(m => m.memberid.toString())].filter(
                        ele => ele !== topicObj.createdBy.toString()
                    );
                    const creatorObjectId = topicObj.createdBy.toString();
                    // returns array of objects [{email: 'email@email.com '}] for members in subscribed emails users is list of full user object
                    const { memberEmails } = teamController.getMemberDetails([...memberIds], [...messageRecipients]);
                    const creatorEmail = await UserModel.findById(creatorObjectId);
                    messageCreatorRecipient = [{ email: creatorEmail.email }];
                    messageRecipients = [...teamNotificationEmails, ...memberEmails];
                }
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

    if (messageCreatorRecipient) {
        let htmlCreator = emailGenerator.generateMessageCreatorNotification(options);

        emailGenerator.sendEmail(
            messageCreatorRecipient,
            constants.hdrukEmail,
            `You have received a new message on the HDR UK Innovation Gateway`,
            htmlCreator,
            false
        );
    }
}

export const sendPubSubMessage = async (tools, topicObjId, message, firstMessag, isServiceAccount) => {
    try {
        const cacheEnabled = parseInt(process.env.CACHE_ENABLED) || 0;

        if (cacheEnabled && !isServiceAccount) {
            let publisherDetails = await PublisherModel.findOne({ _id: ObjectId(tools[0].publisher._id) }).lean();

            if (publisherDetails['dar-integration'] && publisherDetails['dar-integration']['enabled']) {
                const pubSubMessage = {
                    id: '',
                    type: 'enquiry',
                    publisherInfo: {
                        id: publisherDetails._id,
                        name: publisherDetails.name,
                    },
                    details: {
                        topicId: topicObjId,
                        messageId: message.messageID,
                        createdDate: message.createdDate,
                        questionBank: firstMessage,
                    },
                    darIntegration: publisherDetails['dar-integration'],
                };
                await publishMessageWithRetryToPubSub(process.env.PUBSUB_TOPIC_ENQUIRY, JSON.stringify(pubSubMessage));
            }
        }
    } catch (err) {
        process.stdout.write(`MESSAGE - createMessage - send PubSub Message : ${err.message}\n`);
        return res.status(500).json(err.message);
    }
}