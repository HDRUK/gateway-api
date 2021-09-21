import Repository from '../base/repository';
import { Cohort } from './cohort.model';

export default class CohortRepository extends Repository {
	constructor() {
		super(Cohort);
		this.cohort = Cohort;
	}

	async getCohort(query, options) {
		return this.findOne(query, options);
	}

	async getCohorts(query) {
		const options = { lean: true };
		return this.find(query, options);
	}

	async addCohort(body) {
		return this.create(body);
	}
}
