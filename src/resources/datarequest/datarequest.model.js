import { model, Schema } from 'mongoose';

import { WorkflowSchema } from '../workflow/workflow.model';
import constants from '../utilities/constants.util';
import DataRequestClass from './datarequest.entity';

const DataRequestSchema = new Schema(
	{
		majorVersion: { type: Number, default: 1 },
		userId: Number, // Main applicant
		authorIds: [Number],
		dataSetId: String,
		datasetIds: [{ type: String }],
		initialDatasetIds: [{ type: String }],
		datasetTitles: [{ type: String }],
		isCloneable: Boolean,
		projectId: String,
		presubmissionTopic: { type: Schema.Types.ObjectId, ref: 'Topics' },
		workflowId: { type: Schema.Types.ObjectId, ref: 'Workflow' },
		workflow: { type: WorkflowSchema },
		applicationStatus: {
			type: String,
			default: 'inProgress',
			enum: ['inProgress', 'submitted', 'inReview', 'approved', 'rejected', 'approved with conditions', 'withdrawn'],
		},
		applicationType: {
			type: String,
			default: constants.submissionTypes.INITIAL,
			enum: Object.values(constants.submissionTypes),
		},
		submissionDescription: {
			type: String,
		},
		archived: {
			Boolean,
			default: false,
		},
		applicationStatusDesc: String,
		schemaId: { type: Schema.Types.ObjectId, ref: 'data_request_schemas' },
		jsonSchema: {
			type: Object,
			default: {},
		},
		questionAnswers: {
			type: Object,
			default: {},
		},
		questionSetStatus: {
			type: Object,
			default: {},
		},
		initialQuestionAnswers: {
			type: Object,
			default: {},
		},
		aboutApplication: {
			type: Object,
			default: {},
		},
		dateSubmitted: {
			type: Date,
		},
		dateFinalStatus: {
			type: Date,
		},
		dateReviewStart: {
			type: Date,
		},
		publisher: {
			type: String,
			default: '',
		},
		formType: {
			type: String,
			default: constants.formTypes.Extended5Safe,
			enum: Object.values(constants.formTypes),
		},
		files: [
			{
				name: { type: String },
				size: { type: Number },
				description: { type: String },
				status: { type: String },
				fileId: { type: String },
				error: { type: String, default: '' },
				owner: {
					type: Schema.Types.ObjectId,
					ref: 'User',
				},
			},
		],
		amendmentIterations: [
			{
				dateCreated: { type: Date },
				createdBy: { type: Schema.Types.ObjectId, ref: 'User' },
				dateReturned: { type: Date },
				returnedBy: { type: Schema.Types.ObjectId, ref: 'User' },
				dateSubmitted: { type: Date },
				submittedBy: { type: Schema.Types.ObjectId, ref: 'User' },
				questionAnswers: { type: Object, default: {} },
			},
		],
		originId: { type: Schema.Types.ObjectId, ref: 'data_request' },
		versionTree: { type: Object, default: {} },
		isShared: { type: Boolean, default: false },
		publishedForm: { type: Boolean, default: false },
	},
	{
		timestamps: true,
		toJSON: { virtuals: true },
		toObject: { virtuals: true },
	}
);

DataRequestSchema.virtual('datasets', {
	ref: 'Data',
	foreignField: 'datasetid',
	localField: 'datasetIds',
	justOne: false,
});

DataRequestSchema.virtual('dataset', {
	ref: 'Data',
	foreignField: 'datasetid',
	localField: 'dataSetId',
	justOne: true,
});

DataRequestSchema.virtual('mainApplicant', {
	ref: 'User',
	foreignField: 'id',
	localField: 'userId',
	justOne: true,
});

DataRequestSchema.virtual('publisherObj', {
	ref: 'Publisher',
	foreignField: 'name',
	localField: 'publisher',
	justOne: true,
});

DataRequestSchema.virtual('authors', {
	ref: 'User',
	foreignField: 'id',
	localField: 'authorIds',
});

DataRequestSchema.virtual('initialDatasets', {
	ref: 'Data',
	foreignField: 'datasetid',
	localField: 'initialDatasetIds',
	justOne: false,
});

// Load entity class
DataRequestSchema.loadClass(DataRequestClass);

export const DataRequestModel = model('data_request', DataRequestSchema);
