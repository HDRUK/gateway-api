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

	addCohort(body = {}) {
		const document = {
			type: 'cohort',
			name: body.description,
			activeflag: 'draft',
			userId: body.user_id,
			uploaders: [body.user_id],
			request_id: body.request_id,
			cohort: body.cohort,
			items: body.items,
			relatedObjects: body.relatedObjects,
		};
		return this.cohortRepository.addCohort(document);
	}
}
