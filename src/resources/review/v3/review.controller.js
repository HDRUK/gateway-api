import { Reviews } from '../review.model';
import { storeNotificationMessages } from './notification';
import { sendEmailNotifications } from './email';

class ReviewController {
    constructor() {}

    async getReviews(req, res) {
        const reviewId = parseInt(req.params.reviewId) || '';

        const data = await this.getDataReviews(reviewId);

        return res.status(200).json({
            'success': true,
            data
        });
    }

    async updateStateReviews(req, res) {
        const reviewId = parseInt(req.params.reviewId);
        const { activeflag } = req.body;

        if (!activeflag) {
            process.stdout.write(`ReviewController.updateReviews : activeflag missing`);
            throw new Error(`An error occurred : activeflag missing`);
        }
        const statusReview = activeflag === 'approve' ? 'active' : activeflag;

        const review = await this.updateStateDataReviews({ reviewID: reviewId }, { activeflag: statusReview });

        await storeNotificationMessages(review);
        // Send email notififcation of approval to authors and admins who have opted in
		await sendEmailNotifications(review, activeflag);

        return res.status(200).json({
            'success': true
        });
    }

    async updateStateDataReviews(filter, update) {
        try {
            return await Reviews.findOneAndUpdate(filter, update, {
                new: true
            });
        } catch (err) {
            process.stdout.write(`ReviewController.updateDataReviews : ${err.message}`);
            throw new Error(`An error occurred : ${err.message}`);
        }
    }

    async getDataReviews(reviewId) {
        try {
            const pipeline = this.reviewAggregatePipeline(reviewId);
            const statement = Reviews.aggregate(pipeline);
            return await statement.exec();
        } catch (err) {
            process.stdout.write(`ReviewController.getDataReviews : ${err.message}`);
            throw new Error(`An error occurred : ${err.message}`);
        }
    }

    reviewAggregatePipeline(reviewId) {
        let query = [];

        if (reviewId) {
            query.push({ $match: { 'reviewID': reviewId } });
        }
        query.push({ "$lookup": { "from": "tools", "localField": "reviewerID", "foreignField": "id", "as": "person" } });
        query.push({ "$lookup": { "from": "tools", "localField": "toolID", "foreignField": "id", "as": "tool" } });

        return query;
    }

}

module.exports = new ReviewController();
