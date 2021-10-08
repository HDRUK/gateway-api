import { resultLimit } from '../src/config/middleware';

describe('resultLimit', () => {
	const nextFunction = jest.fn();

	const mockResponse = () => {
		const res = {};
		res.status = jest.fn().mockReturnValue(res);
		res.json = jest.fn().mockReturnValue(res);
		return res;
	};

	const allowedLimit = 100;

	it('should return a 400 response code with the correct reason when the requested limit is non numeric', () => {
		const expectedResponse = {
			success: false,
			message: 'The result limit parameter provided must be a numeric value.',
		};

		const req = { query: { limit: 'one hundred' } };
		const res = mockResponse();

		resultLimit(req, res, nextFunction, allowedLimit);

		expect(res.status).toHaveBeenCalledWith(400);
		expect(res.json).toHaveBeenCalledWith(expectedResponse);
	});

	it('should return a 400 response code with the correct reason when the maximum allowed limit is exceeded', () => {
		const expectedResponse = {
			success: false,
			message: `Maximum request limit exceeded.  You may only request up to a maximum of ${allowedLimit} records per page.  Please use the page query parameter to request further data.`,
		};

		const req = { query: { limit: 101 } };
		const res = mockResponse();

		resultLimit(req, res, nextFunction, allowedLimit);

		expect(res.status).toHaveBeenCalledWith(400);
		expect(res.json).toHaveBeenCalledWith(expectedResponse);
	});

	it('should invoke the next function when no request limit is provided', () => {
		const req = {};
		const res = mockResponse();

		resultLimit(req, res, nextFunction, allowedLimit);

		expect(res.status.mock.calls.length).toBe(0);
		expect(res.json.mock.calls.length).toBe(0);
		expect(nextFunction.mock.calls.length).toBe(1);
	});

	it('should invoke the next function when the requested limit is valid', () => {
		const req = { query: { limit: 100 } };
		const res = mockResponse();

		resultLimit(req, res, nextFunction, allowedLimit);

		expect(res.status.mock.calls.length).toBe(0);
		expect(res.json.mock.calls.length).toBe(0);
		expect(nextFunction.mock.calls.length).toBe(1);
	});
});
