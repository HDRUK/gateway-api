import { model, Schema } from 'mongoose';

// this will be our data base's data structure
const CollectionSchema = new Schema(
	{
		id: Number,
		name: String,
		description: String,
		updatedon: Date,
		imageLink: String,
		authors: [Number],
		// emailNotifications: Boolean,
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
		activeflag: String,
		publicflag: Boolean,
		keywords: [String],
	},
	{
		collection: 'collections', //will be created when first posting
		timestamps: true,
	}
);

export const Collections = model('Collections', CollectionSchema);
