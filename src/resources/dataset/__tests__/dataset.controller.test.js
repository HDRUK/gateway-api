import sinon from 'sinon';
import faker from 'faker';

import DatasetController from '../dataset.controller';
import DatasetService from '../dataset.service';

describe('DatasetController', function () {
	beforeAll(() => {
		console.log = sinon.stub();
		console.error = sinon.stub();
	});

	describe('getDataset', function () {
		let req, res, status, json, datasetService, datasetController;

		beforeEach(() => {
			status = sinon.stub();
			json = sinon.spy();
			res = { json, status };
			status.returns(res);
			datasetService = new DatasetService();
		});

		it('should return a dataset that matches the id param', async function () {
			req = { params: { id: faker.random.number({ min: 1, max: 999999999 }) } };
			const stubValue = {
				id: req.params.id,
			};
			const serviceStub = sinon.stub(datasetService, 'getDataset').returns(stubValue);
			datasetController = new DatasetController(datasetService);
			await datasetController.getDataset(req, res);

			expect(serviceStub.calledOnce).toBe(true);
			expect(status.calledWith(200)).toBe(true);
			expect(json.calledWith({ success: true, ...stubValue })).toBe(true);
		});

		it('should return a bad request response if no dataset id is provided', async function () {
			req = { params: {} };

			const serviceStub = sinon.stub(datasetService, 'getDataset').returns({});
			datasetController = new DatasetController(datasetService);
			await datasetController.getDataset(req, res);

			expect(serviceStub.notCalled).toBe(true);
			expect(status.calledWith(400)).toBe(true);
			expect(json.calledWith({ success: false, message: 'You must provide a dataset identifier' })).toBe(true);
		});

		it('should return a not found response if no dataset could be found for the id provided', async function () {
			req = { params: { id: faker.random.number({ min: 1, max: 999999999 }) } };

			const serviceStub = sinon.stub(datasetService, 'getDataset').returns(null);
			datasetController = new DatasetController(datasetService);
			await datasetController.getDataset(req, res);

			expect(serviceStub.calledOnce).toBe(true);
			expect(status.calledWith(404)).toBe(true);
			expect(json.calledWith({ success: false, message: 'A dataset could not be found with the provided id' })).toBe(true);
		});

		it('should return a server error if an unexpected exception occurs', async function () {
			req = { params: { id: faker.random.number({ min: 1, max: 999999999 }) } };

			const error = new Error('A server error occurred');
			const serviceStub = sinon.stub(datasetService, 'getDataset').throws(error);
			datasetController = new DatasetController(datasetService);
			await datasetController.getDataset(req, res);

			expect(serviceStub.calledOnce).toBe(true);
			expect(status.calledWith(500)).toBe(true);
			expect(json.calledWith({ success: false, message: 'A server error occurred, please try again' })).toBe(true);
		});
	});

	describe('getDatasets', function () {
		let req, res, status, json, datasetService, datasetController;
        req = { params: {} };

		beforeEach(() => {
			status = sinon.stub();
			json = sinon.spy();
			res = { json, status };
			status.returns(res);
			datasetService = new DatasetService();
		});

		it('should return an array of datasets', async function () {
			const stubValue = [
				{
					id: faker.random.number({ min: 1, max: 999999999 }),
				},
				{
					id: faker.random.number({ min: 1, max: 999999999 }),
				},
			];
			const serviceStub = sinon.stub(datasetService, 'getDatasets').returns(stubValue);
			datasetController = new DatasetController(datasetService);
			await datasetController.getDatasets(req, res);

			expect(serviceStub.calledOnce).toBe(true);
			expect(status.calledWith(200)).toBe(true);
			expect(json.calledWith({ success: true, datasets: stubValue })).toBe(true);
		});

		it('should return a server error if an unexpected exception occurs', async function () {
			const error = new Error('A server error occurred');
			const serviceStub = sinon.stub(datasetService, 'getDatasets').throws(error);
			datasetController = new DatasetController(datasetService);
			await datasetController.getDatasets(req, res);

			expect(serviceStub.calledOnce).toBe(true);
			expect(status.calledWith(500)).toBe(true);
			expect(json.calledWith({ success: false, message: 'A server error occurred, please try again' })).toBe(true);
		});
	});
});
