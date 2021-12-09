import sinon from 'sinon';

import dbHandler from '../../config/in-memory-db';
import { publisherStub } from '../__mocks__/publisherStub';
import constants from '../../resources/utilities/constants.util';
import { datasetSearchStub } from '../__mocks__/datasetSearchStub';
import datasetOnboardingService from '../../services/datasetonboarding.service';

beforeAll(async () => {
	await dbHandler.connect();
	await dbHandler.loadData({ tools: datasetSearchStub, publishers: publisherStub });
});

afterAll(async () => await dbHandler.closeDatabase());

describe('datasetOnboardingService', () => {
	const datasetonboardingService = new datasetOnboardingService();

	describe('getDatasetsByPublisherCounts', () => {
		it('only inReview dataset counts should be returned as an admin user', async () => {
			const publisherID = 'admin';

			const totalCounts = await datasetonboardingService.getDatasetsByPublisherCounts(publisherID);

			expect(Object.keys(totalCounts).length).toBe(1);
			expect(Object.keys(totalCounts)[0]).toBe(constants.datasetStatuses.INREVIEW);
		});

		it('all keys should be returned as a custodian user', async () => {
			const publisherID = 'TestPublisher';

			const totalCounts = await datasetonboardingService.getDatasetsByPublisherCounts(publisherID);

			expect(Object.keys(totalCounts).length).toBe(Object.keys(constants.datasetStatuses).length);
			expect(Object.keys(totalCounts).sort()).toEqual(Object.values(constants.datasetStatuses).sort());
		});
	});

	describe('getDatasetsByPublisher', () => {
		const statuses = Object.values(constants.datasetStatuses);

		test.each(statuses)('should only return datasets matching the given status', async activeflag => {
			const sortBy = 'latest';
			const sortDirection = 'desc';
			const status = activeflag;
			const publisherID = 'TestPublisher';
			const search = '';

			const [versionedDatasets] = await datasetonboardingService.getDatasetsByPublisher(status, publisherID, sortBy, sortDirection, search);

			expect([...new Set(versionedDatasets.map(dataset => dataset.activeflag))].length).toEqual(1);
		});

		it('should return all status types if no status is given', async () => {
			const sortBy = 'latest';
			const sortDirection = 'desc';
			const status = null;
			const publisherID = 'TestPublisher';
			const search = '';

			const expectedResponse = datasetSearchStub
				.filter(dataset => dataset.datasetv2.summary.publisher.identifier === 'TestPublisher')
				.map(dataset => dataset.activeflag);

			const [versionedDatasets] = await datasetonboardingService.getDatasetsByPublisher(status, publisherID, sortBy, sortDirection, search);

			expect([...new Set(versionedDatasets.map(dataset => dataset.activeflag))]).toEqual([...new Set(expectedResponse)]);
		});

		it('should return the correct count of filered results', async () => {
			const sortBy = 'latest';
			const sortDirection = 'desc';
			const status = 'inReview';
			const publisherID = 'admin';
			const search = '';

			const [_, count] = await datasetonboardingService.getDatasetsByPublisher(status, publisherID, sortBy, sortDirection, search);

			expect(count).toEqual(2);
		});

		it('should return results matching an appropriate search term', async () => {
			const sortBy = 'latest';
			const sortDirection = 'desc';
			const status = 'inReview';
			const publisherID = 'admin';
			const search = 'abstract3';

			const [_, count] = await datasetonboardingService.getDatasetsByPublisher(status, publisherID, sortBy, sortDirection, search);

			expect(count).toEqual(1);
		});
	});

	describe('createNewDatasetVersion', () => {
		it('should call createNewDatasetVersion if no PID exists', async () => {
			const publisherID = '615aee882414847722e46aa1';
			const pid = '';
			const currentVersionId = '';

			const initialDatasetVersionStub = sinon.stub(datasetonboardingService, 'initialDatasetVersion').returns([]);

			await datasetonboardingService.createNewDatasetVersion(publisherID, pid, currentVersionId);

			expect(initialDatasetVersionStub.calledOnce).toBe(true);
		});

		it('should call newVersionForExistingDataset if PID exists', async () => {
			const publisherID = '615aee882414847722e46aa1';
			const pid = 123;
			const currentVersionId = 456;

			const newVersionForExistingDatasetStub = sinon.stub(datasetonboardingService, 'newVersionForExistingDataset').returns([]);

			await datasetonboardingService.createNewDatasetVersion(publisherID, pid, currentVersionId);

			expect(newVersionForExistingDatasetStub.calledOnce).toBe(true);
		});
	});
});
