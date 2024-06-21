import teamV3Util from '../team.v3.util';
import { mockUserRoles, mockRolesAcceptedByRoles, mockResponse } from '../__mocks__/listOfRolesAllowed.mock';

describe('listOfRolesAllowed test', () => {
    it('should return array', () => {
        let response = teamV3Util.listOfRolesAllowed(mockUserRoles, mockRolesAcceptedByRoles);
        expect(typeof response).toBe('object')
        expect(response).toEqual(expect.arrayContaining(mockResponse));
    });
});