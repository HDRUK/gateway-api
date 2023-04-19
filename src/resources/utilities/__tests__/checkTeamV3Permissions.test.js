import teamV3Util from '../team.v3.util';
import {
    mockRole,
    mockRoleManager,
    mockTeam,
    mockTeamEmpty,
    mockUserIdNotInList,
    mockUserIdInList,
    mockUserIdInListNoManager
} from '../__mocks__/checkTeamV3Permissions.mock';

describe("test for checkTeamV3Permissions", () => {
    it("checkTeamV3Permissions with userid in team should return true", () => {
        let response = teamV3Util.checkTeamV3Permissions(mockRole, mockTeam, mockUserIdInList);
        expect(response).toBe(true);
    });

    it("checkTeamV3Permissions with userid not in team should return true", () => {
        let response = teamV3Util.checkTeamV3Permissions(mockRole, mockTeam, mockUserIdInList);
        expect(response).toBe(true);
    });

    it("checkTeamV3Permissions for user without manager permisions should return false", () => {
        let response = teamV3Util.checkTeamV3Permissions(mockRoleManager, mockTeam, mockUserIdInListNoManager);
        expect(response).toBe(false);
    });

    it("checkTeamV3Permissions with empty team should return false", () => {
        let response = teamV3Util.checkTeamV3Permissions(mockRole, mockTeamEmpty, mockUserIdNotInList);
        expect(response).toBe(false);
    });
});