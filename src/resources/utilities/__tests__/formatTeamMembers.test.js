import teamV3Util from '../team.v3.util';
import { mockTeam, mockResponse } from '../__mocks__/formatTeamMembers.mock';

describe("formatTeamMembers test", () => {
    it("should return empty object for empty input", () => {
        let response = teamV3Util.formatTeamMembers({});
        expect(response).toMatchObject({});
    });

    it("should return response as expected", () => {
        let response = teamV3Util.formatTeamMembers(mockTeam);
        expect(response).toMatchObject(mockResponse);
    });
});