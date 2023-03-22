import mongoose from 'mongoose';

import Repository from '../base/repository';
import { PublisherModel } from './publisher.model';
import { Dataset } from '../dataset/dataset.model';
import { DataRequestModel } from '../datarequest/datarequest.model';

export default class PublisherRepository extends Repository {
	constructor() {
		super(PublisherModel);
		this.publisherModel = PublisherModel;
	}

	getPublisher(id, options = {}) {
		let query = {};

		if (mongoose.Types.ObjectId.isValid(id)) {
			query = { _id: id };
		} else {
			query = { name: id };
		}

		return this.findOne(query, options);
	}

	getPublishersAndIds() {
		return PublisherModel.find({}, { _id: 1, name: 1 });
	}

	getPublisherDatasets(id) {
		return Dataset.find({
			type: 'dataset',
			activeflag: 'active',
			'datasetfields.publisher': id,
		})
			.populate('publisher')
			.select('datasetid name description datasetfields.abstract _id datasetfields.publisher datasetfields.contactPoint publisher');
	}

	getPublisherDataAccessRequests(query) {
		return DataRequestModel.find(query)
			.select('-jsonSchema -files')
			.sort({ updatedAt: -1 })
			.populate([
				{
					path: 'datasets dataset mainApplicant',
				},
				{
					path: 'publisherObj',
					populate: {
						path: 'team',
						populate: {
							path: 'users',
							select: 'firstname lastname',
						},
					},
				},
				{
					path: 'workflow.steps.reviewers',
					select: 'firstname lastname',
				},
			])
			.lean();
	}

	async updatePublisher(query, options) {
		return this.updateByQuery(query, options);
	}
}
