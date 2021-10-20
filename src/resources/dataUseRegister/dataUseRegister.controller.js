import Controller from '../base/controller';
import { logger } from '../utilities/logger';
import constants from './../utilities/constants.util';
import { Data } from '../tool/data.model';
import { TeamModel } from '../team/team.model';
import teamController from '../team/team.controller';
import emailGenerator from '../utilities/emailGenerator.util';
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
			// Reverse look up
			var query = Data.aggregate([
				{ $match: { id: parseInt(req.params.id) } },
				{
					$lookup: {
						from: 'tools',
						localField: 'creator',
						foreignField: 'id',
						as: 'creator',
					},
				},
			]);
			query.exec((err, data) => {
				if (data.length > 0) {
					var p = Data.aggregate([
						{
							$match: {
								$and: [{ relatedObjects: { $elemMatch: { objectId: req.params.id } } }],
							},
						},
					]);
					p.exec((err, relatedData) => {
						relatedData.forEach(dat => {
							dat.relatedObjects.forEach(x => {
								if (x.objectId === req.params.id && dat.id !== req.params.id) {
									let relatedObject = {
										objectId: dat.id,
										reason: x.reason,
										objectType: dat.type,
										user: x.user,
										updated: x.updated,
									};
									data[0].relatedObjects = [relatedObject, ...(data[0].relatedObjects || [])];
								}
							});
						});

						if (err) return res.json({ success: false, error: err });

						return res.json({
							success: true,
							data: data,
						});
					});
				} else {
					return res.status(404).send(`Data Use Register not found for Id: ${escape(id)}`);
				}
			});
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
					query = { activeflag: constants.dataUseRegisterStatus.INREVIEW };
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
			const { activeflag, rejectionReason } = req.body;
			const requestingUser = req.user;

			const options = { lean: true, populate: 'user' };
			const dataUseRegister = await this.dataUseRegisterService.getDataUseRegister(id, {}, options);

			this.dataUseRegisterService.updateDataUseRegister(id, req.body).catch(err => {
				logger.logError(err, logCategory);
			});

			// Send notifications
			if (activeflag === 'active') {
				this.createNotifications(constants.dataUseRegisterNotifications.DATAUSEAPPROVED, {}, dataUseRegister, requestingUser);
			} else if (activeflag === 'rejected') {
				this.createNotifications(
					constants.dataUseRegisterNotifications.DATAUSEREJECTED,
					{ rejectionReason },
					dataUseRegister,
					requestingUser
				);
			}

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

	async checkDataUseRegister(req, res) {
		try {
			const { dataUses } = req.body;

			const result = await this.dataUseRegisterService.checkDataUseRegisters(dataUses);

			return res.status(200).json({ success: true, result });
		} catch (err) {
			// Return error response if something goes wrong
			logger.logError(err, logCategory);
			return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
		}
	}

	async createNotifications(type, context, dataUseRegister, requestingUser) {
		const { teams } = requestingUser;
		const { rejectionReason } = context;
		const { id, projectTitle, user: uploader } = dataUseRegister;

		switch (type) {
			case constants.dataUseRegisterNotifications.DATAUSEAPPROVED: {
				const adminTeam = await TeamModel.findOne({ type: 'admin' })
					.populate({
						path: 'users',
					})
					.lean();
				const dataUseTeamMembers = teamController.getTeamMembersByRole(adminTeam, constants.roleTypes.ADMIN_DATA_USE);
				const emailRecipients = [...dataUseTeamMembers, uploader];

				const options = {
					id,
					projectTitle,
				};

				const html = emailGenerator.generateDataUseRegisterApproved(options);
				emailGenerator.sendEmail(emailRecipients, constants.hdrukEmail, `A data use has been approved by HDR UK`, html, false);
				break;
			}

			case constants.dataUseRegisterNotifications.DATAUSEREJECTED: {
				const adminTeam = await TeamModel.findOne({ type: 'admin' })
					.populate({
						path: 'users',
					})
					.lean();

				const dataUseTeamMembers = teamController.getTeamMembersByRole(adminTeam, constants.roleTypes.ADMIN_DATA_USE);
				const emailRecipients = [...dataUseTeamMembers, uploader];

				const options = {
					id,
					projectTitle,
					rejectionReason,
				};

				const html = emailGenerator.generateDataUseRegisterRejected(options);
				emailGenerator.sendEmail(emailRecipients, constants.hdrukEmail, `A data use has been rejected by HDR UK`, html, false);
				break;
			}
		}
	}
}