import dataUseRegisterUtil from './dataUseRegister.util';

export default class DataUseRegisterService {
	constructor(dataUseRegisterRepository) {
		this.dataUseRegisterRepository = dataUseRegisterRepository;
	}

	getDataUseRegister(id, query = {}, options = {}) {
		// Protect for no id passed
		if (!id) return;

		query = { ...query, _id: id };
		return this.dataUseRegisterRepository.getDataUseRegister(query, options);
	}

	getDataUseRegisters(query = {}) {
		return this.dataUseRegisterRepository.getDataUseRegisters(query);
	}

	updateDataUseRegister(id, body = {}) {
		// Protect for no id passed
		if (!id) return;
		
		return this.dataUseRegisterRepository.updateDataUseRegister({ _id: id }, body);
	}

	/**
	 * Upload Data Use Registers
	 *
	 * @desc    Accepts multiple data uses to upload and a team identifier indicating which Custodian team to add the data uses to.
	 *
	 * @param 	{String} 			teamId 	    	Array of data use objects to filter until uniqueness exists
	 * @param 	{Array<Object>} 	dataUseUploads 	 Array of data use objects to filter until uniqueness exists
	 * @returns {Object}		Object containing the details of the upload operation including number of duplicates found in payload, database and number successfully added
	 */
	async uploadDataUseRegisters(creatorUser, teamId, dataUseRegisterUploads = []) {
		const dedupedDataUseRegisters = this.filterDuplicateDataUseRegisters(dataUseRegisterUploads);

		const dataUseRegisters = await dataUseRegisterUtil.buildDataUseRegisters(creatorUser, teamId, dedupedDataUseRegisters);

		const newDataUseRegisters = await this.filterExistingDataUseRegisters(dataUseRegisters);

		const uploadedDataUseRegisters = await this.dataUseRegisterRepository.uploadDataUseRegisters(newDataUseRegisters);

		return {
			uploadedCount: uploadedDataUseRegisters.length,
			duplicateCount: dataUseRegisterUploads.length - newDataUseRegisters.length,
			uploaded: uploadedDataUseRegisters,
		};
	}

	/**
	 * Filter Duplicate Data Uses
	 *
	 * @desc    Accepts multiple data uses and outputs a unique list of data uses based on each entities properties.
	 * 			A duplicate project id is automatically indicates a duplicate entry as the id must be unique.
	 * 			Alternatively, a combination of matching title, summary, organisation name, dataset titles and latest approval date indicates a duplicate entry.
	 * @param 	{Array<Object>} 	dataUses 	    	Array of data use objects to filter until uniqueness exists
	 * @returns {Array<Object>}		Filtered array of data uses assumed unique based on filter criteria
	 */
	filterDuplicateDataUseRegisters(dataUses) {
		return dataUses.reduce((arr, dataUse) => {
			const isDuplicate = arr.some(
				el =>
					el.projectIdText === dataUse.projectIdText ||
					(el.projectTitle === dataUse.projectTitle &&
						el.laySummary === dataUse.laySummary &&
						el.organisationName === dataUse.organisationName &&
						el.datasetTitles === dataUse.datasetTitles &&
						el.latestApprovalDate === dataUse.latestApprovalDate)
			);
			if (!isDuplicate) arr = [...arr, dataUse];
			return arr;
		}, []);
	}

	/**
	 * Filter Existing Data Uses
	 *
	 * @desc    Accepts multiple data uses, verifying each in turn is considered 'new' to the database, then outputs the list of data uses.
	 * 			A duplicate project id is automatically indicates a duplicate entry as the id must be unique.
	 * 			Alternatively, a combination of matching title, summary, organisation name and dataset titles indicates a duplicate entry.
	 * @param 	{Array<Object>} 	dataUses 	    	Array of data use objects to iterate through and check for existence in database
	 * @returns {Array<Object>}		Filtered array of data uses assumed to be 'new' to the database based on filter criteria
	 */
	async filterExistingDataUseRegisters(dataUses) {
		const newDataUses = [];

		for (const dataUse of dataUses) {
			const exists = await this.dataUseRegisterRepository.checkDataUseRegisterExists(dataUse);
			if (exists === false) newDataUses.push(dataUse);
		}

		return newDataUses;
	}
}
