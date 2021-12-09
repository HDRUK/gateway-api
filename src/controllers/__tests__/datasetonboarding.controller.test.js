import sinon from 'sinon';

import datasetOnboardingController from '../datasetonboarding.controller';
import datasetOnboardingService from '../../services/datasetonboarding.service';

afterEach(function () {
	sinon.restore();
});

describe('datasetOnboardingController', () => {
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

	const datasetonboardingService = new datasetOnboardingService();
	const datasetonboardingController = new datasetOnboardingController(datasetonboardingService);

	describe('getDatasetsByPublisher', () => {
		it('should return a correctly formatted JSON response', async () => {
			let serviceStub1 = sinon.stub(datasetonboardingService, 'getDatasetsByPublisherCounts').returns({ inReview: 100 });
			let serviceStub2 = sinon.stub(datasetonboardingService, 'getDatasetsByPublisher').returns([[], 100]);

			let req = mockedRequest();
			let res = mockedResponse();

			req.query.status = 'inReview';
			req.params.publisherID = 'testPublisher';

			const expectedResponse = {
				success: true,
				data: {
					publisherTotals: {
						inReview: 100,
					},
					results: {
						status: 'inReview',
						total: 100,
						listOfDatasets: [],
					},
				},
			};

			await datasetonboardingController.getDatasetsByPublisher(req, res);

			expect(res.status).toHaveBeenCalledWith(200);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(serviceStub1.calledOnce).toBe(true);
			expect(serviceStub2.calledOnce).toBe(true);
		});

		it('should return status=all if no status param given in initial request', async () => {
			let serviceStub1 = sinon.stub(datasetonboardingService, 'getDatasetsByPublisherCounts').returns({ inReview: 100 });
			let serviceStub2 = sinon.stub(datasetonboardingService, 'getDatasetsByPublisher').returns([[], 100]);

			let req = mockedRequest();
			let res = mockedResponse();

			req.params.publisherID = 'testPublisher';

			const expectedResponse = {
				success: true,
				data: {
					publisherTotals: {
						inReview: 100,
					},
					results: {
						status: 'all',
						total: 100,
						listOfDatasets: [],
					},
				},
			};

			await datasetonboardingController.getDatasetsByPublisher(req, res);

			expect(res.status).toHaveBeenCalledWith(200);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(serviceStub1.calledOnce).toBe(true);
			expect(serviceStub2.calledOnce).toBe(true);
		});

		it('should return a 500 error if a service function throws an error', async () => {
			const errMessage = 'random error message';
			const error = new Error(errMessage);
			let serviceStub1 = sinon.stub(datasetonboardingService, 'getDatasetsByPublisherCounts').throws(error);
			let serviceStub2 = sinon.stub(datasetonboardingService, 'getDatasetsByPublisher');

			let req = mockedRequest();
			let res = mockedResponse();

			const expectedResponse = {
				success: false,
				message: errMessage,
			};

			await datasetonboardingController.getDatasetsByPublisher(req, res);

			expect(res.status).toHaveBeenCalledWith(500);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(serviceStub1.calledOnce).toBe(true);
			expect(serviceStub2.calledOnce).toBe(false);
		});
	});

	describe('getDatasetVersion', () => {
		it('should return a correctly formatted JSON response', async () => {
			let serviceStub1 = sinon.stub(datasetonboardingService, 'getDatasetVersion').returns({});
			let serviceStub2 = sinon.stub(datasetonboardingService, 'getAssociatedVersions').returns([]);

			let req = mockedRequest();
			let res = mockedResponse();

			req.params.id = '123';

			const expectedResponse = {
				success: true,
				data: { dataset: {} },
				listOfDatasets: [],
			};

			await datasetonboardingController.getDatasetVersion(req, res);

			expect(res.status).toHaveBeenCalledWith(200);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(serviceStub1.calledOnce).toBe(true);
			expect(serviceStub2.calledOnce).toBe(true);
		});

		it('should return 404 if no dataset ID is given in the request', async () => {
			let serviceStub1 = sinon.stub(datasetonboardingService, 'getDatasetVersion').returns({});
			let serviceStub2 = sinon.stub(datasetonboardingService, 'getAssociatedVersions').returns([]);

			let req = mockedRequest();
			let res = mockedResponse();

			const expectedResponse = {
				success: false,
				message: 'A valid dataset ID was not supplied',
			};

			await datasetonboardingController.getDatasetVersion(req, res);

			expect(res.status).toHaveBeenCalledWith(404);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(serviceStub1.callCount).toBe(0);
			expect(serviceStub2.callCount).toBe(0);
		});

		it('should return 500 if a service function throws an error', async () => {
			const errMessage = 'random error message';
			const error = new Error(errMessage);
			let serviceStub1 = sinon.stub(datasetonboardingService, 'getDatasetVersion').throws(error);
			let serviceStub2 = sinon.stub(datasetonboardingService, 'getAssociatedVersions');

			let req = mockedRequest();
			let res = mockedResponse();

			req.params.id = '123';

			const expectedResponse = {
				success: false,
				message: errMessage,
			};

			await datasetonboardingController.getDatasetVersion(req, res);

			expect(res.status).toHaveBeenCalledWith(500);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(serviceStub1.callCount).toBe(1);
			expect(serviceStub2.callCount).toBe(0);
		});
	});

	describe('createNewDatasetVersion', () => {
		it('should return a correctly formatted JSON response', async () => {
			let serviceStub1 = sinon.stub(datasetonboardingService, 'getDatasetVersion').returns({});
			let serviceStub2 = sinon.stub(datasetonboardingService, 'getAssociatedVersions').returns([]);

			let req = mockedRequest();
			let res = mockedResponse();

			req.params.id = '123';

			const expectedResponse = {
				success: true,
				data: { dataset: {} },
				listOfDatasets: [],
			};

			await datasetonboardingController.getDatasetVersion(req, res);

			expect(res.status).toHaveBeenCalledWith(200);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(serviceStub1.calledOnce).toBe(true);
			expect(serviceStub2.calledOnce).toBe(true);
		});
	});
});
