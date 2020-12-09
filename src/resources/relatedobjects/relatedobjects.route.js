import express from 'express'
import { Data } from '../tool/data.model'
import { Course } from "../course/course.model";

const router = express.Router();

/**
 * {get} /relatedobjects/:id
 * 
 * Return the details on the relatedobject based on the ID.
 */
router.get('/:id', async (req, res) => { 
    console.log(`in relatedobjects.route`)
    let id = req.params.id;
    if (!isNaN(id)) {
        let q = Data.aggregate([
            { $match: { $and: [{ id: parseInt(id) }] } },
            { $lookup: { from: "tools", localField: "authors", foreignField: "id", as: "persons" } }
        ]);
        q.exec((err, data) => {
            if (err) return res.json({ success: false, error: err });
            return res.json({ success: true, data: data });
        });
    }
    else {
        try {
            // Get related dataset
            // 1. Search for a dataset based on pid 
            let data = await Data.aggregate([
                { $match: { $and: [{ pid: id }, {activeflag: 'active'}] } }
            ]).exec();
            
            // 2. If dataset not found search for a dataset based on datasetID
            if(!data || data.length <=0){
                data = await Data.aggregate([
                    { $match: { datasetid: id } }
                ]).exec();
                
                // 3. Use retrieved dataset's pid to search by pid again
                data = await Data.aggregate([
                    { $match: { $and: [{ pid: data[0].pid }, {activeflag: 'active'}] } }
                ]).exec();

                // 4. If related dataset is archived, append old datasetid so it can be unlinked later
                if(id !== data[0].datasetid){
                    data[0].oldDatasetId = id;
                }
            }

            return res.json({ success: true, data: data });
        } catch (err) {
            return res.json({ success: false, error: err });
        }
    }
});

router.get('/course/:id', async (req, res) => { 
    var id = req.params.id;

        var q = Course.aggregate([
            { $match: { $and: [{ id: parseInt(id) }] } },
            // { $lookup: { from: "tools", localField: "authors", foreignField: "id", as: "persons" } }
        ]);
        q.exec((err, data) => {
            if (err) return res.json({ success: false, error: err });
            return res.json({ success: true, data: data });
        });
 
    });

module.exports = router;