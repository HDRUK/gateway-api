import express from 'express';
import { statsService } from '../dependency';
import { logger } from '../../utilities/logger';

const router = express.Router();
const logCategory = 'Stats';

router.get('', logger.logRequestMiddleware({ logCategory, action: 'Viewed stats' }), async (req, res) => {
	try {
		const { query = {} } = req;
		const { type: entityType, month, year } = query;

		let result;
		let data;

		switch (req.query.rank) {
			case 'recent':
				data = await statsService.getRecentSearches().catch(err => {
					logger.logError(err, logCategory);
				});

				result = res.json({
					success: true,
					data,
				});
				break;

			case 'popular':
				data = await statsService.getPopularEntitiesByType(entityType).catch(err => {
					logger.logError(err, logCategory);
				});

				result = res.json({
					success: true,
					data,
				});
				break;

			case 'updates':
				data = await statsService.getRecentlyUpdatedEntitiesByType(entityType).catch(err => {
					logger.logError(err, logCategory);
				});

				result = res.json({
					success: true,
					data,
				});
				break;

			case 'unmet':
				const monthInt = parseInt(month);
				const yearInt = parseInt(year);
				data = await statsService.getUnmetSearchesByMonth(entityType, monthInt, yearInt).catch(err => {
					logger.logError(err, logCategory);
				});

				result = res.json({
					success: true,
					data,
				});
				break;

			default:
				const [searchCounts, accessRequestCount, entityTotalCounts, coursesActiveCount, dataUsesActiveCount] = await Promise.all([
					statsService.getTotalSearchesByUsers(),
					statsService.getDataAccessRequestStats(),
					statsService.getTotalEntityCounts(),
					statsService.getActiveCourseCount(),
					statsService.getActiveDataUsesCount(),
				]).catch(err => {
					logger.logError(err, logCategory);
				});

				data = {
					typecounts: {
						...entityTotalCounts,
						accessRequests: accessRequestCount,
						course: coursesActiveCount,
						dataUses: dataUsesActiveCount,
					},
					daycounts: searchCounts,
				};

				result = res.json({
					success: true,
					data,
				});

				break;
		}
		return result;
	} catch (err) {
		process.stdout.write(`STATS : ${err.message}\n`);
		return res.json({ success: false, error: err.message });
	}
});

router.get('/topSearches', logger.logRequestMiddleware({ logCategory, action: 'Viewed top search stats' }), async (req, res) => {
	try {
		const monthInt = parseInt(req.query.month);
		const yearInt = parseInt(req.query.year);
		const data = await statsService.getTopSearchesByMonth(monthInt, yearInt).catch(err => {
			logger.logError(err, logCategory);
		});
		return res.json({
			success: true,
			data,
		});
	} catch (err) {
		logger.logError(err, logCategory);
		return res.status(500).json({
			success: false,
			message: 'A server error occurred, please try again',
		});
	}
});

module.exports = router;
