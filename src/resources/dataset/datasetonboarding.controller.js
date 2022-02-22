import axios from 'axios';
import FormData from 'form-data';
import moment from 'moment';
import * as Sentry from '@sentry/node';
var fs = require('fs');
import _ from 'lodash';

import { Data } from '../tool/data.model';
import { PublisherModel } from '../publisher/publisher.model';
import { filtersService } from '../filters/dependency';
import constants from '../utilities/constants.util';
import datasetonboardingUtil from './utils/datasetonboarding.util';
import { v4 as uuidv4 } from 'uuid';
import { isEmpty, isNil, escapeRegExp } from 'lodash';
import { activityLogService } from '../activitylog/dependency';
const HttpClient = require('../../services/httpClient/httpClient');

const readEnv = process.env.ENV || 'prod';

module.exports = {
	//GET api/v1/dataset-onboarding
	getDatasetsByPublisher: async (req, res) => {
		try {
			let {
				params: { publisherID },
			} = req;

			//If not publihserID found then return error
			if (!publisherID) return res.status(404).json({ status: 'error', message: 'Publisher ID could not be found.' });

			//Build query, if the publisherId is admin then only return the inReview datasets
			let query = {};
			if (publisherID === constants.userTypes.ADMIN) {
				// get all datasets in review for admin
				query = {
					activeflag: { $in: ['active', 'inReview', 'draft', 'rejected', 'archive'] },
					type: 'dataset',
				};
			} else {
				// get all pids for publisherID
				query = {
					'datasetv2.summary.publisher.identifier': publisherID,
					type: 'dataset',
					activeflag: { $in: ['active', 'inReview', 'draft', 'rejected', 'archive'] },
				};
			}

			const datasets = await Data.find(query)
				.select(
					'_id pid name datasetVersion activeflag timestamps applicationStatusDesc applicationStatusAuthor percentageCompleted datasetv2.summary.publisher.name'
				)
				.sort({ 'timestamps.updated': -1 })
				.lean();

			//Loop through the list of datasets and attach the list of versions to them
			const listOfDatasets = datasets.reduce((arr, dataset) => {
				dataset.listOfVersions = [];
				const datasetIdx = arr.findIndex(item => item.pid === dataset.pid);
				if (datasetIdx === -1) {
					arr = [...arr, dataset];
				} else {
					const { _id, datasetVersion, activeflag } = dataset;
					const versionDetails = { _id, datasetVersion, activeflag };
					arr[datasetIdx].listOfVersions = [...arr[datasetIdx].listOfVersions, versionDetails];
				}
				if (publisherID === constants.userTypes.ADMIN) {
					arr = arr.filter(dataset => dataset.activeflag === constants.applicationStatuses.INREVIEW);
				}
				return arr;
			}, []);

			return res.status(200).json({
				success: true,
				data: { listOfDatasets },
			});
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	//GET api/v1/dataset-onboarding/:id
	getDatasetVersion: async (req, res) => {
		try {
			const id = req.params.id || null;

			if (!id) return res.status(404).json({ status: 'error', message: 'Dataset pid could not be found.' });

			let dataset = await Data.findOne({ _id: id });
			if (dataset.questionAnswers) {
				dataset.questionAnswers = JSON.parse(dataset.questionAnswers);
			} else {
				//if no questionAnswers then populate from MDC
				dataset.questionAnswers = datasetonboardingUtil.populateQuestionAnswers(dataset.datasetv2);
				await Data.findOneAndUpdate({ _id: id }, { questionAnswers: JSON.stringify(dataset.questionAnswers) });
			}

			if (isEmpty(dataset.structuralMetadata)) {
				//if no structuralMetadata then populate from MDC
				dataset.structuralMetadata = datasetonboardingUtil.populateStructuralMetadata(dataset.datasetfields.technicaldetails);
				await Data.findOneAndUpdate({ _id: id }, { structuralMetadata: dataset.structuralMetadata });
			}

			let listOfDatasets = await Data.find({ pid: dataset.pid }, { _id: 1, datasetVersion: 1, activeflag: 1 }).sort({
				'timestamps.created': -1,
			});

			return res.status(200).json({
				success: true,
				data: { dataset },
				listOfDatasets,
			});
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	//POST api/v1/dataset-onboarding
	createNewDatasetVersion: async (req, res) => {
		try {
			const publisherID = req.body.publisherID || null;
			const pid = req.body.pid || null;
			const currentVersionId = req.body.currentVersionId || null;

			//Check user type and authentication to submit application
			let { authorised } = await datasetonboardingUtil.getUserPermissionsForDataset(null, req.user, publisherID);
			if (!authorised) {
				return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
			}

			//If no publisher then return error
			if (!publisherID) return res.status(404).json({ status: 'error', message: 'Dataset publisher could not be found.' });

			const publisherData = await PublisherModel.find({ _id: publisherID }).lean();
			let publisherObject = {
				summary: {
					publisher: {
						identifier: publisherID,
						name: publisherData[0].publisherDetails.name,
						memberOf: publisherData[0].publisherDetails.memberOf,
					},
				},
			};

			//If publisher but no pid then new dataset - create new pid and version is 1.0.0
			if (!pid) {
				let uuid = '';
				while (uuid === '') {
					uuid = uuidv4();
					if ((await Data.find({ pid: uuid }).length) === 0) uuid = '';
				}

				let uniqueID = '';
				while (uniqueID === '') {
					uniqueID = parseInt(Math.random().toString().replace('0.', ''));
					if ((await Data.find({ id: uniqueID }).length) === 0) uniqueID = '';
				}

				let data = new Data();
				data.pid = uuid;
				data.datasetVersion = '1.0.0';
				data.id = uniqueID;
				data.datasetid = 'New dataset';
				data.name = `New dataset ${moment(Date.now()).format('D MMM YYYY HH:mm')}`;
				data.datasetv2 = publisherObject;
				data.type = 'dataset';
				data.activeflag = 'draft';
				data.source = 'HDRUK MDC';
				data.is5Safes = publisherData[0].uses5Safes;
				data.timestamps.created = Date.now();
				data.timestamps.updated = Date.now();
				data.questionAnswers = JSON.stringify({
					'properties/summary/title': `New dataset ${moment(Date.now()).format('D MMM YYYY HH:mm')}`,
				});
				await data.save();

				return res.status(200).json({ success: true, data: { id: data._id } });
			} else {
				//check does a version already exist with the pid that is in draft
				let isDraftDataset = await Data.findOne({ pid, activeflag: 'draft' }, { _id: 1 });

				if (!isNil(isDraftDataset)) {
					//if yes then return with error
					return res.status(200).json({ success: true, data: { id: isDraftDataset._id, draftExists: true } });
				}

				//else create new version of currentVersionId and send back new id
				let datasetToCopy = await Data.findOne({ _id: currentVersionId });

				if (isNil(datasetToCopy)) {
					return res.status(404).json({ status: 'error', message: 'Dataset to copy is not found' });
				}

				//create new uniqueID
				let uniqueID = '';
				while (uniqueID === '') {
					uniqueID = parseInt(Math.random().toString().replace('0.', ''));
					if ((await Data.find({ id: uniqueID }).length) === 0) uniqueID = '';
				}

				//incremenet the dataset version
				let newVersion = datasetonboardingUtil.incrementVersion([1, 0, 0], datasetToCopy.datasetVersion);

				datasetToCopy.questionAnswers = JSON.parse(datasetToCopy.questionAnswers);
				if (!datasetToCopy.questionAnswers['properties/documentation/description'] && datasetToCopy.description)
					datasetToCopy.questionAnswers['properties/documentation/description'] = datasetToCopy.description;

				let data = new Data();
				data.pid = pid;
				data.datasetVersion = newVersion;
				data.id = uniqueID;
				data.datasetid = 'New dataset version';
				data.name = datasetToCopy.name;
				data.datasetv2 = datasetToCopy.datasetv2;
				data.datasetv2.identifier = '';
				data.datasetv2.version = '';
				data.type = 'dataset';
				data.activeflag = 'draft';
				data.source = 'HDRUK MDC';
				data.is5Safes = publisherData[0].uses5Safes;
				data.questionAnswers = JSON.stringify(datasetToCopy.questionAnswers);
				data.structuralMetadata = datasetToCopy.structuralMetadata;
				data.percentageCompleted = datasetToCopy.percentageCompleted;
				data.timestamps.created = Date.now();
				data.timestamps.updated = Date.now();
				await data.save();

				return res.status(200).json({ success: true, data: { id: data._id } });
			}
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	//PATCH api/v1/dataset-onboarding/:id
	updateDatasetVersionDataElement: async (req, res) => {
		try {
			// 1. Id is the _id object in mongoo.db not the generated id or dataset Id
			const {
				params: { id },
				body: data,
			} = req;
			// 2. Check user type and authentication to submit application
			let { authorised } = await datasetonboardingUtil.getUserPermissionsForDataset(id, req.user);
			if (!authorised) {
				return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
			}
			// 3. Destructure body and update only specific fields by building a segregated non-user specified update object
			let updateObj = datasetonboardingUtil.buildUpdateObject({
				...data,
				user: req.user,
			});
			// 4. Find data request by _id to determine current status
			let dataset = await Data.findOne({ _id: id });
			// 5. Check access record
			if (!dataset) {
				return res.status(404).json({ status: 'error', message: 'Dataset not found.' });
			}
			// 6. Update record object
			if (isEmpty(updateObj)) {
				if (data.key !== 'structuralMetadata') {
					return res.status(404).json({ status: 'error', message: 'Update failed' });
				} else {
					let structuralMetadata = JSON.parse(data.rows);

					if (isEmpty(structuralMetadata)) {
						return res.status(404).json({ status: 'error', message: 'Update failed' });
					} else {
						Data.findByIdAndUpdate(
							{ _id: id },
							{ structuralMetadata, percentageCompleted: data.percentageCompleted, 'timestamps.updated': Date.now() },
							{ new: true }
						).catch(err => {
							console.error(err);
							throw err;
						});

						return res.status(200).json();
					}
				}
			} else {
				await datasetonboardingUtil.updateDataset(dataset, updateObj).then(() => {
					let data = {
						status: 'success',
					};

					if (updateObj.updatedQuestionId === 'properties/summary/title') {
						let questionAnswers = JSON.parse(updateObj.questionAnswers);
						let title = questionAnswers['properties/summary/title'];

						if (title && title.length >= 2) {
							Data.findByIdAndUpdate({ _id: id }, { name: title, 'timestamps.updated': Date.now() }, { new: true }).catch(err => {
								console.error(err);
								throw err;
							});
							data.name = title;
						}
					}

					// 7. Return new data object
					return res.status(200).json(data);
				});
			}
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	//POST api/v1/dataset-onboarding/:id
	submitDatasetVersion: async (req, res) => {
		try {
			// 1. id is the _id object in mongoo.db not the generated id or dataset Id
			const id = req.params.id || null;

			if (!id) return res.status(404).json({ status: 'error', message: 'Dataset _id could not be found.' });

			// 3. Check user type and authentication to submit dataset
			let { authorised } = await datasetonboardingUtil.getUserPermissionsForDataset(id, req.user);
			if (!authorised) {
				return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
			}

			let dataset = await Data.findOne({ _id: id });
			dataset.questionAnswers = JSON.parse(dataset.questionAnswers);

			let datasetv2Object = await datasetonboardingUtil.buildv2Object(dataset);

			//update dataset to inreview - constants.datatsetStatuses.INREVIEW
			let updatedDataset = await Data.findOneAndUpdate(
				{ _id: id },
				{
					datasetv2: datasetv2Object,
					activeflag: constants.datatsetStatuses.INREVIEW,
					'timestamps.updated': Date.now(),
					'timestamps.submitted': Date.now(),
				}
			);

			// emails / notifications
			await datasetonboardingUtil.createNotifications(constants.notificationTypes.DATASETSUBMITTED, updatedDataset);

			await activityLogService.logActivity(constants.activityLogEvents.dataset.DATASET_VERSION_SUBMITTED, {
				type: constants.activityLogTypes.DATASET,
				updatedDataset,
				user: req.user,
			});

			if (updatedDataset.datasetVersion !== '1.0.0') {
				let datasetv2DifferenceObject = datasetonboardingUtil.datasetv2ObjectComparison(datasetv2Object, dataset.datasetv2);

				if (!_.isEmpty(datasetv2DifferenceObject)) {
					await activityLogService.logActivity(constants.activityLogEvents.dataset.DATASET_UPDATES_SUBMITTED, {
						type: constants.activityLogTypes.DATASET,
						updatedDataset,
						user: req.user,
						differences: datasetv2DifferenceObject,
					});
				}
			}

			return res.status(200).json({ status: 'success' });
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	//PUT api/v1/dataset-onboarding/:id
	changeDatasetVersionStatus: async (req, res) => {
		const id = req.params.id || null;
		if (!id) {
			return res.status(404).json({
				status: 'error',
				message: 'Dataset _id could not be found.'
			});
		}

		let { firstname, lastname } = req.user;
		let { applicationStatus, applicationStatusDesc = '' } = req.body;

		let { authorised, userType } = await datasetonboardingUtil.getUserPermissionsForDataset(id, req.user);
		if (!authorised) {
			return res.status(401).json({
				status: 'failure',
				message: 'Unauthorised'
			});
		}

		let metadataCatalogueLink = process.env.MDC_Config_HDRUK_metadataUrl || 'https://modelcatalogue.cs.ox.ac.uk/hdruk-preprod';
		const loginDetails = {
			username: process.env.MDC_Config_HDRUK_username || '',
			password: process.env.MDC_Config_HDRUK_password || '',
		};
		let updatedDataset = null;
		let dataset = null;
		let constantActivityLog = null;
		const _httpClient = new HttpClient();
		await _httpClient.post(metadataCatalogueLink + `/api/authentication/logout`, null, { withCredentials: true, timeout: 5000 });

		switch(applicationStatus) {
			case 'approved':
				if (userType !== constants.userTypes.ADMIN) {
					return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
				}

				dataset = await Data.findOne({ _id: id });

				if (!dataset) return res.status(404).json({ status: 'error', message: 'Dataset could not be found.' });

				dataset.questionAnswers = JSON.parse(dataset.questionAnswers);
				const publisherData = await PublisherModel.find({ _id: dataset.datasetv2.summary.publisher.identifier }).lean();

				const responseLogin = await _httpClient.post(metadataCatalogueLink + '/api/authentication/login', loginDetails, { withCredentials: true, timeout: 5000 });
				const [cookie] = responseLogin.headers["set-cookie"];
				_httpClient.setHttpClientCookies(cookie);
				
				let jsonData = JSON.stringify(await datasetonboardingUtil.buildJSONFile(dataset));
				fs.writeFileSync(__dirname + `/datasetfiles/${dataset._id}.json`, jsonData);
				
				var data = new FormData();
				data.append('folderId', publisherData[0].mdcFolderId);
				data.append('importFile', fs.createReadStream(__dirname + `/datasetfiles/${dataset._id}.json`));
				data.append('finalised', 'false');
				data.append('importAsNewDocumentationVersion', 'true');

				const responseImport = await _httpClient.post(
					metadataCatalogueLink + '/api/dataModels/import/ox.softeng.metadatacatalogue.core.spi.json/JsonImporterService/1.1', 
					data, 
					{
						withCredentials: true,
						timeout: 60000,
						headers: {
							...data.getHeaders(),
						},
					});
				
				let newDatasetVersionId = responseImport.data.items[0].id;
				fs.unlinkSync(__dirname + `/datasetfiles/${dataset._id}.json`);

				const updatedDatasetDetails = {
					documentationVersion: dataset.datasetVersion,
				};

				await _httpClient.put(metadataCatalogueLink + `/api/dataModels/${newDatasetVersionId}`, updatedDatasetDetails, { withCredentials: true, timeout: 20000 });
				await _httpClient.put(metadataCatalogueLink + `/api/dataModels/${newDatasetVersionId}/finalise`, null, { withCredentials: true, timeout: 20000 });

				// Adding to DB
				let datasetv2Object = await datasetonboardingUtil.buildv2Object(dataset, newDatasetVersionId);

				let previousDataset = await Data.findOneAndUpdate({ pid: dataset.pid, activeflag: 'active' }, { activeflag: 'archive' });
				let previousCounter = 0;
				let previousDiscourseTopicId = 0;
				if (previousDataset) previousCounter = previousDataset.counter || 0;
				if (previousDataset) previousDiscourseTopicId = previousDataset.discourseTopicId || 0;

				//get technicaldetails and metadataQuality
				let technicalDetails = await datasetonboardingUtil.buildTechnicalDetails(dataset.structuralMetadata);
				let metadataQuality = await datasetonboardingUtil.buildMetadataQuality(dataset, datasetv2Object, dataset.pid);

				// call filterCommercialUsage to determine commericalUse field only pass in v2 a
				let commercialUse = filtersService.computeCommericalUse({}, datasetv2Object);

				updatedDataset = await Data.findOneAndUpdate(
					{ _id: id },
					{
						datasetid: newDatasetVersionId,
						datasetVersion: dataset.datasetVersion,
						name: dataset.questionAnswers['properties/summary/title'] || '',
						description: dataset.questionAnswers['properties/documentation/abstract'] || '',
						activeflag: 'active',
						tags: {
							features: dataset.questionAnswers['properties/summary/keywords'] || [],
						},
						commercialUse,
						hasTechnicalDetails: !isEmpty(technicalDetails) ? true : false,
						'timestamps.updated': Date.now(),
						'timestamps.published': Date.now(),
						counter: previousCounter,
						datasetfields: {
							publisher: `${publisherData[0].publisherDetails.memberOf} > ${publisherData[0].publisherDetails.name}`,
							geographicCoverage: dataset.questionAnswers['properties/coverage/spatial'] || [],
							physicalSampleAvailability: dataset.questionAnswers['properties/coverage/physicalSampleAvailability'] || [],
							abstract: dataset.questionAnswers['properties/summary/abstract'] || '',
							releaseDate: dataset.questionAnswers['properties/provenance/temporal/distributionReleaseDate'] || '',
							accessRequestDuration: dataset.questionAnswers['properties/accessibility/access/deliveryLeadTime'] || '',
							datasetStartDate: dataset.questionAnswers['properties/provenance/temporal/startDate'] || '',
							datasetEndDate: dataset.questionAnswers['properties/provenance/temporal/endDate'] || '',
							ageBand: dataset.questionAnswers['properties/coverage/typicalAgeRange'] || '',
							contactPoint: dataset.questionAnswers['properties/summary/contactPoint'] || '',
							periodicity: dataset.questionAnswers['properties/provenance/temporal/accrualPeriodicity'] || '',
							metadataquality: metadataQuality,
							technicaldetails: technicalDetails,
							phenotypes: [],
						},
						datasetv2: datasetv2Object,
						applicationStatusDesc: applicationStatusDesc,
						discourseTopicId: previousDiscourseTopicId,
					},
					{ new: true }
				);

				filtersService.optimiseFilters('dataset');

				let datasetv2DifferenceObject = datasetonboardingUtil.datasetv2ObjectComparison(datasetv2Object, dataset.datasetv2);

				if (!_.isEmpty(datasetv2DifferenceObject)) {
					await activityLogService.logActivity(constants.activityLogEvents.dataset.DATASET_UPDATES_SUBMITTED, {
						type: constants.activityLogTypes.DATASET,
						updatedDataset,
						user: req.user,
						differences: datasetv2DifferenceObject,
					});
				}

				//emails / notifications
				await datasetonboardingUtil.createNotifications(constants.notificationTypes.DATASETAPPROVED, updatedDataset);

				constantActivityLog = constants.activityLogEvents.dataset.DATASET_VERSION_APPROVED;

				await _httpClient.post(metadataCatalogueLink + `/api/authentication/logout`, null, { withCredentials: true, timeout: 5000 });

				break;
			case 'rejected':
				if (userType !== constants.userTypes.ADMIN) {
					return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
				}

				updatedDataset = await Data.findOneAndUpdate(
					{ _id: id },
					{
						activeflag: constants.datatsetStatuses.REJECTED,
						applicationStatusDesc: applicationStatusDesc,
						applicationStatusAuthor: `${firstname} ${lastname}`,
						'timestamps.rejected': Date.now(),
						'timestamps.updated': Date.now(),
					},
					{ new: true }
				);

				//emails / notifications
				await datasetonboardingUtil.createNotifications(constants.notificationTypes.DATASETREJECTED, updatedDataset);

				constantActivityLog = constants.activityLogEvents.dataset.DATASET_VERSION_REJECTED;
			
			  break;
			case 'archive':
				dataset = await Data.findOne({ _id: id }).lean();

				if (dataset.timestamps.submitted) {
					await _httpClient.post(metadataCatalogueLink + `/api/authentication/logout`, null, { withCredentials: true, timeout: 5000 });

					const responseLogin = await _httpClient.post(metadataCatalogueLink + '/api/authentication/login', loginDetails, { withCredentials: true, timeout: 5000 });
					const [cookie] = responseLogin.headers["set-cookie"];
					_httpClient.setHttpClientCookies(cookie);

					await _httpClient.delete(metadataCatalogueLink + `/api/dataModels/${dataset.datasetid}`, loginDetails, { withCredentials: true, timeout: 5000 });

					await _httpClient.post(metadataCatalogueLink + `/api/authentication/logout`, null, { withCredentials: true, timeout: 5000 });
				}
				updatedDataset = await Data.findOneAndUpdate(
					{ _id: id },
					{ activeflag: constants.datatsetStatuses.ARCHIVE, 'timestamps.updated': Date.now(), 'timestamps.archived': Date.now() }
				);

				constantActivityLog = constants.activityLogEvents.dataset.DATASET_VERSION_ARCHIVED;
			
				break;
			case 'unarchive':
				dataset = await Data.findOne({ _id: id }).lean();
				let flagIs = 'draft';
				if (dataset.timestamps.submitted) {
					await _httpClient.post(metadataCatalogueLink + `/api/authentication/logout`, null, { withCredentials: true, timeout: 5000 });

					const responseLogin = await _httpClient.post(metadataCatalogueLink + '/api/authentication/login', loginDetails, { withCredentials: true, timeout: 5000 });
					const [cookie] = responseLogin.headers["set-cookie"];
					_httpClient.setHttpClientCookies(cookie);

					const updatedDatasetDetails = {
						deleted: 'false',
					};

					await _httpClient.put(metadataCatalogueLink + metadataCatalogueLink + `/api/dataModels/${dataset.datasetid}`, updatedDatasetDetails, { withCredentials: true, timeout: 5000 });

					await _httpClient.post(metadataCatalogueLink + `/api/authentication/logout`, null, { withCredentials: true, timeout: 5000 });

					flagIs = 'active';
				}
				updatedDataset = await Data.findOneAndUpdate({ _id: id }, { activeflag: flagIs }); //active or draft

				constantActivityLog = constants.activityLogEvents.dataset.DATASET_VERSION_UNARCHIVED;

				break;
			default:
				res.status(500).json({
					status: 'error',
					message: 'An error occurred - application status is not set correctly',
				});
		}

		await activityLogService.logActivity(constantActivityLog, {
			type: constants.activityLogTypes.DATASET,
			updatedDataset,
			user: req.user,
		});

		return res.status(200).json({ status: 'success' });

	},

	//GET api/v1/dataset-onboarding/checkUniqueTitle
	checkUniqueTitle: async (req, res) => {
		let { pid, title = '' } = req.query;
		let regex = new RegExp(`^${escapeRegExp(title)}$`, 'i');
		let dataset = await Data.findOne({ name: regex, pid: { $ne: pid } });
		return res.status(200).json({ isUniqueTitle: dataset ? false : true });
	},

	//GET api/v1/dataset-onboarding/metaddataQuality
	getMetadataQuality: async (req, res) => {
		try {
			let { pid = '', datasetID = '', recalculate = false } = req.query;

			let dataset = {};

			if (!isEmpty(pid)) {
				dataset = await Data.findOne({ pid: { $eq: pid }, activeflag: 'active' }).lean();
				if (!isEmpty(datasetID)) dataset = await Data.findOne({ pid: { $eq: datasetID }, activeflag: 'archive' }).sort({ createdAt: -1 });
			} else if (!isEmpty(datasetID)) dataset = await Data.findOne({ datasetid: { datasetID } }).lean();

			if (isEmpty(dataset)) return res.status(404).json({ status: 'error', message: 'Dataset could not be found.' });

			let metaddataQuality = {};

			if (recalculate) {
				metaddataQuality = await datasetonboardingUtil.buildMetadataQuality(dataset, dataset.datasetv2, dataset.pid);
				await Data.findOneAndUpdate({ _id: dataset._id }, { 'datasetfields.metadataquality': metaddataQuality });
			} else {
				metaddataQuality = dataset.datasetfields.metadataquality;
			}

			return res.status(200).json({ metaddataQuality });
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	//DELETE api/v1/dataset-onboarding/delete/:id
	deleteDraftDataset: async (req, res) => {
		try {
			let id = req.params.id;

			//Check user type and authentication to submit application
			let { authorised } = await datasetonboardingUtil.getUserPermissionsForDataset(id, req.user);
			if (!authorised) {
				return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
			}

			let dataset = await Data.findOneAndRemove({ _id: id, activeflag: 'draft' });
			let draftDatasetName = dataset.name;

			await datasetonboardingUtil.createNotifications(constants.notificationTypes.DRAFTDATASETDELETED, dataset);

			return res.status(200).json({
				success: true,
				data: draftDatasetName,
			});
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},

	//POST /api/v1/dataset-onboarding/bulk-upload
	bulkUpload: async (req, res) => {
		try {
			let key = req.body.key;
			// Check for key
			if (!key) {
				return res.status(400).json({ success: false, error: 'Bulk upload of metadata could not be started' });
			}
			// Check that key matches
			if (key !== process.env.METADATA_BULKUPLOAD_KEY) {
				return res.status(400).json({ success: false, error: 'Bulk upload of metadata could not be started' });
			}

			//Check for file
			if (isEmpty(req.file)) {
				return res.status(404).json({ success: false, message: 'For bulk upload of metadata you must supply a JSON file' });
			}

			let arrayOfDraftDatasets = [];
			try {
				arrayOfDraftDatasets = JSON.parse(req.file.buffer);
			} catch {
				return res.status(400).json({ success: false, message: 'Unable to read JSON file' });
			}

			if (!isEmpty(arrayOfDraftDatasets)) {
				//Build bulk upload object
				const resultObject = await datasetonboardingUtil.buildBulkUploadObject(arrayOfDraftDatasets);

				if (resultObject.result === true) {
					for (let dataset of resultObject.datasets) {
						//Build publisher object
						let publisherObject = {
							summary: {
								publisher: {
									identifier: dataset.publisher._id.toString(),
									name: dataset.publisher.publisherDetails.name,
									memberOf: dataset.publisher.publisherDetails.memberOf,
								},
							},
						};

						//Create new pid if needed
						if (isEmpty(dataset.pid)) {
							while (dataset.pid === '') {
								dataset.pid = uuidv4();
								if ((await Data.find({ pid: dataset.pid }).length) === 0) dataset.pid = '';
							}
						}

						//Create new uniqueID
						let uniqueID = '';
						while (uniqueID === '') {
							uniqueID = parseInt(Math.random().toString().replace('0.', ''));
							if ((await Data.find({ id: uniqueID }).length) === 0) uniqueID = '';
						}

						//Create DB entry
						let data = new Data();
						data.pid = dataset.pid;
						data.datasetVersion = dataset.version || '1.0.0';
						data.id = uniqueID;
						data.datasetid = 'New dataset';
						data.name = dataset.title;
						data.datasetv2 = publisherObject;
						data.type = 'dataset';
						data.activeflag = 'draft';
						data.source = 'HDRUK MDC';
						data.is5Safes = dataset.publisher.uses5Safes;
						data.timestamps.created = Date.now();
						data.timestamps.updated = Date.now();
						data.questionAnswers = JSON.stringify(dataset.questionAnswers);
						data.structuralMetadata = [...dataset.structuralMetadata];
						await data.save();
					}
					return res.status(200).json({ success: true, message: 'Bulk upload of metadata completed' });
				} else {
					return res.status(400).json({ success: false, message: 'Bulk upload of metadata failed', error: resultObject.error });
				}
			} else {
				return res.status(400).json({ success: false, message: 'No metadata found' });
			}
		} catch (err) {
			if (readEnv === 'test' || readEnv === 'prod') {
				Sentry.captureException(err);
			}
			console.error(err.message);
			return res.status(500).json({ success: false, message: 'Bulk upload of metadata failed', error: err.message });
		}
	},
  
	//POST api/v1/dataset-onboarding/duplicate/:id
	duplicateDataset: async (req, res) => {
		try {
			let id = req.params.id;

			//Check user type and authentication to submit application
			let { authorised } = await datasetonboardingUtil.getUserPermissionsForDataset(id, req.user);
			if (!authorised) {
				return res.status(401).json({ status: 'failure', message: 'Unauthorised' });
			}

			let dataset = await Data.findOne({ _id: id });
			let datasetCopy = JSON.parse(JSON.stringify(dataset));
			let duplicateText = '-duplicate';

			delete datasetCopy._id;
			datasetCopy.pid = uuidv4();

			let parsedQuestionAnswers = JSON.parse(datasetCopy.questionAnswers);
			parsedQuestionAnswers['properties/summary/title'] += duplicateText;

			datasetCopy.name += duplicateText;
			datasetCopy.activeflag = 'draft';
			datasetCopy.datasetVersion = '1.0.0';
			datasetCopy.questionAnswers = JSON.stringify(parsedQuestionAnswers);
			if (datasetCopy.datasetv2.summary.title) {
				datasetCopy.datasetv2.summary.title += duplicateText;
			}

			await Data.create(datasetCopy);

			await datasetonboardingUtil.createNotifications(constants.notificationTypes.DATASETDUPLICATED, dataset);

			return res.status(200).json({
				success: true,
				datasetName: dataset.name,
			});
		} catch (err) {
			console.error(err.message);
			res.status(500).json({ status: 'error', message: err.message });
		}
	},
};

/* Sentry.addBreadcrumb({
				category: 'Bulk Upload',
				message: 'Unable to get metadata quality value ' + err.message,
				level: Sentry.Severity.Error,
			});
			Sentry.captureException(err); */
