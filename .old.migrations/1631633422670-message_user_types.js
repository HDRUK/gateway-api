import { MessagesModel } from '../src/resources/message/message.model';
import { TeamModel } from '../src/resources/team/team.model';
import constants from '../src/resources/utilities/constants.util';

/**
 * Make any changes you need to make to the database here
 */
async function up() {
	// 1. Find all messages
	const messages = await MessagesModel.find({ messageType: 'message' }).lean();

	let ops = [];

	for (const message of messages) {
		const { _id, createdBy } = message;

		const creatorTeamCount = await TeamModel.countDocuments({ 'members.memberid': createdBy });
		const userType = creatorTeamCount > 0 ? constants.userTypes.CUSTODIAN : constants.userTypes.APPLICANT;

		ops.push({
			updateOne: {
				filter: { _id },
				update: {
					userType,
				},
				upsert: false,
			},
		});
	}

	await MessagesModel.bulkWrite(ops);
}

/**
 * Make any changes that UNDO the up function side effects here (if possible)
 */
async function down() {}

module.exports = { up, down };
