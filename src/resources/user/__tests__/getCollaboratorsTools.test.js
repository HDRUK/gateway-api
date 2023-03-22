import dbHandler from '../../../config/in-memory-db';
import {mockTools} from '../__mocks__/tools.data';

const {getCollaboratorsTools} = require('../user.service');


beforeAll(async () => {
	await dbHandler.connect();
	await dbHandler.loadData({ tools: mockTools });
});

afterAll(async () => { 
    await dbHandler.clearDatabase();
    await dbHandler.closeDatabase();
});

describe('getCollaboratorsTools tests', () => {
    it('should return values', async () => {
        const currentUserId = 8470291714590257;
        const filter = currentUserId ? { uploaders: currentUserId } : {};

        const result = await getCollaboratorsTools(filter, currentUserId);
        expect(typeof result).toBe('object');
    });

    it('should return values', async () => {
        const currentUserId = null;
        const filter = currentUserId ? { uploaders: currentUserId } : {};

        const result = await getCollaboratorsTools(filter, currentUserId);
        expect(result.length > 0).toBe(true);
        expect(typeof result).toBe('object');
    });
});