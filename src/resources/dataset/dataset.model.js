import { model, Schema } from 'mongoose';

import DatasetClass from './dataset.entity';

//DO NOT DELETE publisher and team model below
import { PublisherModel } from '../publisher/publisher.model';
import { TeamModel } from '../team/team.model';
import { DataRequestModel } from '../datarequest/datarequest.model';

const datasetSchema = new Schema(
	{
		id: Number,
		name: String,
		description: String,
		source: String,
		is5Safes: Boolean,
		hasTechnicalDetails: Boolean,
		resultsInsights: String,
		link: String,
		type: String,
		categories: {
			category: { type: String },
		},
		license: String,
		authors: [Number],
		tags: {
			features: [String],
			topics: [String],
		},
		activeflag: String,
		updatedon: Date,
		counter: Number,
		discourseTopicId: Number,
		relatedObjects: [
			{
				objectId: String,
				reason: String,
				pid: String,
				objectType: String,
				user: String,
				updated: String,
			},
		],
		uploader: Number,
		datasetid: String,
		pid: String,
		datasetVersion: String,
		datasetfields: {
			publisher: String,
			geographicCoverage: [String],
			physicalSampleAvailability: [String],
			abstract: String,
			releaseDate: String,
			accessRequestDuration: String,
			conformsTo: String,
			accessRights: String,
			jurisdiction: String,
			datasetStartDate: String,
			datasetEndDate: String,
			statisticalPopulation: String,
			ageBand: String,
			contactPoint: String,
			periodicity: String,
			populationSize: String,
			metadataquality: {},
			datautility: {},
			metadataschema: {},
			technicaldetails: [],
			versionLinks: [],
			phenotypes: [],
		},
		datasetv2: {},
		isLatestVersion: Boolean
	},
	{
		timestamps: true,
		collection: 'tools',
		toJSON: { virtuals: true },
		toObject: { virtuals: true },
	}
);

// Virtuals
datasetSchema.virtual('publisher', {
	ref: 'Publisher',
	foreignField: 'name',
	localField: 'datasetfields.publisher',
	justOne: true,
});

datasetSchema.virtual('reviews', {
	ref: 'Reviews',
	foreignField: 'reviewerID',
	localField: 'id',
	justOne: false,
});

datasetSchema.virtual('tools', {
	ref: 'Data',
	foreignField: 'authors',
	localField: 'id',
	justOne: false,
});

datasetSchema.virtual('submittedDataAccessRequests', {
	ref: 'data_request',
	foreignField: 'datasetIds',
	localField: 'datasetid',
	count: true,
	match: {
		applicationStatus: { $in: ['submitted', 'approved', 'inReview', 'rejected', 'approved with conditions'] },
	},
	justOne: true,
});

// Pre hook query middleware
datasetSchema.pre('find', function() {
    this.where({type: 'dataset'});
});

datasetSchema.pre('findOne', function() {
    this.where({type: 'dataset'});
});

// Load entity class
datasetSchema.loadClass(DatasetClass);

export const Dataset = model('Dataset', datasetSchema, 'tools');