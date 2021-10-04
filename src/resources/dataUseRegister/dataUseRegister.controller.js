import Controller from '../base/controller';
import { logger } from '../utilities/logger';
import _ from 'lodash';
import constants from './../utilities/constants.util';

const logCategory = 'dataUseRegister';

export default class DataUseRegisterController extends Controller {
	constructor(dataUseRegisterService) {
		super(dataUseRegisterService);
		this.dataUseRegisterService = dataUseRegisterService;
	}

	async getDataUseRegister(req, res) {
		try {
			// Extract id parameter from query string
			const { id } = req.params;
			// If no id provided, it is a bad request
			if (!id) {
				return res.status(400).json({
					success: false,
					message: 'You must provide a dataUseRegister identifier',
				});
			}
			// Find the dataUseRegister
			const options = { lean: true };
			const dataUseRegister = await this.dataUseRegisterService.getDataUseRegister(id, req.query, options);
			// Return if no dataUseRegister found
			if (!dataUseRegister) {
				return res.status(404).json({
					success: false,
					message: 'A dataUseRegister could not be found with the provided id',
				});
			}
			// Return the dataUseRegister
			return res.status(200).json({
				success: true,
				...dataUseRegister,
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

	async getDataUseRegisters(req, res) {
		try {
			const { team } = req.query;
			const requestingUser = req.user;

			let query = '';
			switch (team) {
				case 'user':
					query = { user: requestingUser._id };
					break;
				case 'admin':
					query = { status: constants.dataUseRegisterStatus.INREVIEW };
					break;
				default:
					query = { publisher: team };
			}

			const dataUseRegisters = await this.dataUseRegisterService.getDataUseRegisters(query).catch(err => {
				logger.logError(err, logCategory);
			});
			// Return the dataUseRegisters
			return res.status(200).json({
				success: true,
				data: dataUseRegisters,
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

	async updateDataUseRegister(req, res) {
		try {
			const id = req.params.id;
			const body = req.body;

			this.dataUseRegisterService.updateDataUseRegister(id, body).catch(err => {
				logger.logError(err, logCategory);
			});
			// Return success
			return res.status(200).json({
				success: true,
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

	async uploadDataUseRegisters(req, res) {
		try {
			const { teamId, dataUses } = req.body;
			const requestingUser = req.user;
			const result = await this.dataUseRegisterService.uploadDataUseRegisters(requestingUser, teamId, dataUses);
			// Return success
			return res.status(result.uploadedCount > 0 ? 201 : 200).json({
				success: true,
				result,
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
