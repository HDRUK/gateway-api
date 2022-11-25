import { model, Schema } from 'mongoose';

const ReviewsSchema = new Schema(
	{
		reviewID: Number,
		toolID: Number,
		reviewerID: Number,
		rating: Number,
		projectName: String,
		review: String,
		activeflag: String,
		date: Date,
		replierID: Number,
		reply: String,
		replydate: Date,
	},
	{
		collection: 'reviews',
		timestamps: true,
	}
);

export const Reviews = model('Reviews', ReviewsSchema);
