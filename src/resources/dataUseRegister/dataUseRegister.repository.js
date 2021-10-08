import Repository from '../base/repository';
import { DataUseRegister } from './dataUseRegister.model';

export default class DataUseRegisterRepository extends Repository {
	constructor() {
		super(DataUseRegister);
		this.dataUseRegister = DataUseRegister;
	}

	getDataUseRegister(query, options) {
		return this.findOne(query, options);
	}

	getDataUseRegisters(query) {
		const options = { lean: true };
		return this.find(query, options);
	}

	getDataUseRegisterByApplicationId(applicationId) {
		return this.dataUseRegister.findOne({ projectId: applicationId }, 'id').lean();
	}

	updateDataUseRegister(id, body) {
		return this.update(id, body);
	}

	uploadDataUseRegisters(dataUseRegisters) {
		return this.dataUseRegister.insertMany(dataUseRegisters);
	}

	async createDataUseRegister(dataUseRegister) {
		await this.linkRelatedDataUseRegisters(dataUseRegister);
		return await this.create(dataUseRegister);
	}

	async linkRelatedDataUseRegisters(dataUseRegister) {
		const { relatedObjects = [], userName } = dataUseRegister;
		const dataUseRegisterIds = relatedObjects.filter(el => el.objectType === 'dataUseRegister').map(el => el.objectId);
		const relatedObject = {
			objectId: dataUseRegister.id,
			objectType: 'dataUseRegister',
			user: userName,
			updated: Date.now(),
			isLocked: true,
			reason: `This data use register was added automatically as it was derived from a newer approved version of the same data access request`,
		};

		await this.dataUseRegister.updateMany(
			{ id: { $in: dataUseRegisterIds } },
			{
				$push: {
					relatedObjects: relatedObject,
				},
			}
		);
	}

	async checkDataUseRegisterExists(dataUseRegister) {
		const { projectIdText, projectTitle, laySummary, organisationName, datasetTitles, latestApprovalDate } = dataUseRegister;
		const duplicatesFound = await this.dataUseRegister.countDocuments({
			$or: [
				{ projectIdText },
				{
					projectTitle,
					laySummary,
					organisationName,
					datasetTitles,
					latestApprovalDate,
				},
			],
		});

		return duplicatesFound > 0;
	}
}
