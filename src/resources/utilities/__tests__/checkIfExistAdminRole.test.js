import teamV3Util from '../team.v3.util';
import { 
    mockMembersTrue,
    mockMembersFalse,
    mockRolesAuth, 
} from '../__mocks__/checkIfExistAdminRole.mock';
import HttpExceptions from '../../../exceptions/HttpExceptions';

describe('checkIfExistAdminRole test', () => {
    it('should return true', () => {
        let response = teamV3Util.checkIfExistAdminRole(mockMembersTrue, mockRolesAuth);
        expect(typeof response).toBe('boolean');
        expect(response).toBe(true);
    });

    it('should return an exception', () => {
        try {
            teamV3Util.checkIfExistAdminRole(mockMembersFalse, mockRolesAuth);
        } catch (error) {
            expect(error).toBeInstanceOf(HttpExceptions);
        }
    });
});