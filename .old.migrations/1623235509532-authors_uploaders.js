import { Data as ToolModel } from '../src/resources/tool/data.model';

// Add the uploader to the list of authors if not present
async function up() {
	let toolRecords = await ToolModel.find({ uploader: { $exists: true } });
	let ops = [];

	toolRecords.forEach(toolRecord => {
		const { uploader, authors = [], _id } = toolRecord;

		if (!authors.includes(uploader)) {
			authors.push(uploader);

			ops.push({
				updateOne: {
					filter: { _id },
					update: {
						uploader,
						authors,
					},
					upsert: false,
				},
			});
		}
	});

	await ToolModel.bulkWrite(ops);
}

async function down() {}

module.exports = { up, down };
