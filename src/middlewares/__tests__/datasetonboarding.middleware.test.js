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

			req.params = {
				publisherID: 'fakeTeam',
			};

			req.query = {
				search: '',
				sortBy: 'latest',
				sortDirection: 'asc',
				status: 'inReview',
			};

			validateSearchParameters(req, res, nextFunction);

			expect(nextFunction.mock.calls.length).toBe(1);
		});

		it('Should invoke next() for each correct datasetSort option', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			const sortOptions = Object.values(constants.datasetSortOptions);

			sortOptions.forEach(sortOption => {
				req.params = {
					publisherID: 'fakeTeam',
				};

				req.query = {
					search: '',
					sortBy: sortOption,
					sortDirection: 'asc',
					status: 'active',
				};
				validateSearchParameters(req, res, nextFunction);
			});

			expect(nextFunction.mock.calls.length).toBe(sortOptions.length);
		});

		it('Should invoke next() for each correct status option', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			const statuses = Object.values(constants.datasetStatuses);

			statuses.forEach(status => {
				req.params = {
					publisherID: 'fakeTeam',
				};

				req.query = {
					search: '',
					sortBy: 'latest',
					sortDirection: 'asc',
					status: status,
				};
				validateSearchParameters(req, res, nextFunction);
			});

			expect(nextFunction.mock.calls.length).toBe(statuses.length);
		});

		it('Should return a 401 if and admin team member provides a status which is not "inReview"', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			const expectedResponse = {
				success: false,
				message: 'Only inReview datasets can be accessed by the admin team',
			};

			req.params = {
				publisherID: 'admin',
			};

			req.query = {
				search: '',
				sortBy: 'latest',
				sortDirection: 'asc',
				status: 'active',
			};

			validateSearchParameters(req, res, nextFunction);

			expect(res.status).toHaveBeenCalledWith(401);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(nextFunction.mock.calls.length).toBe(0);
		});

		it('Should return a 500 error for an unallowed sort option', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.params = {
				publisherID: 'fakeTeam',
			};

			req.query = {
				search: '',
				sortBy: 'unallowedSortOption',
				sortDirection: 'asc',
				status: 'inReview',
			};

			validateSearchParameters(req, res, nextFunction);

			expect(res.status).toHaveBeenCalledWith(500);
			expect(nextFunction.mock.calls.length).toBe(0);
		});

		it('Should return a 500 error for an unallowed status parameter', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.params = {
				publisherID: 'fakeTeam',
			};

			req.query = {
				search: '',
				sortBy: 'latest',
				sortDirection: 'asc',
				status: 'notARealStatus',
			};

			validateSearchParameters(req, res, nextFunction);

			expect(res.status).toHaveBeenCalledWith(500);
			expect(nextFunction.mock.calls.length).toBe(0);
		});

		it('Should remove illegal characters from the search string', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.params = {
				publisherID: 'fakeTeam',
			};

			req.query = {
				search: 'unallowed-/?@"{}()characters',
				sortBy: 'latest',
				sortDirection: 'asc',
				status: 'inReview',
			};

			validateSearchParameters(req, res, nextFunction);

			expect(req.query.search).toEqual('unallowedcharacters');
			expect(nextFunction.mock.calls.length).toBe(1);
		});

		it('Should return a 500 error for an unallowed sortDirection option', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			req.params = {
				publisherID: 'fakeTeam',
			};

			req.query = {
				search: 'unallowed-/?@"{}()characters',
				sortBy: 'latest',
				sortDirection: 'unallowedSortDirection',
				status: 'inReview',
			};

			validateSearchParameters(req, res, nextFunction);

			expect(res.status).toHaveBeenCalledWith(500);
			expect(nextFunction.mock.calls.length).toBe(0);
		});

		it('Should return a 500 error for the popularity sort option with a status which does not equal active', () => {
			let req = mockedRequest();
			let res = mockedResponse();
			const nextFunction = jest.fn();

			const expectedResponse = {
				success: false,
				message: `Sorting by popularity is only available for active datasets [status=active]`,
			};

			req.params = {
				publisherID: 'fakeTeam',
			};

			req.query = {
				search: '',
				sortBy: 'popularity',
				sortDirection: 'asc',
				status: 'inReview',
			};

			validateSearchParameters(req, res, nextFunction);

			expect(res.status).toHaveBeenCalledWith(500);
			expect(res.json).toHaveBeenCalledWith(expectedResponse);
			expect(nextFunction.mock.calls.length).toBe(0);
		});
	});
});
