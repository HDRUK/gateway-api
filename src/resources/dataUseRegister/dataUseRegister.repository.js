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

	updateDataUseRegister(id, body) {
		return this.update(id, body);
	}

	uploadDataUseRegisters(dataUseRegisters) {
		return this.dataUseRegister.insertMany(dataUseRegisters);
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
