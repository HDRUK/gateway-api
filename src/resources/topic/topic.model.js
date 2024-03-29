import { model, Schema } from 'mongoose';

const TopicSchema = new Schema(
	{
		title: {
			type: String,
			default: '',
			trim: true,
		},
		subTitle: {
			type: String,
			default: '',
			trim: true,
		},
		messageType: {
			type: String,
			enum: ['DAR_Message', 'DAR_Notes_Applicant', 'DAR_Notes_Custodian'],
		},
		recipients: [
			{
				type: Schema.Types.ObjectId,
				ref: 'User',
			},
		],
		linkedDataAccessApplication: {
			type: Schema.Types.ObjectId,
			ref: 'data_request',
		},
		status: {
			type: String,
			enum: ['active', 'closed'],
			default: 'active',
		},
		createdDate: {
			type: Date,
			default: Date.now,
		},
		exiryDate: {
			type: Date,
		},
		createdBy: {
			type: Schema.Types.ObjectId,
			ref: 'User',
		},
		relatedObjectIds: [
			{
				type: Schema.Types.ObjectId,
				ref: 'Data',
			},
		],
		isDeleted: {
			type: Boolean,
			default: false,
		},
		is5Safes: {
			type: Boolean,
			default: false,
		},
		unreadMessages: {
			type: Number,
			default: 0,
		},
		lastUnreadMessage: {
			type: Date,
		},
		datasets: [
			{
				datasetId: {
					type: String,
				},
				publisher: {
					type: String,
				},
			},
		],
		tags: [
			{
				id: {
					type: String,
				},
				datasetId: {
					type: String,
				},
				name: {
					type: String,
				},
				publisher: {
					type: String,
				},
			},
		],
	},
	{
		toJSON: { virtuals: true },
		toObject: { virtuals: true },
	}
);

// Virtual Populate  - Topic to bring back messages if topics querried messages without persisting it to the db, it doesnt slow down the query - populate in route
TopicSchema.virtual('topicMessages', {
	ref: 'Messages',
	foreignField: 'topic',
	localField: '_id',
});

TopicSchema.pre(/^find/, function (next) {
	this.populate([
		{
			path: 'createdBy',
			select: 'firstname lastname',
		},
		{
			path: 'topicMessages',
			select: 'messageDescription firstMessage createdDate isRead _id readBy userType',
			options: { sort: '-createdDate' },
			populate: {
				path: 'createdBy',
				model: 'User',
				select: 'firstname lastname',
			},
		},
	]);

	next();
});

export const TopicModel = model('Topics', TopicSchema);
