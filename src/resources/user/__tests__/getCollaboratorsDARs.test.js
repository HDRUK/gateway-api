import dbHandler from '../../../config/in-memory-db';
import {mockDars} from '../__mocks__/dars.data';

const {getCollaboratorsDARs} = require('../user.service');


beforeAll(async () => {
	await dbHandler.connect();
	await dbHandler.loadData({ data_requests: mockDars });
});

afterAll(async () => { 
    await dbHandler.clearDatabase();
    await dbHandler.closeDatabase();
});

describe('getCollaboratorsDARs tests', () => {
    it('should return values', async () => {
        const currentUserId = 8470291714590257;
        const filter = currentUserId ? { $or: [{ userId: currentUserId }, { authorIds: currentUserId }] } : {};

        const result = await getCollaboratorsDARs(filter, currentUserId);
        expect(result.length > 0).toBe(true);
        expect(typeof result).toBe('object');
    });

    it('should return values', async () => {
        const currentUserId = null;
        const filter = currentUserId ? { $or: [{ userId: currentUserId }, { authorIds: currentUserId }] } : {};

        const result = await getCollaboratorsDARs(filter, currentUserId);
        expect(result.length > 0).toBe(true);
        expect(typeof result).toBe('object');
    });
});