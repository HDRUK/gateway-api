import { model, Schema } from 'mongoose';

const EventLogSchema = new Schema( 
  {
    userId: String,
    email: String,
    event: String, 
    provider: String,
    providerId: String,
    timestamp: Date,
  }
);

export const EventLogModel = model('eventlog', EventLogSchema)