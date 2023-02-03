import teamV3Util from '../team.v3.util';
import { 
    mockArrayCheckRolesEmptyRole,
    mockArrayCheckRolesOneRole,
    mockArrayCheckRolesMultiRole,
    mockArrayCheckRolesManagerRole,
    mockTeam,
    mockUserId, 
} from '../__mocks__/checkUserRolesByTeam.mock';
import HttpExceptions from '../../../exceptions/HttpExceptions';

describe('checkUserRolesByTeam test', () => {
    it('should return true for no role request', () => {
        let response = teamV3Util.checkUserRolesByTeam(mockArrayCheckRolesEmptyRole, mockTeam, mockUserId);
        expect(typeof response).toBe('boolean');
        expect(response).toBe(true);
    });

    it('should return true for one role request', () => {
        let response = teamV3Util.checkUserRolesByTeam(mockArrayCheckRolesOneRole, mockTeam, mockUserId);
        expect(typeof response).toBe('boolean');
        expect(response).toBe(true);
    });
    
    it('should return true for multi role request', () => {
        let response = teamV3Util.checkUserRolesByTeam(mockArrayCheckRolesMultiRole, mockTeam, mockUserId);
        expect(typeof response).toBe('boolean');
        expect(response).toBe(true);
    });

    it('should return an exception for manager role', () => {
        try {
            teamV3Util.checkUserRolesByTeam(mockArrayCheckRolesManagerRole, mockTeam, mockUserId);
        } catch (error) {
            expect(error).toBeInstanceOf(HttpExceptions);
        }
    });
});