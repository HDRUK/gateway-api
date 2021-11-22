import { authUtils } from '../index';

describe('Auth utilities', () => {
	describe('loginAndSignToken', () => {
		it('should be a function', () => {
			expect(typeof authUtils.loginAndSignToken).toBe('function');
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

			authUtils.loginAndSignToken(req, res, next);

			// assert
			expect(req.login.mock.calls.length).toBe(1);
		});
	});
});
