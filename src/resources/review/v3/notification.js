import { Data } from '../../tool/data.model';
import { UserModel } from '../../user/user.model';
import { MessagesModel } from '../../message/message.model';

export const storeNotificationMessages = async (review) => {
	const tool = await Data.findOne({ id: review.toolID });
	const reviewer = await UserModel.findOne({ id: review.reviewerID });
	const toolLink = process.env.homeURL + '/tool/' + review.toolID + '/' + tool.name;
    const messageId = parseInt(Math.random().toString().replace('0.', ''));

	let message = new MessagesModel();
	message.messageID = messageId;
	message.messageTo = 0;
	message.messageObjectID = review.toolID;
	message.messageType = 'review';
	message.messageSent = Date.now();
	message.isRead = false;
	message.messageDescription = `${reviewer.firstname} ${reviewer.lastname} gave a ${review.rating}-star review to your tool ${tool.name} ${toolLink}`;

	await message.save(async err => {
		if (err) {
			return new Error({ success: false, error: err });
		}
	});

	const authors = tool.authors;
	authors.forEach(async author => {
		message.messageTo = author;
		await message.save(async err => {
			if (err) {
				return new Error({ success: false, error: err });
			}
		});
	});

	return { success: true, id: messageId };
}