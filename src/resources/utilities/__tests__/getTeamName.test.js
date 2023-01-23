import teamV3Util from '../team.v3.util';
import { mockTeamWithPublisher, mockTeamWithoutPublisher } from '../__mocks__/getTeamName.mock';

describe('getTeamName test', () => {
    it('should return a string who contain the publisher name', () => {
        let response = teamV3Util.getTeamName(mockTeamWithPublisher);
        expect(typeof response).toBe('string')
        expect(response).toContain('ALLIANCE > Test40');
    });

    it('should return a string who contain a generic publisher name', () => {
        let response = teamV3Util.getTeamName(mockTeamWithoutPublisher);
        expect(typeof response).toBe('string')
        expect(response).toContain('No team name');
    });
});