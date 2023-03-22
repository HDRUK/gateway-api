import sinon from 'sinon';

import { dataUseRegisterService } from '../../resources/dataUseRegister/dependency';
import { validateUpdateRequest, validateUploadRequest, authorizeUpdate, authorizeUpload } from '../dataUseRegister.middleware';

afterEach(function () {
	sinon.restore();
});

describe('Testing the dataUserRegister middleware', () => {
	const mockedRequest = () => {
		const req = {};
		req.params = jest.fn().mockReturnValue(req);
		req.body = jest.fn().mockReturnValue(req);
		return req;
	};

	const mockedResponse = () => {
		const res = {};
		res.status = jest.fn().mockReturnValue(res);
		res.json = jest.fn().mockReturnValue(res);
		return res;
	};

	describe('Testing the validateUpdateRequest middleware', () => {
		it('it should invoke next() if a valid ID is passed in the request', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.params.id = 'mockID';

			validateUpdateRequest(req, res, nextFunction);

			expect(nextFunction.mock.calls.length).toBe(1);
		});

		it('it should return the appropriate 400 error if no ID is given in the request', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			validateUpdateRequest(req, res, nextFunction);

			const expectedResponse = {
				success: false,
				message: 'You must provide a data user register identifier',
			};

			expect(nextFunction.mock.calls.length).toBe(0);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(res.status).toHaveBeenCalledWith(400);
		});
	});

	describe('Testing the validateUploadRequest middleware', () => {
		it('it should invoke next() if a valid teamID and dataUses array are supplied in the request', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.body.teamId = 'testID';
			req.body.dataUses = ['dataUse'];

			validateUploadRequest(req, res, nextFunction);

			expect(nextFunction.mock.calls.length).toBe(1);
		});

		it('it should give an appropriate error if no teamID is given in the request', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.body.dataUses = ['dataUse'];

			validateUploadRequest(req, res, nextFunction);

			const expectedResponse = {
				success: false,
				message: 'You must provide the custodian team identifier to associate the data uses to',
			};

			expect(nextFunction.mock.calls.length).toBe(0);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(res.status).toHaveBeenCalledWith(400);
		});

		it('it should give an appropriate error if no dataUses are given in the request', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.body.teamId = 'testID';

			validateUploadRequest(req, res, nextFunction);

			const expectedResponse = {
				success: false,
				message: 'You must provide data uses to upload',
			};

			expect(nextFunction.mock.calls.length).toBe(0);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(res.status).toHaveBeenCalledWith(400);
		});
	});

	describe('Testing the authorizeUpdate middleware', () => {
		it('it should return a 404 if no data use can be found for a given ID', async () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			sinon.stub(dataUseRegisterService, 'getDataUseRegister');

			await authorizeUpdate(req, res, nextFunction);

			const expectedResponse = {
				success: false,
				message: 'The requested data use register entry could not be found',
			};

			expect(nextFunction.mock.calls.length).toBe(0);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(res.status).toHaveBeenCalledWith(404);
		});

		it('it should return a 401 if user not authorised to update DUR', async () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.user = {
				_id: 'testUser',
				teams: [
					{
						publisher: { _id: { equals: jest.fn() } },
						type: 'NOT_ADMIN_TEAM',
						members: [{ memberid: 'testUser', roles: 'admin_data_use' }],
					},
				],
			};

			sinon
				.stub(dataUseRegisterService, 'getDataUseRegister')
				.returns({ publisher: 'testPublisher', gatewayApplicants: ['anotherTestUser'] });

			await authorizeUpdate(req, res, nextFunction);

			const expectedResponse = {
				success: false,
				message: 'You are not authorised to perform this action',
			};

			expect(nextFunction.mock.calls.length).toBe(0);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(res.status).toHaveBeenCalledWith(401);
		});

		it('it should return a 401 if the projectID text is mismatched', async () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.body = {
				projectIdText: 'notAMatch',
			};

			req.user = {
				_id: 'testUser',
				teams: [{ publisher: { _id: 'testPublisher' }, type: 'admin', members: [{ memberid: 'testUser', roles: 'admin_data_use' }] }],
			};

			sinon.stub(dataUseRegisterService, 'getDataUseRegister').returns({ projectIdText: 'testIdText', gatewayApplicants: ['testUser'] });

			await authorizeUpdate(req, res, nextFunction);

			const expectedResponse = {
				success: false,
				message: 'You are not authorised to update the project ID of an automatic data use register',
			};

			expect(nextFunction.mock.calls.length).toBe(0);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(res.status).toHaveBeenCalledWith(401);
		});

		it('it should return a 401 if the datasetTitles is mismatched', async () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.body = {
				datasetTitles: 'notAMatch',
			};

			req.user = {
				_id: 'testUser',
				teams: [{ publisher: { _id: 'testPublisher' }, type: 'admin', members: [{ memberid: 'testUser', roles: 'admin_data_use' }] }],
			};

			sinon.stub(dataUseRegisterService, 'getDataUseRegister').returns({ datasetTitles: 'datasetTitles', gatewayApplicants: ['testUser'] });

			await authorizeUpdate(req, res, nextFunction);

			const expectedResponse = {
				success: false,
				message: 'You are not authorised to update the datasets of an automatic data use register',
			};

			expect(nextFunction.mock.calls.length).toBe(0);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(res.status).toHaveBeenCalledWith(401);
		});

		it('it should invoke next if all conditions are satisfied', async () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.body = {
				datasetTitles: 'match',
				projectIdText: 'match',
			};

			req.user = {
				_id: 'testUser',
				teams: [{ publisher: { _id: 'testPublisher' }, type: 'admin', members: [{ memberid: 'testUser', roles: 'admin_data_use' }] }],
			};

			sinon
				.stub(dataUseRegisterService, 'getDataUseRegister')
				.returns({ datasetTitles: 'match', projectIdText: 'match', gatewayApplicants: ['testUser'] });

			await authorizeUpdate(req, res, nextFunction);

			expect(nextFunction.mock.calls.length).toBe(1);
		});
	});

	describe('Testing the authorizeUpload middleware', () => {
		it('It should return 401 if user is not authorised', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.user = {
				_id: 'testUser',
				teams: [
					{ publisher: { _id: { equals: jest.fn() } }, type: 'NotAdmin', members: [{ memberid: 'testUser', roles: 'admin_data_use' }] },
				],
			};

			authorizeUpload(req, res, nextFunction);

			const expectedResponse = {
				success: false,
				message: 'You are not authorised to perform this action',
			};

			expect(nextFunction.mock.calls.length).toBe(0);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(res.status).toHaveBeenCalledWith(401);
		});

		it('It should invoke next() if user is authorised', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.user = {
				_id: 'testUser',
				teams: [{ publisher: { _id: 'testPublisher' }, type: 'admin', members: [{ memberid: 'testUser', roles: 'admin_data_use' }] }],
			};

			authorizeUpload(req, res, nextFunction);

			expect(nextFunction.mock.calls.length).toBe(1);
		});
	});
});
