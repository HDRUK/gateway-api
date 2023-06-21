import teamV3Util from '../team.v3.util';
import {
    mockUser,
    mockRole,
    mockRoleEmpty
} from '../__mocks__/checkIfAdmin.mock';

describe("test checkIfAdmin", () => {
    it("should return true", () => {
        let response = teamV3Util.checkIfAdmin(mockUser, mockRole);
        expect(response).toBe(true);
    });

    it("should return false", () => {
        let response = teamV3Util.checkIfAdmin(mockUser, mockRoleEmpty);
        expect(response).toBe(false);
    });
});