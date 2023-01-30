import teamV3Util from '../team.v3.util';
import { mockUserUpdateRoles, mockUserUpdateRolesFalse, mockAllowedRoles } from '../__mocks__/checkAllowNewRoles.mock';
import HttpExceptions from '../../../exceptions/HttpExceptions';

describe('checkAllowNewRoles test', () => {
    it('should return true', () => {
        let response = teamV3Util.checkAllowNewRoles(mockUserUpdateRoles, mockAllowedRoles);
        expect(typeof response).toBe('boolean');
        expect(response).toBe(true);
    });

    it('should return an exception', () => {
        try {
            teamV3Util.checkAllowNewRoles(mockUserUpdateRolesFalse, mockAllowedRoles);
        } catch (error) {
            expect(error).toBeInstanceOf(HttpExceptions);
        }
    });
});