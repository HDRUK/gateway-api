import sinon from 'sinon';

import dbHandler from '../../config/in-memory-db';
import { Data } from '../../resources/tool/data.model';
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
			const page = 1;
			const limit = 10;
			const sortBy = 'latest';
			const sortDirection = 'desc';
			const statusArray = [activeflag];
			const publisherID = 'TestPublisher';
			const search = '';

			const [versionedDatasets] = await datasetonboardingService.getDatasetsByPublisher(
				statusArray,
				publisherID,
				page,
				limit,
				sortBy,
				sortDirection,
				search
			);

			expect([...new Set(versionedDatasets.map(dataset => dataset.activeflag))].length).toEqual(1);
		});

		it('should return the correct count of filered results', async () => {
			const page = 1;
			const limit = 10;
			const sortBy = 'latest';
			const sortDirection = 'desc';
			const statusArray = ['inReview'];
			const publisherID = 'admin';
			const search = '';

			const [_, count] = await datasetonboardingService.getDatasetsByPublisher(
				statusArray,
				publisherID,
				page,
				limit,
				sortBy,
				sortDirection,
				search
			);

			expect(count).toEqual(3);
		});

		it('should return results matching an appropriate search term', async () => {
			const page = 1;
			const limit = 10;
			const sortBy = 'latest';
			const sortDirection = 'desc';
			const statusArray = ['inReview'];
			const publisherID = 'admin';
			const search = 'abstract3';

			const [_, count] = await datasetonboardingService.getDatasetsByPublisher(
				statusArray,
				publisherID,
				page,
				limit,
				sortBy,
				sortDirection,
				search
			);

			expect(count).toEqual(1);
		});

		it('should allow for pagintation', async () => {
			const page = 1;
			const limit = 1;
			const sortBy = 'latest';
			const sortDirection = 'desc';
			const statusArray = ['inReview'];
			const publisherID = 'admin';
			const search = '';

			const [versionedDatasets, count] = await datasetonboardingService.getDatasetsByPublisher(
				statusArray,
				publisherID,
				page,
				limit,
				sortBy,
				sortDirection,
				search
			);

			expect(count).toEqual(3);
			expect(versionedDatasets.length).toEqual(1);
		});

		test.each(Object.keys(constants.datasetSortOptions))(
			'Each sort option should lead to correctly sorted output arrays for ascending direction',
			async sortOption => {
				const page = 1;
				const limit = 10;
				const sortBy = sortOption;
				const sortDirection = 'asc';
				const statusArray = Object.values(constants.datasetStatuses);
				const publisherID = 'admin';
				const search = 'test';

				const [versionedDatasets, _] = await datasetonboardingService.getDatasetsByPublisher(
					statusArray,
					publisherID,
					page,
					limit,
					sortBy,
					sortDirection,
					search
				);

				if (sortOption === 'latest') {
					let arr = versionedDatasets.map(dataset => dataset.timestamps.updated);
					expect(arr[0]).toBeLessThan(arr[1]);
				}

				if (sortOption === 'alphabetic') {
					let arr = versionedDatasets.map(dataset => dataset.name);
					let expectedResult = [arr[0].name, arr[1].name].sort();
					expect([arr[0].name, arr[1].name]).toEqual(expectedResult);
				}

				if (sortOption === 'metadata') {
					let arr = versionedDatasets.map(dataset => dataset.metadataQualityScore);
					expect(arr[0]).toBeLessThan(arr[1]);
				}

				if (sortOption === 'recentlyadded') {
					let arr = versionedDatasets.map(dataset => dataset.timestamps.created);
					expect(arr[0]).toBeLessThan(arr[1]);
				}

				if (sortOption === 'popularity') {
					let arr = versionedDatasets.map(dataset => dataset.counter);
					expect(arr[0]).toBeLessThan(arr[1]);
				}

				if (sortOption === 'relevance') {
					let arr = versionedDatasets.map(dataset => dataset.pid);
					expect(arr[0]).toEqual('pid2');
				}
			}
		);

		test.each(Object.keys(constants.datasetSortOptions))(
			'Each sort option should lead to correctly sorted output arrays for descending direction',
			async sortOption => {
				const page = 1;
				const limit = 10;
				const sortBy = sortOption;
				const sortDirection = 'desc';
				const statusArray = Object.values(constants.datasetStatuses);
				const publisherID = 'admin';
				const search = 'test';

				const [versionedDatasets, _] = await datasetonboardingService.getDatasetsByPublisher(
					statusArray,
					publisherID,
					page,
					limit,
					sortBy,
					sortDirection,
					search
				);

				if (sortOption === 'latest') {
					let arr = versionedDatasets.map(dataset => dataset.timestamps.updated);
					expect(arr[1]).toBeLessThan(arr[0]);
				}

				if (sortOption === 'alphabetic') {
					let arr = versionedDatasets.map(dataset => dataset.name);
					let expectedResult = [arr[0].name, arr[1].name].sort().reverse();
					expect([arr[1].name, arr[0].name]).toEqual(expectedResult);
				}

				if (sortOption === 'metadata') {
					let arr = versionedDatasets.map(dataset => dataset.metadataQualityScore);
					expect(arr[1]).toBeLessThan(arr[0]);
				}

				if (sortOption === 'recentlyadded') {
					let arr = versionedDatasets.map(dataset => dataset.timestamps.created);
					expect(arr[1]).toBeLessThan(arr[0]);
				}

				if (sortOption === 'popularity') {
					let arr = versionedDatasets.map(dataset => dataset.counter);
					expect(arr[1]).toBeLessThan(arr[0]);
				}

				if (sortOption === 'relevance') {
					let arr = versionedDatasets.map(dataset => dataset.pid);
					expect(arr[0]).toEqual('pid1');
				}
			}
		);
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

	describe('duplicateDataset', () => {
		it('should not create duplicates of the pid and datasetid fields', async () => {
			const dataset = datasetSearchStub[7];

			await datasetonboardingService.duplicateDataset(dataset._id);

			const allDatasets = await Data.find({}).sort({ createdAt: -1 });

			const duplicatedDataset = allDatasets[0];

			expect(duplicatedDataset._id).not.toEqual(dataset._id);
			expect(duplicatedDataset.datasetid).not.toEqual(dataset.datasetid);
			expect(duplicatedDataset.pid).not.toEqual(dataset.pid);
			expect(duplicatedDataset.questionAnswers).not.toEqual(dataset.questionAnswers);
			expect(duplicatedDataset.name).toEqual(dataset.name + '-duplicate');
			expect(duplicatedDataset.activeflag).toEqual('draft');
			expect(duplicatedDataset.datasetVersion).toEqual('1.0.0');
		});
	});
});
