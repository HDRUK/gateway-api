import sinon from 'sinon';
import mongoose from 'mongoose';

import dbHandler from '../../../config/in-memory-db';
import { dataUseRegistersStub } from '../__mocks__/dataUseRegisters';
import DataUseRegisterController from '../dataUseRegister.controller';

beforeAll(async () => {
	await dbHandler.connect();
	await dbHandler.loadData({ datauseregisters: dataUseRegistersStub });
	await mongoose.connection
		.collection('datauseregisters')
		.createIndex({ datasetTitles: 'text', fundersAndSponsors: 'text', keywords: 'text', laySummary: 'text', projectTitle: 'text' });
});

afterEach(() => {
	sinon.restore();
});

afterAll(async () => {
	await dbHandler.closeDatabase();
});

describe('CLASS: dataUseRegisterController', () => {
	const dataUseRegisterController = new DataUseRegisterController();

	const mockedRequest = () => {
		const req = {
			query: {},
			params: {},
		};
		return req;
	};

	const mockedResponse = () => {
		const res = {};
		res.status = jest.fn().mockReturnValue(res);
		res.json = jest.fn().mockReturnValue(res);
		return res;
	};

	describe('METHOD: searchDataUseRegisters', () => {
		it('TEST: it should return a 200 response and 2 DURs if no search string is given', async () => {
			const req = mockedRequest();
			const res = mockedResponse();

			await dataUseRegisterController.searchDataUseRegisters(req, res);

			expect(res.json.mock.calls[0][0].result.length).toBe(2);
			expect(res.status).toHaveBeenCalledWith(200);
		});

		it('TEST: it should filter the results appropriately based on a free text search term', async () => {
			const req = mockedRequest();
			const res = mockedResponse();

			req.query.search = 'second';

			await dataUseRegisterController.searchDataUseRegisters(req, res);

			expect(res.json.mock.calls[0][0].result.length).toBe(1);
			expect(res.status).toHaveBeenCalledWith(200);
		});
	});
});
