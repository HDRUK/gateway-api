//const mongoose = require('mongoose');
const dbHandler = require('../../../../config/in-memory-db');

const dataRequest = require('../../__mocks__/datarequest');
//const amendmentController = require('../amendment.controller');
//const amendmentModel = require('../amendment.model');

/**
 * Connect to a new in-memory database before running any tests.
 */
beforeAll(async () => { 
	await dbHandler.connect();
	await dbHandler.loadData({ 'data_requests': dataRequest });
});

/**
 * Revert to initial test data after every test.
 */
afterEach(async () => {
	await dbHandler.clearDatabase()
	await dbHandler.loadData({ 'data_requests': dataRequest });
});

/**
 * Remove and close the db and server.
 */
afterAll(async () => await dbHandler.closeDatabase());

// Placeholder for integration tests
describe('', () => {
	test('', () => {
		expect(1).toBe(1);
	});
});