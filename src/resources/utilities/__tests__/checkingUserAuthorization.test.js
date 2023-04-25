import teamV3Util from '../team.v3.util';
import { mockArrayRolesAllow, mockArrayRolesNotAllow, mockArrayCurrentUserRoles } from '../__mocks__/checkingUserAuthorization.mock';
import HttpExceptions from '../../../exceptions/HttpExceptions';

describe('checkingUserAuthorization test', () => {
    it('should return true', () => {
        let response = teamV3Util.checkingUserAuthorization(mockArrayRolesAllow, mockArrayCurrentUserRoles);
        expect(typeof response).toBe('boolean');
        expect(response).toBe(true);
    });

    it('should return an exception', () => {
        try {
            teamV3Util.checkingUserAuthorization(mockArrayRolesNotAllow, mockArrayCurrentUserRoles);
        } catch (error) {
            expect(error).toBeInstanceOf(HttpExceptions);
        }
    });
});