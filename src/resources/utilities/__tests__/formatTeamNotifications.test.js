import teamV3Util from '../team.v3.util';
import {
    mockTeam,
    mockResponse,
} from '../__mocks__/formatTeamNotifications.mock';

describe("test formatTeamNotifications", () => {
    it("should return return response", () => {
        let response = teamV3Util.formatTeamNotifications(mockTeam);
        expect(typeof response).toBe('object')
        expect(response).toEqual(expect.arrayContaining(mockResponse));
    });
});