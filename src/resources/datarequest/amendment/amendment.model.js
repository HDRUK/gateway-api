import { model, Schema } from 'mongoose';

const AmendmentSchema = new Schema({
  questionSetId: String,
  requested: Boolean,
  reason: String,
  requestedBy: String,
  requestedByUser: { type : Schema.Types.ObjectId, ref: 'User' },
  dateRequested: Date,
  answer: { type: Schema.Types.Mixed },
  updatedBy: String,
  updatedByUser: { type : Schema.Types.ObjectId, ref: 'User' },
  dateUpdated: Date,
});

export const AmendmentModel = model('amendment', AmendmentSchema)
