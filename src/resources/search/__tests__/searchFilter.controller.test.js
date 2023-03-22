import sinon from 'sinon';

import { filtersService } from '../../filters/dependency';
import SearchFilterController from '../searchFilter.controller';

afterEach(function () {
	sinon.restore();
});

describe('searchFilterController', () => {
	const mockedRequest = () => {
		const req = {
			query: {},
		};
		return req;
	};

	const mockedResponse = () => {
		const res = {};
		res.status = jest.fn().mockReturnValue(res);
		res.json = jest.fn().mockReturnValue(res);
		return res;
	};

	const searchFilterController = new SearchFilterController(filtersService);

	describe('getSearchFilters', () => {
		it('should return a base list of filters if no filter selected', async () => {
			const filterServiceStub = sinon.stub(filtersService, 'buildFilters').resolves({ publisher: ['SAIL', 'PUBLIC HEALTH SCOTLAND'] });
			3;
			let req = mockedRequest();
			let res = mockedResponse();

			req.query.tab = 'Datasets';
			req.query.search = '';

			const expectedResponse = {
				success: true,
				filters: {
					publisher: ['SAIL', 'PUBLIC HEALTH SCOTLAND'],
					spatialv2: [],
				},
			};

			await searchFilterController.getSearchFilters(req, res);

			expect(res.status).toHaveBeenCalledWith(200);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(filterServiceStub.calledOnce).toBe(true);
		});

		it('should return a modified list of filters if a given filter group is selected', async () => {
			// For example, submitting a request with "datasetpublisher" filter selected
			const filterServiceStub = sinon.stub(filtersService, 'buildFilters');
			filterServiceStub.onFirstCall().resolves({ publisher: ['SAIL', 'PUBLIC HEALTH SCOTLAND'] });
			filterServiceStub.onSecondCall().resolves({ publisher: ['SAIL', 'PUBLIC HEALTH SCOTLAND', 'BARTS'] });

			let req = mockedRequest();
			let res = mockedResponse();

			req.query.tab = 'Datasets';
			req.query.search = '';
			req.query.datasetpublisher = '';

			const expectedResponse = {
				success: true,
				filters: {
					publisher: ['SAIL', 'PUBLIC HEALTH SCOTLAND', 'BARTS'],
					spatialv2: [],
				},
			};

			await searchFilterController.getSearchFilters(req, res);

			expect(res.status).toHaveBeenCalledWith(200);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(filterServiceStub.calledTwice).toBe(true);
		});

		it('should return 500 if an exception is thrown', async () => {
			// For example, submitting a request with "datasetpublisher" filter selected
			const err = 'TEST MESSAGE: error thrown';
			const filterServiceStub = sinon.stub(filtersService, 'buildFilters').rejects(new Error(err));

			let req = mockedRequest();
			let res = mockedResponse();

			req.query.tab = 'Datasets';
			req.query.search = '';

			const expectedResponse = {
				success: false,
				message: err,
			};

			await searchFilterController.getSearchFilters(req, res);

			expect(res.status).toHaveBeenCalledWith(500);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(filterServiceStub.calledOnce).toBe(true);
		});
	});
});
