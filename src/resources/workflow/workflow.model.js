import { model, Schema } from 'mongoose';

export const minReviewers = (val) => {
  return val.length > 0;
}

export const minSteps = (val) => {
  return val.length > 0;
}

export const minSections = (val) => {
  return val.length > 0;
}

const StepSchema = new Schema({
  stepName: { type: String, required: true },
  reviewers: { type: [{ type : Schema.Types.ObjectId, ref: 'User' }], validate:[minReviewers, 'There must be at least one reviewer per phase'] },
  sections: { type: [String], validate:[minSections, 'There must be at least one section assigned to a phase'] },
  deadline: { type: Number, required: true }, // Number of days from step starting that a deadline is reached
  reminderOffset: { type: Number, required: true, default: 3 }, // Number of days before deadline that SLAs are triggered by Camunda
  // Items below not required for step definition
  active: { type: Boolean, default: false },
  completed: { type: Boolean, default: false },
  startDateTime: { type: Date },
  endDateTime: { type: Date },
  recommendations: [{
    reviewer: { type : Schema.Types.ObjectId, ref: 'User' },
    approved: { type: Boolean },
    comments: { type: String },
    createdDate: { type: Date }
  }]
});

export const WorkflowSchema = new Schema({
  id: { type: Number, required: true },
  workflowName: { type: String, required: true },
  version: Number,
  publisher: { type : Schema.Types.ObjectId, ref: 'Publisher', required: true },
  steps: { type: [ StepSchema ], validate:[minSteps, 'There must be at least one phase in a workflow']},
  active: { 
      type: Boolean, 
      default: true 
  },
  createdBy: { type : Schema.Types.ObjectId, ref: 'User', required: true },
  updatedBy: { type : Schema.Types.ObjectId, ref: 'User' }
}, {
  timestamps: true,
  toJSON:     { virtuals: true },
  toObject:   { virtuals: true }
});

WorkflowSchema.virtual('applications', {
  ref: 'data_request',
  foreignField: 'workflowId',
  localField: '_id'
});

export const WorkflowModel = model('Workflow', WorkflowSchema);