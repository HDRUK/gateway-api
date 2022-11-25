import { Reviews } from '../review.model';
import helper from '../../utilities/helper.util';

class ReviewController {
    constructor() {}

    async handleReviewsUsersPending(req, res) {
        const userRole = req.params.role;
        const idString = parseInt(req.query.id) || '';

        let pipeline, statement, response;
        let responseApi = {};

        responseApi.success = true;

        pipeline = this.reviewDynamicPipeline(userRole, 'active', idString);
        statement = Reviews.aggregate(pipeline);
        response = await statement.exec();

        if (userRole === 'admin') {
            response.map(item => {
                item.person = helper.hidePrivateProfileDetails(item.person);
            });
        }
        responseApi.data = response;

        if (userRole === 'creator') {
            pipeline = this.reviewDynamicPipeline('active', 'active', idString);
            statement = Reviews.aggregate(pipeline);
            response = await statement.exec();
            responseApi.allReviews = response;
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