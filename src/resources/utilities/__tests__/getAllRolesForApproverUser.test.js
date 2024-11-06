import teamV3Util from '../team.v3.util';
import { mockTeam, mockTeamId, mockUserId, mockResponse } from '../__mocks__/getAllRolesForApproverUser.mock';

describe('getAllRolesForApproverUser test', () => {
    it('should return array', () => {
        let response = teamV3Util.getAllRolesForApproverUser(mockTeam, mockTeamId, mockUserId);
        expect(typeof response).toBe('object')
        expect(response).toEqual(expect.arrayContaining(mockResponse));
    });
});