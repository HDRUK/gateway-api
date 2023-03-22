const {getUniqueCollaborators} = require('../user.service');


describe('getUniqueCollaborators tests', () => {
    it('should return a unique collaborator like map', () => {
        let collaborators = [39025048818527176,917335621870613];
        const result = getUniqueCollaborators(collaborators);

        expect(result instanceof Map).toBe(true);
    });

    it('should return a specific number of unique collaborators', () => {
        let collaborators = [39025048818527176,917335621870613];
        const result = getUniqueCollaborators(collaborators);

        expect(result.size).toBe(2);
    });

    it('should return empty', () => {
        let collaborators = [];
        const result = getUniqueCollaborators(collaborators);

        expect(result.size).toBe(0);
    });

    it('should return values', () => {
        let collaborators = [39025048818527176,917335621870613];
        const result = getUniqueCollaborators(collaborators);

        expect(result.has(39025048818527176)).toBe(true);
        expect(result.has(917335621870613)).toBe(true);
    })


    it('should return correct keys and values', () => {
        let collaborators = [39025048818527176,917335621870613];
        const result = getUniqueCollaborators(collaborators);

        const mapValues = [...result.values()];
        const typeMapValues = typeof mapValues;

        const mapKeys = [...result.keys()];
        const typeMapKeys = typeof mapKeys;

        expect(typeMapValues).toBe('object');
        expect(typeMapKeys).toBe('object');
    })
});