import { model, Schema } from 'mongoose'

// this will be our data base's data structure 
const CollectionSchema = new Schema(
  {
    id: Number,
    name: String,
    description: String,
    imageLink: String,
    authors: [Number],
    // emailNotifications: Boolean, 
    relatedObjects: [{
        objectId: String,
        reason: String,
        objectType: String
    }],
    activeflag: String
  },
  { 
    collection: 'collections', //will be created when first posting 
    timestamps: true 
  }
);

export const Collections = model('Collections', CollectionSchema) 