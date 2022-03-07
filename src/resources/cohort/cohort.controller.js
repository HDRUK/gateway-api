import Controller from '../base/controller';
import { logger } from '../utilities/logger';

const logCategory = 'cohort';

export default class CohortController extends Controller {
	constructor(cohortService) {
		super(cohortService);
		this.cohortService = cohortService;
	}

	async getCohort(req, res) {
		try {
			// Extract id parameter from query string
			const { id } = req.params;
			// If no id provided, it is a bad request
			if (!id) {
				return res.status(400).json({
					success: false,
					message: 'You must provide a cohort identifier',
				});
			}
			// Find the cohort
			const options = { lean: true };
			const cohort = await this.cohortService.getCohort(id, req.query, options);
			// Return if no cohort found
			if (!cohort) {
				return res.status(404).json({
					success: false,
					message: 'A cohort could not be found with the provided id',
				});
			}
			// Return the cohort
			return res.status(200).json({
				success: true,
				...cohort,
			});
		} catch (err) {
			// Return error response if something goes wrong
			console.error(err.message);
			return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
		}
	}

	async getCohorts(req, res) {
		try {
			// Find the relevant cohorts
			const cohorts = await this.cohortService.getCohorts(req.query).catch(err => {
				logger.logError(err, logCategory);
			});
			// Return the cohorts
			return res.status(200).json({
				success: true,
				data: cohorts,
			});
		} catch (err) {
			// Return error response if something goes wrong
			logger.logError(err, logCategory);
			return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
		}
	}

	async addCohort(req, res) {
		try {
			const cohort = await this.cohortService.addCohort(req.body).catch(err => {
				logger.logError(err, logCategory);
			});
			const { id } = cohort;

			// Return the cohorts
			return res.status(201).json({
				informationrequestid: req.body.query_id,
				redirect_url: `${process.env.GATEWAY_WEB_URL}/cohort/add/${id}`,
			});
		} catch (err) {
			// Return error response if something goes wrong
			logger.logError(err, logCategory);
			return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
		}
	}
}
