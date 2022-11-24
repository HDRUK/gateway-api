import { TopicModel } from '../src/resources/topic/topic.model';
import { MessagesModel } from '../src/resources/message/message.model';
import { UserModel } from '../src/resources/user/user.model';

/**
 * Make any changes you need to make to the database here
 */
async function up () {

  // 1. Find all topics relating to datasets
  const topics = await TopicModel.find({ datasets: { $exists: true, $not: {$size: 0}} }).select({ datasets: 1 }).lean();
  
  let ops = [];

	topics.forEach(topic => {
		const { _id } = topic;
		ops.push({
			updateOne: {
				filter: { _id },
				update: {
          is5Safes: true
				},
				upsert: false,
			},
		});
	});

	await TopicModel.bulkWrite(ops);
}

/**
 * Make any changes that UNDO the up function side effects here (if possible)
 */
async function down () {

  // 1. Find all topics relating to datasets
  const topics = await TopicModel.find({ datasets: { $exists: true, $not: {$size: 0}} }).select({ datasets: 1 }).lean();
  
  let ops = [];

	topics.forEach(topic => {
		const { _id } = topic;
		ops.push({
			updateOne: {
				filter: { _id },
				update: {
          is5Safes: undefined
				},
				upsert: false,
			},
		});
	});

	await TopicModel.bulkWrite(ops);
}

module.exports = { up, down };
