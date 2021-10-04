import sinon from 'sinon';

import DataUseRegisterService from '../dataUseRegister.service';
import DataUseRegisterRepository from '../dataUseRegister.repository';
import { dataUseRegisterUploadsWithDuplicates, dataUseRegisterUploads } from '../__mocks__/dataUseRegisters';

describe('DataUseRegisterService', function () {
	describe('filterDuplicateDataUseRegisters', function () {
		it('filters out data uses that have matching project Ids', async function () {
			// Arrange
			const dataUseRegisterRepository = new DataUseRegisterRepository();
			const dataUseRegisterService = new DataUseRegisterService(dataUseRegisterRepository);

			// Act
			const result = dataUseRegisterService.filterDuplicateDataUseRegisters(dataUseRegisterUploadsWithDuplicates);

			// Assert
			expect(dataUseRegisterUploadsWithDuplicates.length).toEqual(6);
			expect(result.length).toEqual(2);
			expect(result[0].projectIdText).not.toEqual(result[1].projectIdText);
			expect(result[0]).toEqual(dataUseRegisterUploadsWithDuplicates[0]);
		});
		it('filters out duplicate data uses that match across the following fields: project title, lay summary, organisation name, dataset names and latest approval date', async function () {
			// Arrange
			const dataUseRegisterRepository = new DataUseRegisterRepository();
			const dataUseRegisterService = new DataUseRegisterService(dataUseRegisterRepository);

			// Act
			const result = dataUseRegisterService.filterDuplicateDataUseRegisters(dataUseRegisterUploadsWithDuplicates);

			// Assert
			expect(dataUseRegisterUploadsWithDuplicates.length).toEqual(6);
			expect(result.length).toEqual(2);
			expect(result[1]).toEqual(dataUseRegisterUploadsWithDuplicates[4]);
		});
	});

	describe('filterExistingDataUseRegisters', function () {
		it('filters out data uses that are found to already exist in the database', async function () {
			// Arrange
			const dataUseRegisterRepository = new DataUseRegisterRepository();
			const dataUseRegisterService = new DataUseRegisterService(dataUseRegisterRepository);

			const checkDataUseRegisterExistsStub = sinon.stub(dataUseRegisterRepository, 'checkDataUseRegisterExists');
			checkDataUseRegisterExistsStub.onCall(0).returns(false);
			checkDataUseRegisterExistsStub.onCall(1).returns(true);

			// Act
			const result = await dataUseRegisterService.filterExistingDataUseRegisters(dataUseRegisterUploads);

			// Assert
			expect(checkDataUseRegisterExistsStub.calledTwice).toBe(true);
			expect(dataUseRegisterUploads.length).toBe(2);
			expect(result.length).toBe(1);
			expect(result[0].projectIdText).toEqual(dataUseRegisterUploads[0].projectIdText);
		});
	});
});
