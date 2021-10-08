import { catchLoginErrorAndRedirect, loginAndSignToken } from '../utils';

describe('Utilities', () => {
	describe('catchErrorAndRedirect middleware', () => {
		it('should be a function', () => {
			expect(typeof catchLoginErrorAndRedirect).toBe('function');
		});

		it('should call next once when ( req.auth.err || !req.auth.user ) == false', () => {
			let res = {};
			let req = {
				auth: {
					user: 'someUser',
					err: null,
				},
			};
			const next = jest.fn();

			catchLoginErrorAndRedirect(req, res, next);

			// assert
			expect(next.mock.calls.length).toBe(1);
		});

		it('should not call next when ( req.auth.err || !req.auth.user ) == true', () => {
			let res = {};
			res.status = jest.fn().mockReturnValue(res);
			res.redirect = jest.fn().mockReturnValue(res);
			let req = {
				auth: {
					user: {},
					err: 'someErr',
				},
				param: {
					returnpage: 'somePage',
				},
			};
			const next = jest.fn();

			catchLoginErrorAndRedirect(req, res, next);

			// assert
			expect(next.mock.calls.length).toBe(0);
			expect(res.status.mock.calls.length).toBe(1);
			expect(res.redirect.mock.calls.length).toBe(1);
		});
	});

	describe('loginAndSignToken middleware', () => {
		it('should be a function', () => {
			expect(typeof loginAndSignToken).toBe('function');
		});

		it('should call res.login once', () => {
			let res = {};
			res.status = jest.fn().mockReturnValue(res);
			res.redirect = jest.fn().mockReturnValue(res);
			let req = {
				auth: {
					user: 'someUser',
				},
			};
			req.login = jest.fn().mockReturnValue(req);
			const next = jest.fn();

			loginAndSignToken(req, res, next);

			// assert
			expect(req.login.mock.calls.length).toBe(1);
		});
	});
});
