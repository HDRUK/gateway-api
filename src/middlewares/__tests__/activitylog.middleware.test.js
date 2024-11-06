import sinon from 'sinon';

import { validateViewRequest, authoriseView } from '../activitylog.middleware';
import { datasetService } from '../../resources/dataset/dependency';
import { UserModel } from '../../resources/user/user.model';

afterEach(function () {
	sinon.restore();
});

describe('Testing the ActivityLog middleware', () => {
	const mockedRequest = () => {
		const req = {};
		return req;
	};

	const mockedResponse = () => {
		const res = {};
		res.status = jest.fn().mockReturnValue(res);
		res.json = jest.fn().mockReturnValue(res);
		return res;
	};

	describe('Testing the validateViewRequest middleware', () => {
		const expectedResponse = {
			success: false,
			message: 'You must provide a valid log category and array of version identifiers to retrieve corresponding logs',
		};

		it('Should return 400 when no versionIds are passed in request', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			req.body = { versionIds: [], type: 'dataset' };
			const nextFunction = jest.fn();

			validateViewRequest(req, res, nextFunction);

			expect(res.status).toHaveBeenCalledWith(400);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(nextFunction.mock.calls.length).toBe(0);
		});

		it('Should return 400 if activity log type "data_request" or "dataset"', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			req.body = { versionIds: [123, 456], type: 'notARealType' };
			const nextFunction = jest.fn();

			validateViewRequest(req, res, nextFunction);

			expect(res.status).toHaveBeenCalledWith(400);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(nextFunction.mock.calls.length).toBe(0);
		});

		it('Should invoke next() if conditions are satisfied', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			req.body = { versionIds: [123, 456], type: 'dataset' };
			const nextFunction = jest.fn();

			validateViewRequest(req, res, nextFunction);

			expect(nextFunction.mock.calls.length).toBe(1);
		});
	});
	describe('Testing the authoriseView middleware', () => {
		const expectedResponse = {
			success: false,
			message: 'You are not authorised to perform this action',
		};
		it('Should return a 401 error if the user is not authorised', async () => {
			let req = mockedRequest();
			let res = mockedResponse();
			req.body = { versionIds: ['xyz', 'abc'], type: 'dataset' };
			req.user = undefined;
			const nextFunction = jest.fn();

			let versionsStub = sinon.stub(datasetService, 'getDatasets').returns([
				{
					datasetv2: {
						identifier: 'abc',
						summary: {
							publisher: {
								identifier: 'pub1',
							},
						},
					},
				},
				{
					datasetv2: {
						identifier: 'xyz',
						summary: {
							publisher: {
								identifier: 'pub2',
							},
						},
					},
				},
			]);

			await authoriseView(req, res, nextFunction);

			expect(versionsStub.calledOnce).toBe(true);
			expect(res.status).toHaveBeenCalledWith(401);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(nextFunction.mock.calls.length).toBe(0);
		});
		it('Should invoke next() if the user is authorised against dataset(s)', async () => {
			let req = mockedRequest();
			let res = mockedResponse();
			req.body = { versionIds: ['xyz', 'abc'], type: 'dataset' };
			req.user = new UserModel({
				_id: '618a72fd5ec8f54772b7a17b',
				firstname: 'John',
				lastname: 'Smith',
				teams: [
					{
						publisher: { _id: 'fakeTeam', name: 'fakeTeam' },
						type: 'admin',
						members: [{ memberid: '618a72fd5ec8f54772b7a17b', roles: ['admin_dataset'] }],
					},
				],
			});
			const nextFunction = jest.fn();

			let versionsStub = sinon.stub(datasetService, 'getDatasets').returns([
				{
					datasetv2: {
						identifier: 'abc',
						summary: {
							publisher: {
								identifier: 'pub1',
							},
						},
					},
				},
				{
					datasetv2: {
						identifier: 'xyz',
						summary: {
							publisher: {
								identifier: 'pub2',
							},
						},
					},
				},
			]);

			await authoriseView(req, res, nextFunction);

			expect(versionsStub.calledOnce).toBe(true);
		});

		it('Should respond 401 if an error is thrown', async () => {
			let req = mockedRequest();
			let res = mockedResponse();
			req.body = { versionIds: ['xyz', 'abc'], type: 'dataset' };
			const nextFunction = jest.fn();

			let versionsStub = sinon.stub(datasetService, 'getDatasets').throws();

			let badCall = await authoriseView(req, res, nextFunction);

			try {
				badCall();
			} catch {
				expect(versionsStub.calledOnce).toBe(true);
				expect(nextFunction.mock.calls.length).toBe(0);
				expect(res.status).toHaveBeenCalledWith(401);
			}
		});
	});
});
