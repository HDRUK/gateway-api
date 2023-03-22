import dbHandler from '../../../config/in-memory-db';
import {mockCollections} from '../__mocks__/collections.data';

const {getCollaboratorsCollections} = require('../user.service');


beforeAll(async () => {
	await dbHandler.connect();
	await dbHandler.loadData({ collections: mockCollections });
});

afterAll(async () => { 
    await dbHandler.clearDatabase();
    await dbHandler.closeDatabase();
});

describe('getCollaboratorsCollections tests', () => {
    it('should return values', async () => {
        const currentUserId = 8470291714590257;
        const filter = currentUserId ? { authors: currentUserId } : {};

        const result = await getCollaboratorsCollections(filter, currentUserId);
        expect(result.length).toBe(1);
        expect(typeof result).toBe('object');
    });
});