import express from 'express';
import { Help } from './help.model';
import _ from 'lodash';

const router = express.Router();

// @router   GET /api/help/:category
// @desc     Get Help FAQ for a category
// @access   Public
router.get('/:category', async (req, res) => {
	try {
		// 1. Destructure category parameter with safe default
		let { category = '' } = req.params;
		// 2. Check if parameter is empty (if required throw error response)
		if (_.isEmpty(category)) {
			return res.status(400).json({ success: false, message: 'Category is required' });
		}
		// 3. Find matching help items in MongoDb
		let help = await Help.find({ $and: [{ active: true }, { category }] });
		// 4. Return help data in response
		return res.status(200).json({ success: true, help });
	} catch (err) {
		process.stdout.write(`HELP ROUTER - GET CATEGORY : ${err.message}\n`);
		return res.status(500).json({
			success: false,
			message: 'An error occurred searching for help data',
		});
	}
});

module.exports = router;
