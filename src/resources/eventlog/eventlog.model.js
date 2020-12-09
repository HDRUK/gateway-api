import { model, Schema } from 'mongoose';

const EventLogSchema = new Schema( 
  {
    userId: String,
    event: String, 
    timestamp: Date,
  }
);

export const EventLogModel = model('eventlog', EventLogSchema)