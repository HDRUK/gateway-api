import { model, Schema } from 'mongoose';
import { WorkflowSchema } from '../workflow/workflow.model'; 

const DataRequestSchema = new Schema({
  version: Number,
  userId: Number, // Main applicant
  authorIds: [Number],
  dataSetId: String,
  datasetIds: [{ type: String}],
  projectId: String,
  workflowId: { type : Schema.Types.ObjectId, ref: 'Workflow' },
  workflow: { type: WorkflowSchema },
  applicationStatus: {
    type: String,
    default: 'inProgress',
    enum: ['inProgress' , 'submitted', 'inReview', 'approved', 'rejected', 'approved with conditions', 'withdrawn']
  },
  archived: { 
    Boolean, 
    default: false 
  },
  applicationStatusDesc : String,
  jsonSchema: {
    type: String,
    default: "{}"
  },
  questionAnswers: {
    type: String,
    default: "{}"
  },
  aboutApplication: {
    type: Object,
    default: {}
  },
  dateSubmitted: {
    type: Date
  },
  dateFinalStatus: {
    type: Date
  },
  dateReviewStart: {
    type: Date
  },
  publisher: {
    type: String,
    default: ""
  },
  files: [{ 
    name: { type: String },
    size: { type: Number },
    description: { type: String },
    status: { type: String },
    fileId: { type: String },
    error: { type: String, default: '' },
    owner: {
      type: Schema.Types.ObjectId,
      ref: 'User' 
    }
  }],
  amendmentIterations: [{
    dateCreated: { type: Date },
    createdBy: { type : Schema.Types.ObjectId, ref: 'User' },
    dateReturned: { type: Date },
    returnedBy: { type : Schema.Types.ObjectId, ref: 'User' },
    dateSubmitted: { type: Date },
    submittedBy: { type : Schema.Types.ObjectId, ref: 'User' },
    questionAnswers: { type: Object, default: {} }
  }],
}, {
    timestamps: true,
    toJSON:     { virtuals: true },
    toObject:   { virtuals: true }
});

DataRequestSchema.virtual('datasets', {
  ref: 'Data',
  foreignField: 'datasetid',
  localField: 'datasetIds',
  justOne: false
});

DataRequestSchema.virtual('dataset', {
  ref: 'Data',
  foreignField: 'datasetid',
  localField: 'dataSetId',
  justOne: true
});

DataRequestSchema.virtual('mainApplicant', {
  ref: 'User',
  foreignField: 'id',
  localField: 'userId',
  justOne: true
});

DataRequestSchema.virtual('publisherObj', {
  ref: 'Publisher',
  foreignField: 'name',
  localField: 'publisher',
  justOne: true
});

DataRequestSchema.virtual('authors', {
  ref: 'User',
  foreignField: 'id',
  localField: 'authorIds'
});

export const DataRequestModel = model('data_request', DataRequestSchema)
