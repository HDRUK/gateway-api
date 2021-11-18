import { authoriseUserForPublisher, validateSearchParameters } from '../datasetonboarding.middleware';
import { UserModel } from '../../resources/user/user.model';
import constants from '../../resources/utilities/constants.util';

describe('Testing the datasetonboarding middleware', () => {
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

	describe('Testing the authoriseUserForPublisher middleware', () => {
		it('Should invoke next() if user on admin team', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.user = new UserModel({
				_id: '618a72fd5ec8f54772b7a17b',
				firstname: 'John',
				lastname: 'Smith',
				teams: [
					{
						publisher: { _id: 'fakeTeam', name: 'fakeTeam' },
						type: 'admin',
					},
				],
			});

			authoriseUserForPublisher(req, res, nextFunction);

			expect(nextFunction.mock.calls.length).toBe(1);
		});

		it('Should invoke next() if user on publisher team', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.params = {
				publisherID: 'fakeTeam',
			};

			req.user = new UserModel({
				_id: '618a72fd5ec8f54772b7a17b',
				firstname: 'John',
				lastname: 'Smith',
				teams: [
					{
						publisher: { _id: 'fakeTeam', name: 'fakeTeam' },
						type: 'publisher',
					},
				],
			});

			authoriseUserForPublisher(req, res, nextFunction);

			expect(nextFunction.mock.calls.length).toBe(1);
		});

		it('Should return a 401 error is user is unauthorised', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			const expectedResponse = {
				success: false,
				message: 'You are not authorised to view these datasets',
			};

			req.params = {
				publisherID: 'fakeTeam',
			};

			req.user = new UserModel({
				_id: '618a72fd5ec8f54772b7a17b',
				firstname: 'John',
				lastname: 'Smith',
				teams: [
					{
						publisher: { _id: 'wrongFakeTeam', name: 'fakeTeam' },
						type: 'publisher',
					},
				],
			});

			authoriseUserForPublisher(req, res, nextFunction);

			expect(res.status).toHaveBeenCalledWith(401);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(nextFunction.mock.calls.length).toBe(0);
		});
	});

	describe('Testing the validateSearchParameters middleware', () => {
		it('Should invoke next() if correct query parameters are supplied', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.query = {
				search: '',
				datasetIndex: 0,
				maxResults: 10,
				datasetSort: 'recentActivityAsc',
				status: 'inReview',
			};

			validateSearchParameters(req, res, nextFunction);

			expect(nextFunction.mock.calls.length).toBe(1);
		});

		it('Should invoke next() for each correct datasetSort option', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			const sortOptions = Object.keys(constants.datasetSortOptions);

			sortOptions.forEach(sortOption => {
				req.query = {
					search: '',
					datasetIndex: 0,
					maxResults: 10,
					datasetSort: sortOption,
					status: 'inReview',
				};
				validateSearchParameters(req, res, nextFunction);
			});

			expect(nextFunction.mock.calls.length).toBe(sortOptions.length);
		});

		it('Should invoke next() for each correct status option', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			const statuses = ['active', 'inReview', 'draft', 'rejected', 'archive'];

			statuses.forEach(status => {
				req.query = {
					search: '',
					datasetIndex: 0,
					maxResults: 10,
					datasetSort: 'recentActivityAsc',
					status: status,
				};
				validateSearchParameters(req, res, nextFunction);
			});

			expect(nextFunction.mock.calls.length).toBe(statuses.length);
		});

		it('Should return a 500 error for an unallowed sort option', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.query = {
				search: '',
				datasetIndex: 0,
				maxResults: 10,
				datasetSort: 'unallowedSortOption',
				status: 'inReview',
			};

			validateSearchParameters(req, res, nextFunction);

			expect(res.status).toHaveBeenCalledWith(500);
			expect(nextFunction.mock.calls.length).toBe(0);
		});

		it('Should return a 500 error for an unallowed status option', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.query = {
				search: '',
				datasetIndex: 0,
				maxResults: 10,
				datasetSort: 'recentActivityAsc',
				status: 'unallowedStatusOption',
			};

			validateSearchParameters(req, res, nextFunction);

			expect(res.status).toHaveBeenCalledWith(500);
			expect(nextFunction.mock.calls.length).toBe(0);
		});

		it('Should return a 500 error for a missing status parameter', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.query = {
				search: '',
				datasetIndex: 0,
				maxResults: 10,
				datasetSort: 'recentActivityAsc',
			};

			validateSearchParameters(req, res, nextFunction);

			expect(res.status).toHaveBeenCalledWith(500);
			expect(nextFunction.mock.calls.length).toBe(0);
		});

		it('Should remove illegal characters from the search string', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.query = {
				search: 'unallowed-/?@"{}()characters',
				datasetIndex: 0,
				maxResults: 10,
				datasetSort: 'recentActivityAsc',
				status: 'inReview',
			};

			validateSearchParameters(req, res, nextFunction);

			expect(req.query.search).toEqual('unallowedcharacters');
			expect(nextFunction.mock.calls.length).toBe(1);
		});
	});
});