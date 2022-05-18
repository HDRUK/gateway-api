/* eslint-disable class-methods-use-this */
import Mongoose from 'mongoose';
import Controller from '../base/controller';
import { logger } from '../utilities/logger';
import constants from './../utilities/constants.util';
import { Data } from '../tool/data.model';
import { Course } from '../course/course.model';
import { TeamModel } from '../team/team.model';
import teamController from '../team/team.controller';
import emailGenerator from '../utilities/emailGenerator.util';
import { getObjectFilters } from '../search/search.repository';
import { filtersService } from '../filters/dependency';

import { DataUseRegister } from '../dataUseRegister/dataUseRegister.model';
import { isEmpty, isUndefined } from 'lodash';
import { UserModel } from '../user/user.model';

const logCategory = 'dataUseRegister';

export default class DataUseRegisterController extends Controller {
	constructor(dataUseRegisterService, activityLogService) {
		super(dataUseRegisterService);
		this.dataUseRegisterService = dataUseRegisterService;
		this.activityLogService = activityLogService;
	}

	async getDataUseRegister(req, res) {
		try {
			// Extract id parameter from query string
			const { id } = req.params;
			const isEdit = req.query.isEdit || false;
			if (req.query.isEdit) delete req.query.isEdit;

			// If no id provided, it is a bad request
			if (!id) {
				return res.status(400).json({
					success: false,
					message: 'You must provide a dataUseRegister identifier',
				});
			}

			// Find the dataUseRegister
			const options = {
				lean: true,
				populate: [
					{ path: 'gatewayApplicants', select: 'id firstname lastname' },
					{ path: 'gatewayDatasetsInfo', select: 'name pid' },
					{ path: 'gatewayOutputsToolsInfo', select: 'name id' },
					{ path: 'gatewayOutputsPapersInfo', select: 'name id' },
				],
			};
			const dataUseRegister = await this.dataUseRegisterService.getDataUseRegister(id, req.query, options);

			// Return if no dataUseRegister found
			if (!dataUseRegister) {
				return res.status(404).json({
					success: false,
					message: 'A dataUseRegister could not be found with the provided id',
				});
			}

			// Reverse look up
			let relatedData = await Data.find({
				relatedObjects: { $elemMatch: { objectId: req.params.id } },
				activeflag: 'active',
			});

			let relatedDataFromCourses = await Course.find({
				relatedObjects: { $elemMatch: { objectId: req.params.id } },
				activeflag: 'active',
			});

			let relatedDataFromDatauses = await DataUseRegister.find({
				relatedObjects: { $elemMatch: { objectId: req.params.id } },
				activeflag: 'active',
			});

			relatedData = [...relatedData, ...relatedDataFromCourses, ...relatedDataFromDatauses];

			if (!isEdit) {
				relatedData.forEach(dat => {
					dat.relatedObjects.forEach(x => {
						if (x.objectId === id && dat.id !== id) {
							if (typeof dataUseRegister.relatedObjects === 'undefined') dataUseRegister.relatedObjects = [];
							dataUseRegister.relatedObjects.push({
								objectId: dat.id,
								reason: x.reason,
								objectType: dat.type,
								user: x.user,
								updated: x.updated,
							});
						}
					});
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

			if (!isUndefined(team)) {
				if (team === 'user') {
					delete req.query.team;
					query = { ...req.query, gatewayApplicants: requestingUser._id };
				} else if (team === 'admin') {
					delete req.query.team;
					query = { ...req.query, activeflag: constants.dataUseRegisterStatus.INREVIEW };
				} else if (team !== 'user' && team !== 'admin') {
					delete req.query.team;
					query = { publisher: new Mongoose.Types.ObjectId(team) };
				}

				const dataUseRegisters = await this.dataUseRegisterService
					.getDataUseRegisters({ $and: [query] }, { aggregate: true })
					.catch(err => {
						logger.logError(err, logCategory);
					});
				// Return the dataUseRegisters
				return res.status(200).json({
					success: true,
					data: dataUseRegisters,
				});
			} else {
				const dataUseRegisters = await this.dataUseRegisterService.getDataUseRegisters(req.query).catch(err => {
					logger.logError(err, logCategory);
				});
				// Return the dataUseRegisters
				return res.status(200).json({
					success: true,
					data: dataUseRegisters,
				});
			}
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
			const requestingUser = req.user;
			const { rejectionReason } = req.body;

			const options = { lean: true, populate: 'applicantDetails' };
			const dataUseRegister = await this.dataUseRegisterService.getDataUseRegister(id, {}, options);
			const updateObj = await this.dataUseRegisterService.buildUpdateObject(dataUseRegister, req.body, requestingUser);

			if (isEmpty(updateObj)) {
				return res.status(200).json({
					success: true,
				});
			}

			await this.dataUseRegisterService.updateDataUseRegister(dataUseRegister._id, updateObj).catch(err => {
				logger.logError(err, logCategory);
			});

			filtersService.optimiseFilters('dataUseRegister');

			const isDataUseRegisterApproved =
				updateObj.activeflag &&
				updateObj.activeflag === constants.dataUseRegisterStatus.ACTIVE &&
				dataUseRegister.activeflag === constants.dataUseRegisterStatus.INREVIEW;

			const isDataUseRegisterRejected =
				updateObj.activeflag &&
				updateObj.activeflag === constants.dataUseRegisterStatus.REJECTED &&
				dataUseRegister.activeflag === constants.dataUseRegisterStatus.INREVIEW;

			// Send notifications
			if (isDataUseRegisterApproved) {
				await this.createNotifications(constants.dataUseRegisterNotifications.DATAUSEAPPROVED, {}, dataUseRegister);
			} else if (isDataUseRegisterRejected) {
				await this.createNotifications(constants.dataUseRegisterNotifications.DATAUSEREJECTED, { rejectionReason }, dataUseRegister);
			}

			if (!isEmpty(updateObj)) {
				await this.activityLogService.logActivity(constants.activityLogEvents.DATA_USE_REGISTER_UPDATED, {
					dataUseRegister,
					updateObj,
					user: requestingUser,
				});
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
			await this.createNotifications(constants.dataUseRegisterNotifications.DATAUSEPENDING, {}, result, teamId);
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

	async searchDataUseRegisters(req, res) {
		try {
			let searchString = req.query.search || '';

			if (typeof searchString === 'string' && searchString.includes('-') && !searchString.includes('"')) {
				const regex = /(?=\S*[-])([a-zA-Z'-]+)/g;
				searchString = searchString.replace(regex, '"$1"');
			}
			let searchQuery = { $and: [{ activeflag: 'active' }] };

			searchQuery = getObjectFilters(searchQuery, req, 'dataUseRegister');

			const aggregateQuery = [
				{
					$lookup: {
						from: 'publishers',
						localField: 'publisher',
						foreignField: '_id',
						as: 'publisherDetails',
					},
				},
				{
					$lookup: {
						from: 'tools',
						localField: 'gatewayOutputsTools',
						foreignField: 'id',
						as: 'gatewayOutputsToolsInfo',
					},
				},
				{
					$lookup: {
						from: 'tools',
						localField: 'gatewayOutputsPapers',
						foreignField: 'id',
						as: 'gatewayOutputsPapersInfo',
					},
				},
				{
					$lookup: {
						from: 'users',
						let: {
							listOfGatewayApplicants: '$gatewayApplicants',
						},
						pipeline: [
							{
								$match: {
									$expr: {
										$and: [{ $in: ['$_id', '$$listOfGatewayApplicants'] }],
									},
								},
							},
							{ $project: { firstname: 1, lastname: 1 } },
						],

						as: 'gatewayApplicantsDetails',
					},
				},
				{
					$lookup: {
						from: 'tools',
						let: {
							listOfGatewayDatasets: '$gatewayDatasets',
						},
						pipeline: [
							{
								$match: {
									$expr: {
										$and: [
											{ $in: ['$pid', '$$listOfGatewayDatasets'] },
											{
												$eq: ['$activeflag', 'active'],
											},
										],
									},
								},
							},
							{ $project: { pid: 1, name: 1 } },
						],
						as: 'gatewayDatasetsInfo',
					},
				},
				{
					$addFields: {
						publisherInfo: { name: '$publisherDetails.name' },
					},
				},
				{ $match: searchQuery },
			];

			if (searchString.length > 0) {
				aggregateQuery.unshift({ $match: { $text: { $search: searchString } } });
			}

			const result = await DataUseRegister.aggregate(aggregateQuery);

			return res.status(200).json({ success: true, result });
		} catch (err) {
			//Return error response if something goes wrong
			logger.logError(err, logCategory);
			return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
		}
	}

	async createNotifications(type, context, dataUseRegister, publisher) {
		const { rejectionReason } = context;
		const { id, projectTitle, user: uploaderID } = dataUseRegister;

		const uploader = await UserModel.findOne({ _id: uploaderID });

		switch (type) {
			case constants.dataUseRegisterNotifications.DATAUSEAPPROVED: {
				let teamEmailNotification = [];
				const adminTeam = await TeamModel.findOne({ type: 'admin' })
					.populate({
						path: 'users',
					})
					.lean();
				const team = await TeamModel.findById(dataUseRegister.publisher.toString());
				if (team.notifications.length > 0 && team.notifications[0].optIn) {
					team.notifications[0].subscribedEmails.map(teamEmail => {
						teamEmailNotification.push({email: teamEmail});
					});
				}
				const dataUseTeamMembers = teamController.getTeamMembersByRole(adminTeam, constants.roleTypes.ADMIN_DATA_USE);
				const emailRecipients = [...dataUseTeamMembers, uploader, ...teamEmailNotification];

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
			case constants.dataUseRegisterNotifications.DATAUSEPENDING: {
				const adminTeam = await TeamModel.findOne({ type: 'admin' })
					.populate({
						path: 'users',
					})
					.lean();

				const publisherTeam = await TeamModel.findOne({ _id: { $eq: publisher } })
					.populate({
						path: 'publisher',
					})
					.lean();

				const dataUseTeamMembers = teamController.getTeamMembersByRole(adminTeam, constants.roleTypes.ADMIN_DATA_USE);
				const emailRecipients = [...dataUseTeamMembers];

				const { uploaded } = dataUseRegister;
				let listOfProjectTitles = [];
				uploaded.forEach(dataset => {
					listOfProjectTitles.push(dataset.projectTitle);
				});

				const options = {
					listOfProjectTitles,
					publisher: publisherTeam.publisher.name,
				};

				const html = emailGenerator.generateDataUseRegisterPending(options);
				emailGenerator.sendEmail(emailRecipients, constants.hdrukEmail, `New data uses to review`, html, false);
				break;
			}
		}
	}

	updateDataUseRegisterCounter(req, res) {
		try {
			const { id, counter } = req.body;
			this.dataUseRegisterService.updateDataUseRegister(id, { counter });
			return res.status(200).json({ success: true });
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
