import Controller from '../../base/controller';
import { logger } from '../../utilities/logger';

const logCategory = 'datarequestschema';

export default class DatarequestschemaController extends Controller {
	constructor(datarequestschemaService) {
		super(datarequestschemaService);
		this.datarequestschemaService = datarequestschemaService;
	}

	async getDatarequestschema(req, res) {
		try {
			// Extract id parameter from query string
			const { id } = req.params;
			// If no id provided, it is a bad request
			if (!id) {
				return res.status(400).json({
					success: false,
					message: 'You must provide a datarequestschema identifier',
				});
			}
			// Find the datarequestschema
			const datarequestschema = await this.datarequestschemaService.getDatarequestschemaById(id);
			// Return if no datarequestschema found
			if (!datarequestschema) {
				return res.status(404).json({
					success: false,
					message: 'A datarequestschema could not be found with the provided id',
				});
			}
			// Return the datarequestschema
			return res.status(200).json({
				success: true,
				...datarequestschema,
			});
		} catch (err) {
			// Return error response if something goes wrong
			process.stdout.write(`DATA REQUEST - getDatarequestschema : ${err.message}\n`);
			return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
		}
	}

	async getDatarequestschemas(req, res) {
		try {
			// Find the relevant datarequestschemas
			const datarequestschemas = await this.datarequestschemaService.getDatarequestschemas(req.query).catch(err => {
				logger.logError(err, logCategory);
			});
			// Return the datarequestschemas
			return res.status(200).json({
				success: true,
				data: datarequestschemas,
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

	async updateDatarequestschema(req, res) {
		try {
			const id = req.params.id;
			const updatedSchema = req.body;

			// Find the relevant datarequestschemas
			const datarequestschema = await this.datarequestschemaService.updateDatarequestschema(id, updatedSchema).catch(err => {
				logger.logError(err, logCategory);
			});
			// Return the datarequestschemas
			return res.status(200).json({
				success: true,
				data: datarequestschema,
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
