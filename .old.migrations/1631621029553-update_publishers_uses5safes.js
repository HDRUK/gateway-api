import { PublisherModel } from '../src/resources/publisher/publisher.model';

/**
 * Make any changes you need to make to the database here
 */
async function up() {
	// 1. Find all publishers
	const publishers = await PublisherModel.find({ allowAccessRequestManagement: true, name: { $not : /.*ALLIANCE > NHS DIGITAL.*/i } }).lean();

	let ops = [];

	publishers.forEach(publisher => {
		const { _id } = publisher;
		ops.push({
			updateOne: {
				filter: { _id },
				update: {
					uses5Safes: true,
				},
				upsert: false,
			},
		});
	});

	await PublisherModel.bulkWrite(ops);
}

/**
 * Make any changes that UNDO the up function side effects here (if possible)
 */
async function down() {}

module.exports = { up, down };

