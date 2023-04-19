import { MessagesModel } from '../message/message.model';

const triggerNotificationMessage = (messageRecipients, messageDescription, messageType, messageObjectID, publisherName = '') => {
	messageRecipients.forEach(async recipient => {
		let messageID = parseInt(Math.random().toString().replace('0.', ''));
		let message = new MessagesModel({
			messageType,
			messageSent: Date.now(),
			messageDescription,
			isRead: false,
			messageID,
			messageObjectID: typeof messageObjectID == 'number' ? messageObjectID : messageID,
			messageTo: recipient,
			messageDataRequestID: messageType === 'data access request' || messageType === 'data access message sent' ? messageObjectID : null,
			publisherName,
			datasetID: messageType === 'dataset approved' || messageType === 'dataset rejected' ? messageObjectID : null,
		});
		await message.save(async err => {
			if (err) {
				process.stdout.write(`NOTIFICATION BUILDER - Failed to save ${messageType} message with error : ${err.message}\n`);
			}
		});
	});
};

export default {
	triggerNotificationMessage: triggerNotificationMessage,
};
