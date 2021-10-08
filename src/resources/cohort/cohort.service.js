import { v4 as uuidv4 } from 'uuid';
import { Data } from '../tool/data.model';

export default class CohortService {
	constructor(cohortRepository) {
		this.cohortRepository = cohortRepository;
	}

	getCohort(id, query = {}, options = {}) {
		// Protect for no id passed
		if (!id) return;

		query = { ...query, id };
		return this.cohortRepository.getCohort(query, options);
	}

	getCohorts(query = {}) {
		return this.cohortRepository.getCohorts(query);
	}

	async addCohort(body = {}) {
		// 1. Generate uuid for Cohort PID
		let uuid = '';
		while (uuid === '') {
			uuid = uuidv4();
			if ((await this.cohortRepository.getCohorts({ pid: uuid })).length > 0) uuid = '';
		}
		// 2. Generate uniqueId for Cohort so we can differentiate between versions
		let uniqueId = '';
		while (uniqueId === '') {
			uniqueId = parseInt(Math.random().toString().replace('0.', ''));
			if ((await this.cohortRepository.getCohorts({ id: uniqueId }).length) > 0) uniqueId = '';
		}

		// 3. Extract PIDs from cohort object so we can build up related objects
		let datasetIdentifiersPromises = await body.cohort.input.collections.map(async collection => {
			let dataset = await Data.findOne({ pid: collection.external_id, activeflag: 'active' }, { datasetid: 1 }).lean();
			return { pid: collection.external_id, datasetId: dataset.datasetid };
		});
		let datasetIdentifiers = await Promise.all(datasetIdentifiersPromises);
		let relatedObjects = [];
		let datasetPids = [];
		datasetIdentifiers.forEach(datasetIdentifier => {
			datasetPids.push(datasetIdentifier.pid);
			relatedObjects.push({
				objectType: 'dataset',
				pid: datasetIdentifier.pid,
				objectId: datasetIdentifier.datasetId,
				isLocked: true,
			});
		});

		// 4. Extract filter criteria used in query
		let filterCriteria = [];
		body.cohort.input.cohorts.forEach(cohort => {
			cohort.groups.forEach(group => {
				group.rules.forEach(rule => {
					filterCriteria.push(rule.value);
				});
			});
		});

		// 5. Build document object and save to DB
		const document = {
			id: uniqueId,
			pid: uuid,
			type: 'cohort',
			name: body.description,
			activeflag: 'draft',
			userId: body.user_id,
			uploaders: [parseInt(body.user_id)],
			updatedAt: Date.now(),
			lastRefresh: Date.now(),
			request_id: body.request_id,
			cohort: body.cohort,
			items: body.items,
			rquestRelatedObjects: body.relatedObjects,
			datasetPids,
			filterCriteria,
			relatedObjects,
			description: '',
			publicflag: true,
		};
		return this.cohortRepository.addCohort(document);
	}
}
