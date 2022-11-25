import { Reviews } from '../review.model';
import helper from '../../utilities/helper.util';

class ReviewController {
    constructor() {}

    async handleReviewsUsersPending(req, res) {
        const userRole = req.params.role;
        const idString = parseInt(req.query.id) || '';

        let responseDB;
        let responseApi = {};

        responseApi.success = true;

        responseDB = await this.statementExecutionDB(userRole, 'active', idString);

        if (userRole === 'admin') {
            responseDB.map(item => {
                item.person = helper.hidePrivateProfileDetails(item.person);
            });
        }
        responseApi.data = responseDB;

        if (userRole === 'creator') {
            responseApi.allReviews = await this.statementExecutionDB('active', 'active', idString);
        }

        return res.status(200).json(responseApi);
    }

    async handleReviewsByReviewId (req, res) {
        let reviewIDString = '';

        if (req.query.id) {
            reviewIDString = parseInt(req.query.id);
        }
    
        try {
            const r = Reviews.aggregate([
                { $match: { $and: [{ activeflag: 'active' }, { reviewID: reviewIDString }] } },
                { $lookup: { from: 'tools', localField: 'reviewerID', foreignField: 'id', as: 'person' } },
                { $lookup: { from: 'tools', localField: 'toolID', foreignField: 'id', as: 'tool' } },
            ]);
            r.exec((err, data) => {
                if (err) return res.json({ success: false, error: err });
        
                data.map(dat => {
                    dat.person = helper.hidePrivateProfileDetails(dat.person);
                });
                return res.json({ success: true, data: data });
            });
        } catch (err) {
            process.stdout.write(`ReviewController.handleReviewsByReviewId : ${err.message}`);
            throw new Error(`An error occurred : ${err.message}`);
        }
    }

    async statementExecutionDB(role, flag, idString) {
        try {
            const pipeline = this.reviewDynamicPipeline(role, flag, idString);
            const statement = Reviews.aggregate(pipeline);
            return await statement.exec();
        } catch (err) {
            process.stdout.write(`ReviewController.handleReviewsByReviewId : ${err.message}`);
            throw new Error(`An error occurred : ${err.message}`);
        }
    }

    reviewDynamicPipeline(role, flag, idString) {
        let query = [];

        if (role === 'creator') {
            query.push({ $match: { $and: [{ activeflag: flag }, { reviewID: idString }] } });
        }
        query.push({ $lookup: { from: 'tools', localField: 'reviewerID', foreignField: 'id', as: 'person' } });
        query.push({ $lookup: { from: 'tools', localField: 'toolID', foreignField: 'id', as: 'tool' } });

        return query;
    }
}

module.exports = new ReviewController();