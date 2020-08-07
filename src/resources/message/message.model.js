import { model, Schema } from 'mongoose'

const MessageSchema = new Schema({
  messageID: Number,
  messageTo: Number,
  messageObjectID: Number,
  messageType: String,
  messageSent: Date,
  isRead: String,
  messageDescription: String,
  topic: { type: Schema.Types.ObjectId, ref: 'Topic'}
})

export const MessagesModel = model('Messages', MessageSchema);