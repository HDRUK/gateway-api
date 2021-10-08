import express from 'express';
import { Course } from './course.model';
const rateLimit = require('express-rate-limit');

const router = express.Router();

const datasetLimiter = rateLimit({
	windowMs: 60 * 1000, // 1 minute window
	max: 50, // start blocking after 50 requests
	message: 'Too many calls have been made to this api from this IP, please try again after an hour',
});

router.post('/update', datasetLimiter, async (req, res) => {
	const { id, counter } = req.body;
	Course.findOneAndUpdate({ id: { $eq: id } }, { counter }, err => {
		if (err) return res.json({ success: false, error: err });
		return res.json({ success: true });
	});
});

module.exports = router;
