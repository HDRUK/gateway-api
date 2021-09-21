import { model, Schema } from 'mongoose';

import CohortClass from './cohort.entity';

const cohortSchema = new Schema(
	{
		id: Number,
		pid: String,
		type: String,
		name: String,
		activeflag: String,
		userId: Number,
		uploaders: [],
		publicflag: Boolean,
		version: Number,
		changeLog: String,
		updatedAt: Date,
		lastRefresh: Date,

		// fields from RQuest
		request_id: String,
		cohort: {},
		items: [],

		relatedObjects: [
			{
				objectId: String,
				reason: String,
				objectType: String,
				pid: String,
				user: String,
				updated: String,
				isLocked: Boolean,
			},
		],
	},
	{
		timestamps: true,
		toJSON: { virtuals: true },
		toObject: { virtuals: true },
	}
);

// Load entity class
cohortSchema.loadClass(CohortClass);

export const Cohort = model('Cohort', cohortSchema);
