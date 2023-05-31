import teamV3Util from '../team.v3.util';
import {
    currUserId,
    permission,
    mockTeam,
    mockUsers,
} from '../__mocks__/checkUserAuthorization.mock';

describe("test checkUserAuthorization", () => {
    it("should return true", () => {
        let response = teamV3Util.checkUserAuthorization(currUserId, permission, mockTeam, mockUsers);
        expect(response).toBe(true);
    });
});