import { Reviews } from '../review.model';
import helper from '../../utilities/helper.util';

class ReviewController {
    constructor() {}

    async handleReviewsAdminPending (req, res) {
        try {
            const r = Reviews.aggregate([
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
            process.stdout.write(`ReviewController.handleReviewsAdminPending : ${err.message}`);
            throw new Error(`An error occurred : ${err.message}`);
        }
    }

    async handleReviewsCreatorPending (req, res) {
        let idString = '';

        if (req.query.id) {
            idString = parseInt(req.query.id);
        }
    
        try {
            const r = Reviews.aggregate([
                { $match: { $and: [{ activeflag: 'review' }, { reviewerID: idString }] } },
                { $lookup: { from: 'tools', localField: 'reviewerID', foreignField: 'id', as: 'person' } },
                { $lookup: { from: 'tools', localField: 'toolID', foreignField: 'id', as: 'tool' } },
            ]);
            r.exec((err, data) => {
                const a = Reviews.aggregate([
                    { $match: { $and: [{ activeflag: 'active' }, { reviewerID: idString }] } },
                    { $lookup: { from: 'tools', localField: 'reviewerID', foreignField: 'id', as: 'person' } },
                    { $lookup: { from: 'tools', localField: 'toolID', foreignField: 'id', as: 'tool' } },
                ]);
                a.exec((err, allReviews) => {
                    if (err) return res.json({ success: false, error: err });
                    return res.json({ success: true, data: data, allReviews: allReviews });
                });
            });    
        } catch (err) {
            process.stdout.write(`ReviewController.handleReviewsCreatorPending : ${err.message}`);
            throw new Error(`An error occurred : ${err.message}`);
        }
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
}

module.exports = new ReviewController();