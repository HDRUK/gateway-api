import { DataRequestModel } from '../src/resources/datarequest/datarequest.model';

async function up() {
	// 1. Add default application type to all applications
	// 2. Add version 1 to all applications
	// 3. Create version tree for all applications

	const accessRecords = await DataRequestModel.find({ isShared: true }).select('_id versionTree isShared').lean();
	const ops = [];

	accessRecords.forEach(accessRecord => {
		try {
			const { isShared = false, versionTree = {}, _id: applicationId } = accessRecord;

			Object.keys(versionTree).forEach(key => {
				versionTree[key].isShared = isShared;
			});

			ops.push({
				updateOne: {
					filter: { _id: applicationId },
					update: {
						versionTree,
					},
					upsert: false,
				},
			});
		} catch (err) {
			process.stdout.write(`Migration error - shared applications versioning: ${err.message}\n`);
		}
	});

	await DataRequestModel.bulkWrite(ops);
}

/**
 * Make any changes that UNDO the up function side effects here (if possible)
 */
async function down() {
	// Write migration here
}

module.exports = { up, down };
