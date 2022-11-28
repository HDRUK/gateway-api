import { Reviews } from '../review.model';

class ReviewController {
    constructor() {}

    async getReviews(req, res) {
        const idString = parseInt(req.params.reviewId) || '';

        const data = await this.statementExecutionDB(idString);

        return res.status(200).json({
            'success': true,
            data
        });
    }

    async statementExecutionDB(idString) {
        try {
            const pipeline = this.reviewDynamicPipeline(idString);
            const statement = Reviews.aggregate(pipeline);
            return await statement.exec();
        } catch (err) {
            process.stdout.write(`ReviewController.statementExecutionDB : ${err.message}`);
            throw new Error(`An error occurred : ${err.message}`);
        }
    }

    reviewDynamicPipeline(idString) {
        let query = [];

        if (idString) {
            query.push({ $match: { 'reviewID': idString } });
        }
        query.push({ "$lookup": { "from": "tools", "localField": "reviewerID", "foreignField": "id", "as": "person" } });
        query.push({ "$lookup": { "from": "tools", "localField": "toolID", "foreignField": "id", "as": "tool" } });

        console.log(JSON.stringify(query));

        return query;
    }

}

module.exports = new ReviewController();
