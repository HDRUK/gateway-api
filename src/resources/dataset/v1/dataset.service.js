import { Data } from '../../tool/data.model';
import { MetricsData } from '../../stats/metrics.model';
import axios from 'axios';
import * as Sentry from '@sentry/node';
import { v4 as uuidv4 } from 'uuid';
import { filtersService } from '../../filters/dependency';
import { PublisherModel } from '../../publisher/publisher.model';
import { metadataCatalogues, validateCatalogueParams } from '../dataset.util';
import { has, isEmpty } from 'lodash';

let metadataQualityList = [],
	phenotypesList = [],
	dataUtilityList = [],
	dataAccessRequestCustodians = [],
	datasetsMDCList = [],
	datasetsMDCIDs = [],
	counter = 0;

const readEnv = process.env.NODE_ENV || 'prod';

export async function updateExternalDatasetServices(services) {
	for (let service of services) {
		if (service === 'phenotype') {
			const phenotypesList = await axios
				.get('https://raw.githubusercontent.com/spiros/hdr-caliber-phenotype-library/master/_data/dataset2phenotypes.json', {
					timeout: 10000,
				})
				.catch(err => {
					if (readEnv === 'test' || readEnv === 'prod') {
						Sentry.addBreadcrumb({
							category: 'Caching',
							message: 'Unable to get metadata quality value ' + err.message,
							level: Sentry.Severity.Error,
						});
						Sentry.captureException(err);
					}
					console.error('Unable to get metadata quality value ' + err.message);
				});

			for (const pid in phenotypesList.data) {
				await Data.updateMany({ pid: pid }, { $set: { 'datasetfields.phenotypes': phenotypesList.data[pid] } });
				console.log(`PID is ${pid} and number of phenotypes is ${phenotypesList.data[pid].length}`);
			}
		} else if (service === 'dataUtility') {
			const dataUtilityList = await axios
				.get('https://raw.githubusercontent.com/HDRUK/datasets/master/reports/data_utility.json', { timeout: 10000 })
				.catch(err => {
					if (readEnv === 'test' || readEnv === 'prod') {
						Sentry.addBreadcrumb({
							category: 'Caching',
							message: 'Unable to get data utility ' + err.message,
							level: Sentry.Severity.Error,
						});
						Sentry.captureException(err);
					}
					console.error('Unable to get data utility ' + err.message);
				});

			for (const dataUtility of dataUtilityList.data) {
				// we only hav dataUtility so will be only checking the score of allowable_uses
				const dataset = await Data.findOne({ datasetid: dataUtility.id });
				if (!isEmpty(dataset)) {
					// deconstruct out datasetv2 if available
					let { datasetv2 = {} } = dataset;
					// set commercial use
					dataset.commercialUse = filtersService.computeCommericalUse(dataUtility, datasetv2);
					// set datautility
					dataset.datasetfields.datautility = dataUtility;
					// save dataset into db
					await dataset.save();
				}
				// log details
				//  console.log(`DatasetID is ${dataUtility.id} and metadata richness is ${dataUtility.metadata_richness}`);
			}
		}
	}
}

/**
 * Import Metadata Catalogues
 *
 * @desc    Performs the import of a given array of catalogues
 * @param 	{Array<String>} 	cataloguesToImport 	The recognised names of each catalogue to import with this request
 * @param 	{Boolean} 			override 			Overriding forces the import of each catalogue requested regardless of differential in datasets
 * @param 	{Number} 			limit 				The maximum number of datasets to import from each catalogue requested
 */
export async function importCatalogues(cataloguesToImport, override = false, limit) {
	dataAccessRequestCustodians = await getDataAccessRequestCustodians();
	for (const catalogue in metadataCatalogues) {
		if (!cataloguesToImport.includes(catalogue)) {
			continue;
		}
		const isValid = validateCatalogueParams(metadataCatalogues[catalogue]);
		if (!isValid) {
			console.error('Catalogue failed to run due to incorrect or incomplete parameters');
			continue;
		}
		const { metadataUrl, dataModelExportRoute, username, password, source, instanceType } = metadataCatalogues[catalogue];
		const options = {
			instanceType,
			credentials: {
				username,
				password,
			},
			override,
			limit,
		};
		initialiseImporter();
		await importMetadataFromCatalogue(metadataUrl, dataModelExportRoute, source, options);
	}
}

export async function saveUptime() {
	const monitoring = require('@google-cloud/monitoring');
	const projectId = 'hdruk-gateway';
	const client = new monitoring.MetricServiceClient();

	var selectedMonthStart = new Date();
	selectedMonthStart.setMonth(selectedMonthStart.getMonth() - 1);
	selectedMonthStart.setDate(1);
	selectedMonthStart.setHours(0, 0, 0, 0);

	var selectedMonthEnd = new Date();
	selectedMonthEnd.setDate(0);
	selectedMonthEnd.setHours(23, 59, 59, 999);

	const request = {
		name: client.projectPath(projectId),
		filter:
			'metric.type="monitoring.googleapis.com/uptime_check/check_passed" AND resource.type="uptime_url" AND metric.label."check_id"="check-production-web-app-qsxe8fXRrBo" AND metric.label."checker_location"="eur-belgium"',

		interval: {
			startTime: {
				seconds: selectedMonthStart.getTime() / 1000,
			},
			endTime: {
				seconds: selectedMonthEnd.getTime() / 1000,
			},
		},
		aggregation: {
			alignmentPeriod: {
				seconds: '86400s',
			},
			crossSeriesReducer: 'REDUCE_NONE',
			groupByFields: ['metric.label."checker_location"', 'resource.label."instance_id"'],
			perSeriesAligner: 'ALIGN_FRACTION_TRUE',
		},
	};

	// Writes time series data
	const [timeSeries] = await client.listTimeSeries(request);
	var dailyUptime = [];
	var averageUptime;

	timeSeries.forEach(data => {
		data.points.forEach(data => {
			dailyUptime.push(data.value.doubleValue);
		});

		averageUptime = (dailyUptime.reduce((a, b) => a + b, 0) / dailyUptime.length) * 100;
	});

	var metricsData = new MetricsData();
	metricsData.uptime = averageUptime;
	await metricsData.save();
}

/**
 * Initialise Importer Instance
 *
 * @desc    Resets the importer module scoped variables to original values for next run
 */
function initialiseImporter() {
	metadataQualityList = [];
	phenotypesList = [];
	dataUtilityList = [];
	datasetsMDCList = [];
	datasetsMDCIDs = [];
	counter = 0;
}

async function importMetadataFromCatalogue(baseUri, dataModelExportRoute, source, { instanceType, credentials, override = false, limit }) {
	const startCacheTime = Date.now();
	console.log(
		`Starting metadata import for ${source} on ${instanceType} at ${Date()} with base URI ${baseUri}, override:${override}, limit:${
			limit || 'all'
		}`
	);
	datasetsMDCList = await getDataModels(baseUri);
	if (datasetsMDCList === 'Update failed') return;

	const isDifferentialValid = await checkDifferentialValid(datasetsMDCList.count, source, override);
	if (!isDifferentialValid) return;

	metadataQualityList = await getMetadataQualityExport();
	phenotypesList = await getPhenotypesExport();
	dataUtilityList = await getDataUtilityExport();

	await logoutCatalogue(baseUri);
	await loginCatalogue(baseUri, credentials);
	await loadDatasets(baseUri, dataModelExportRoute, datasetsMDCList.items, datasetsMDCList.count, source, limit).catch(err => {
		if (readEnv === 'test' || readEnv === 'prod') {
			Sentry.addBreadcrumb({
				category: 'Caching',
				message: `Unable to complete the metadata import for ${source} ${err.message}`,
				level: Sentry.Severity.Error,
			});
			Sentry.captureException(err);
		}
		console.error(`Unable to complete the metadata import for ${source} ${err.message}`);
	});
	await logoutCatalogue(baseUri);
	await archiveMissingDatasets(source);

	const totalCacheTime = ((Date.now() - startCacheTime) / 1000).toFixed(3);
	console.log(`Run Completed for ${source} at ${Date()} - Run took ${totalCacheTime}s`);
}

async function loadDatasets(baseUri, dataModelExportRoute, datasetsToImport, datasetsToImportCount, source, limit) {
	if (limit) {
		datasetsToImport = [...datasetsToImport.slice(0, limit)];
		datasetsToImportCount = datasetsToImport.length;
	}
	for (const datasetMDC of datasetsToImport) {
		counter++;
		console.log(`Starting ${counter} of ${datasetsToImportCount} datasets (${datasetMDC.id})`);

		let datasetHDR = await Data.findOne({ datasetid: datasetMDC.id });
		datasetsMDCIDs.push({ datasetid: datasetMDC.id });

		const metadataQuality = metadataQualityList.data.find(x => x.id === datasetMDC.id);
		const dataUtility = dataUtilityList.data.find(x => x.id === datasetMDC.id) || {};
		const phenotypes = phenotypesList.data[datasetMDC.id] || [];

		const startImportTime = Date.now();

		const exportUri = `${baseUri}${dataModelExportRoute}`.replace('@datasetid@', datasetMDC.id);
		const datasetMDCJSON = await axios
			.get(exportUri, {
				timeout: 60000,
			})
			.catch(err => {
				if (readEnv === 'test' || readEnv === 'prod') {
					Sentry.addBreadcrumb({
						category: 'Caching',
						message: 'Unable to get dataset JSON ' + err.message,
						level: Sentry.Severity.Error,
					});
					Sentry.captureException(err);
				}
				console.error('Unable to get metadata JSON ' + err.message);
			});

		const elapsedTime = ((Date.now() - startImportTime) / 1000).toFixed(3);
		console.log(`Time taken to import JSON  ${elapsedTime} (${datasetMDC.id})`);

		const metadataSchemaCall = axios //Paul - Remove and populate gateway side
			.get(`${baseUri}/api/profiles/uk.ac.hdrukgateway/HdrUkProfilePluginService/schema.org/${datasetMDC.id}`, {
				timeout: 10000,
			})
			.catch(err => {
				if (readEnv === 'test' || readEnv === 'prod') {
					Sentry.addBreadcrumb({
						category: 'Caching',
						message: 'Unable to get metadata schema ' + err.message,
						level: Sentry.Severity.Error,
					});
					Sentry.captureException(err);
				}
				console.error('Unable to get metadata schema ' + err.message);
			});

		const versionLinksCall = axios.get(`${baseUri}/api/catalogueItems/${datasetMDC.id}/semanticLinks`, { timeout: 10000 }).catch(err => {
			if (readEnv === 'test' || readEnv === 'prod') {
				Sentry.addBreadcrumb({
					category: 'Caching',
					message: 'Unable to get version links ' + err.message,
					level: Sentry.Severity.Error,
				});
				Sentry.captureException(err);
			}
			console.error('Unable to get version links ' + err.message);
		});

		const [metadataSchema, versionLinks] = await axios.all([metadataSchemaCall, versionLinksCall]);

		let datasetv1Object = {},
			datasetv2Object = {};
		if (has(datasetMDCJSON, 'data.dataModel.metadata')) {
			datasetv1Object = populateV1datasetObject(datasetMDCJSON.data.dataModel.metadata);
			datasetv2Object = populateV2datasetObject(datasetMDCJSON.data.dataModel.metadata);
		}

		// Get technical details data classes
		let technicaldetails = [];
		if (has(datasetMDCJSON, 'data.dataModel.childDataClasses')) {
			for (const dataClassMDC of datasetMDCJSON.data.dataModel.childDataClasses) {
				if (dataClassMDC.childDataElements) {
					// Map out data class elements to attach to class
					const dataClassElementArray = dataClassMDC.childDataElements.map(element => {
						return {
							domainType: element.domainType,
							label: element.label,
							description: element.description,
							dataType: {
								domainType: element.dataType.domainType,
								label: element.dataType.label,
							},
						};
					});

					// Create class object
					const technicalDetailClass = {
						domainType: dataClassMDC.domainType,
						label: dataClassMDC.label,
						description: dataClassMDC.description,
						elements: dataClassElementArray,
					};

					technicaldetails = [...technicaldetails, technicalDetailClass];
				}
			}
		}

		// Detect if dataset uses 5 Safes form for access
		const is5Safes = dataAccessRequestCustodians.includes(datasetMDC.publisher);
		const hasTechnicalDetails = technicaldetails.length > 0;
		// calculate commercialUse
		const commercialUse = filtersService.computeCommericalUse(dataUtility, datasetv2Object);

		if (datasetHDR) {
			//Edit
			if (!datasetHDR.pid) {
				let uuid = uuidv4();
				let listOfVersions = [];
				datasetHDR.pid = uuid;
				datasetHDR.datasetVersion = '0.0.1';

				if (versionLinks && versionLinks.data && versionLinks.data.items && versionLinks.data.items.length > 0) {
					versionLinks.data.items.forEach(item => {
						if (!listOfVersions.find(x => x.id === item.source.id)) {
							listOfVersions.push({ id: item.source.id, version: item.source.documentationVersion });
						}
						if (!listOfVersions.find(x => x.id === item.target.id)) {
							listOfVersions.push({ id: item.target.id, version: item.target.documentationVersion });
						}
					});

					listOfVersions.forEach(async item => {
						if (item.id !== datasetMDC.id) {
							await Data.findOneAndUpdate({ datasetid: item.id }, { pid: uuid, datasetVersion: item.version });
						} else {
							datasetHDR.pid = uuid;
							datasetHDR.datasetVersion = item.version;
						}
					});
				}
			}

			let keywordArray = splitString(datasetv1Object.keywords);
			let physicalSampleAvailabilityArray = splitString(datasetv1Object.physicalSampleAvailability);
			let geographicCoverageArray = splitString(datasetv1Object.geographicCoverage);

			await Data.findOneAndUpdate(
				{ datasetid: datasetMDC.id },
				{
					pid: datasetHDR.pid,
					datasetVersion: datasetHDR.datasetVersion,
					name: datasetMDC.label,
					description: datasetMDC.description,
					source,
					is5Safes: is5Safes,
					hasTechnicalDetails,
					commercialUse,
					activeflag: 'active',
					license: datasetv1Object.license,
					tags: {
						features: keywordArray,
					},
					datasetfields: {
						publisher: datasetv1Object.publisher,
						geographicCoverage: geographicCoverageArray,
						physicalSampleAvailability: physicalSampleAvailabilityArray,
						abstract: datasetv1Object.abstract,
						releaseDate: datasetv1Object.releaseDate,
						accessRequestDuration: datasetv1Object.accessRequestDuration,
						conformsTo: datasetv1Object.conformsTo,
						accessRights: datasetv1Object.accessRights,
						jurisdiction: datasetv1Object.jurisdiction,
						datasetStartDate: datasetv1Object.datasetStartDate,
						datasetEndDate: datasetv1Object.datasetEndDate,
						statisticalPopulation: datasetv1Object.statisticalPopulation,
						ageBand: datasetv1Object.ageBand,
						contactPoint: datasetv1Object.contactPoint,
						periodicity: datasetv1Object.periodicity,

						metadataquality: metadataQuality ? metadataQuality : {},
						datautility: dataUtility ? dataUtility : {},
						metadataschema: metadataSchema && metadataSchema.data ? metadataSchema.data : {},
						technicaldetails: technicaldetails,
						versionLinks: versionLinks && versionLinks.data && versionLinks.data.items ? versionLinks.data.items : [],
						phenotypes,
					},
					datasetv2: datasetv2Object,
				}
			);
			console.log(`Dataset Editted (${datasetMDC.id})`);
		} else {
			//Add
			let uuid = uuidv4();
			let listOfVersions = [];
			let pid = uuid;
			let datasetVersion = '0.0.1';

			if (versionLinks && versionLinks.data && versionLinks.data.items && versionLinks.data.items.length > 0) {
				versionLinks.data.items.forEach(item => {
					if (!listOfVersions.find(x => x.id === item.source.id)) {
						listOfVersions.push({ id: item.source.id, version: item.source.documentationVersion });
					}
					if (!listOfVersions.find(x => x.id === item.target.id)) {
						listOfVersions.push({ id: item.target.id, version: item.target.documentationVersion });
					}
				});

				for (const item of listOfVersions) {
					if (item.id !== datasetMDC.id) {
						var existingDataset = await Data.findOne({ datasetid: item.id });
						if (existingDataset && existingDataset.pid) pid = existingDataset.pid;
						else {
							await Data.findOneAndUpdate({ datasetid: item.id }, { pid: uuid, datasetVersion: item.version });
						}
					} else {
						datasetVersion = item.version;
					}
				}
			}

			let uniqueID = '';
			while (uniqueID === '') {
				uniqueID = parseInt(Math.random().toString().replace('0.', ''));
				if ((await Data.find({ id: uniqueID }).length) === 0) {
					uniqueID = '';
				}
			}

			let { keywords = '', physicalSampleAvailability = '', geographicCoverage = '' } = datasetv1Object;
			let keywordArray = splitString(keywords);
			let physicalSampleAvailabilityArray = splitString(physicalSampleAvailability);
			let geographicCoverageArray = splitString(geographicCoverage);

			let data = new Data();
			data.pid = pid;
			data.datasetVersion = datasetVersion;
			data.id = uniqueID;
			data.datasetid = datasetMDC.id;
			data.type = 'dataset';
			data.activeflag = 'active';
			data.source = source;
			data.is5Safes = is5Safes;
			data.hasTechnicalDetails = hasTechnicalDetails;
			data.commercialUse = commercialUse;

			data.name = datasetMDC.label;
			data.description = datasetMDC.description;
			data.license = datasetv1Object.license;
			data.tags.features = keywordArray;
			data.datasetfields.publisher = datasetv1Object.publisher;
			data.datasetfields.geographicCoverage = geographicCoverageArray;
			data.datasetfields.physicalSampleAvailability = physicalSampleAvailabilityArray;
			data.datasetfields.abstract = datasetv1Object.abstract;
			data.datasetfields.releaseDate = datasetv1Object.releaseDate;
			data.datasetfields.accessRequestDuration = datasetv1Object.accessRequestDuration;
			data.datasetfields.conformsTo = datasetv1Object.conformsTo;
			data.datasetfields.accessRights = datasetv1Object.accessRights;
			data.datasetfields.jurisdiction = datasetv1Object.jurisdiction;
			data.datasetfields.datasetStartDate = datasetv1Object.datasetStartDate;
			data.datasetfields.datasetEndDate = datasetv1Object.datasetEndDate;
			data.datasetfields.statisticalPopulation = datasetv1Object.statisticalPopulation;
			data.datasetfields.ageBand = datasetv1Object.ageBand;
			data.datasetfields.contactPoint = datasetv1Object.contactPoint;
			data.datasetfields.periodicity = datasetv1Object.periodicity;

			data.datasetfields.metadataquality = metadataQuality ? metadataQuality : {};
			data.datasetfields.datautility = dataUtility ? dataUtility : {};
			data.datasetfields.metadataschema = metadataSchema && metadataSchema.data ? metadataSchema.data : {};
			data.datasetfields.technicaldetails = technicaldetails;
			data.datasetfields.versionLinks = versionLinks && versionLinks.data && versionLinks.data.items ? versionLinks.data.items : [];
			data.datasetfields.phenotypes = phenotypes;
			data.datasetv2 = datasetv2Object;
			await data.save();
			console.log(`Dataset Added (${datasetMDC.id})`);
		}

		console.log(`Finished ${counter} of ${datasetsToImportCount} datasets (${datasetMDC.id})`);
	}
}

/**
 * Get Data Utility Export
 *
 * @desc    Gets a JSON extract from GitHub containing all HDRUK Data Utility data
 * @returns {Array<Object>} JSON response from HDRUK GitHub
 */
async function getDataUtilityExport() {
	return await axios
		.get('https://raw.githubusercontent.com/HDRUK/datasets/master/reports/data_utility.json', { timeout: 10000 })
		.catch(err => {
			if (readEnv === 'test' || readEnv === 'prod') {
				Sentry.addBreadcrumb({
					category: 'Caching',
					message: 'Unable to get data utility ' + err.message,
					level: Sentry.Severity.Error,
				});
				Sentry.captureException(err);
			}
			console.error('Unable to get data utility ' + err.message);
		});
}

/**
 * Get Phenotypes Export
 *
 * @desc    Gets a JSON extract from GitHub containing all HDRUK recognised Phenotypes
 * @returns {Array<Object>} Json response from HDRUK GitHub
 */
async function getPhenotypesExport() {
	return await axios
		.get('https://raw.githubusercontent.com/spiros/hdr-caliber-phenome-portal/master/_data/dataset2phenotypes.json', { timeout: 10000 })
		.catch(err => {
			if (readEnv === 'test' || readEnv === 'prod') {
				Sentry.addBreadcrumb({
					category: 'Caching',
					message: 'Unable to get metadata quality value ' + err.message,
					level: Sentry.Severity.Error,
				});
				Sentry.captureException(err);
			}
			console.error('Unable to get metadata quality value ' + err.message);
		});
}

/**
 * Get Metadata Quality Export
 *
 * @desc    Gets a JSON extract from GitHub containing all HDRUK dataset Metadata Quality
 * @returns {Array<Object>} Json response from HDRUK GitHub
 */
async function getMetadataQualityExport() {
	return await axios
		.get('https://raw.githubusercontent.com/HDRUK/datasets/master/reports/metadata_quality.json', { timeout: 10000 })
		.catch(err => {
			if (readEnv === 'test' || readEnv === 'prod') {
				Sentry.addBreadcrumb({
					category: 'Caching',
					message: 'Unable to get metadata quality value ' + err.message,
					level: Sentry.Severity.Error,
				});
				Sentry.captureException(err);
			}
			console.error('Unable to get metadata quality value ' + err.message);
		});
}

async function getDataModels(baseUri) {
	return await new Promise(function (resolve, reject) {
		axios
			.get(baseUri + '/api/dataModels')
			.then(function (response) {
				resolve(response.data);
			})
			.catch(err => {
				if (readEnv === 'test' || readEnv === 'prod') {
					Sentry.addBreadcrumb({
						category: 'Caching',
						message: 'The caching run has failed because it was unable to get a count from the MDC',
						level: Sentry.Severity.Fatal,
					});
					Sentry.captureException(err);
				}
				reject(err);
			});
	}).catch(() => {
		return 'Update failed';
	});
}

async function checkDifferentialValid(incomingMetadataCount, source, override) {
	// Compare counts from HDR and MDC, if greater drop of 10%+ then stop process and email support queue
	const datasetsHDRCount = await Data.countDocuments({ type: 'dataset', activeflag: 'active', source });

	if ((incomingMetadataCount / datasetsHDRCount) * 100 < 90 && !override) {
		if (readEnv === 'test' || readEnv === 'prod') {
			Sentry.addBreadcrumb({
				category: 'Caching',
				message: `The caching run has failed because the counts from the MDC (${incomingMetadataCount}) where ${
					100 - (incomingMetadataCount / datasetsHDRCount) * 100
				}% lower than the number stored in the DB (${datasetsHDRCount})`,
				level: Sentry.Severity.Fatal,
			});
			Sentry.captureException();
		}
		return false;
	}
	return true;
}

async function getDataAccessRequestCustodians() {
	const publishers = await PublisherModel.find({ allowAccessRequestManagement: true, uses5Safes: true }).select('name').lean();
	return publishers.map(publisher => publisher.name);
}

async function logoutCatalogue(baseUri) {
	await axios.post(`${baseUri}/api/authentication/logout`, { withCredentials: true, timeout: 10000 }).catch(err => {
		console.error(`Error when trying to logout of the MDC - ${err.message}`);
	});
}

async function loginCatalogue(baseUri, credentials) {
	let response = await axios.post(`${baseUri}/api/authentication/login`, credentials, {
		withCredentials: true,
		timeout: 10000,
	});

	axios.defaults.headers.Cookie = response.headers['set-cookie'][0];
}

async function archiveMissingDatasets(source) {
	let datasetsHDRIDs = await Data.aggregate([
		{ $match: { type: 'dataset', activeflag: 'active', source } },
		{ $project: { _id: 0, datasetid: 1 } },
	]);

	let datasetsNotFound = datasetsHDRIDs.filter(o1 => !datasetsMDCIDs.some(o2 => o1.datasetid === o2.datasetid));

	await Promise.all(
		datasetsNotFound.map(async dataset => {
			//Archive
			await Data.findOneAndUpdate(
				{ datasetid: dataset.datasetid },
				{
					activeflag: 'archive',
				}
			);
		})
	);
}

function populateV1datasetObject(v1Data) {
	let datasetV1List = v1Data.filter(item => item.namespace === 'uk.ac.hdrukgateway');
	let datasetv1Object = {};
	if (datasetV1List.length > 0) {
		datasetv1Object = {
			keywords: datasetV1List.find(x => x.key === 'keywords') ? datasetV1List.find(x => x.key === 'keywords').value : '',
			license: datasetV1List.find(x => x.key === 'license') ? datasetV1List.find(x => x.key === 'license').value : '',
			publisher: datasetV1List.find(x => x.key === 'publisher') ? datasetV1List.find(x => x.key === 'publisher').value : '',
			geographicCoverage: datasetV1List.find(x => x.key === 'geographicCoverage')
				? datasetV1List.find(x => x.key === 'geographicCoverage').value
				: '',
			physicalSampleAvailability: datasetV1List.find(x => x.key === 'physicalSampleAvailability')
				? datasetV1List.find(x => x.key === 'physicalSampleAvailability').value
				: '',
			abstract: datasetV1List.find(x => x.key === 'abstract') ? datasetV1List.find(x => x.key === 'abstract').value : '',
			releaseDate: datasetV1List.find(x => x.key === 'releaseDate') ? datasetV1List.find(x => x.key === 'releaseDate').value : '',
			accessRequestDuration: datasetV1List.find(x => x.key === 'accessRequestDuration')
				? datasetV1List.find(x => x.key === 'accessRequestDuration').value
				: '',
			conformsTo: datasetV1List.find(x => x.key === 'conformsTo') ? datasetV1List.find(x => x.key === 'conformsTo').value : '',
			accessRights: datasetV1List.find(x => x.key === 'accessRights') ? datasetV1List.find(x => x.key === 'accessRights').value : '',
			jurisdiction: datasetV1List.find(x => x.key === 'jurisdiction') ? datasetV1List.find(x => x.key === 'jurisdiction').value : '',
			datasetStartDate: datasetV1List.find(x => x.key === 'datasetStartDate')
				? datasetV1List.find(x => x.key === 'datasetStartDate').value
				: '',
			datasetEndDate: datasetV1List.find(x => x.key === 'datasetEndDate') ? datasetV1List.find(x => x.key === 'datasetEndDate').value : '',
			statisticalPopulation: datasetV1List.find(x => x.key === 'statisticalPopulation')
				? datasetV1List.find(x => x.key === 'statisticalPopulation').value
				: '',
			ageBand: datasetV1List.find(x => x.key === 'ageBand') ? datasetV1List.find(x => x.key === 'ageBand').value : '',
			contactPoint: datasetV1List.find(x => x.key === 'contactPoint') ? datasetV1List.find(x => x.key === 'contactPoint').value : '',
			periodicity: datasetV1List.find(x => x.key === 'periodicity') ? datasetV1List.find(x => x.key === 'periodicity').value : '',
		};
	}

	return datasetv1Object;
}

function populateV2datasetObject(v2Data) {
	let datasetV2List = v2Data.filter(item => item.namespace === 'org.healthdatagateway');

	let datasetv2Object = {};
	if (datasetV2List.length > 0) {
		datasetv2Object = {
			identifier: datasetV2List.find(x => x.key === 'properties/identifier')
				? datasetV2List.find(x => x.key === 'properties/identifier').value
				: '',
			version: datasetV2List.find(x => x.key === 'properties/version') ? datasetV2List.find(x => x.key === 'properties/version').value : '',
			issued: datasetV2List.find(x => x.key === 'properties/issued') ? datasetV2List.find(x => x.key === 'properties/issued').value : '',
			modified: datasetV2List.find(x => x.key === 'properties/modified')
				? datasetV2List.find(x => x.key === 'properties/modified').value
				: '',
			revisions: [],
			summary: {
				title: datasetV2List.find(x => x.key === 'properties/summary/title')
					? datasetV2List.find(x => x.key === 'properties/summary/title').value
					: '',
				abstract: datasetV2List.find(x => x.key === 'properties/summary/abstract')
					? datasetV2List.find(x => x.key === 'properties/summary/abstract').value
					: '',
				publisher: {
					identifier: datasetV2List.find(x => x.key === 'properties/summary/publisher/identifier')
						? datasetV2List.find(x => x.key === 'properties/summary/publisher/identifier').value
						: '',
					name: datasetV2List.find(x => x.key === 'properties/summary/publisher/name')
						? datasetV2List.find(x => x.key === 'properties/summary/publisher/name').value
						: '',
					logo: datasetV2List.find(x => x.key === 'properties/summary/publisher/logo')
						? datasetV2List.find(x => x.key === 'properties/summary/publisher/logo').value
						: '',
					description: datasetV2List.find(x => x.key === 'properties/summary/publisher/description')
						? datasetV2List.find(x => x.key === 'properties/summary/publisher/description').value
						: '',
					contactPoint: checkForArray(
						datasetV2List.find(x => x.key === 'properties/summary/publisher/contactPoint')
							? datasetV2List.find(x => x.key === 'properties/summary/publisher/contactPoint').value
							: []
					),
					memberOf: datasetV2List.find(x => x.key === 'properties/summary/publisher/memberOf')
						? datasetV2List.find(x => x.key === 'properties/summary/publisher/memberOf').value
						: '',
					accessRights: checkForArray(
						datasetV2List.find(x => x.key === 'properties/summary/publisher/accessRights')
							? datasetV2List.find(x => x.key === 'properties/summary/publisher/accessRights').value
							: []
					),
					deliveryLeadTime: datasetV2List.find(x => x.key === 'properties/summary/publisher/deliveryLeadTime')
						? datasetV2List.find(x => x.key === 'properties/summary/publisher/deliveryLeadTime').value
						: '',
					accessService: datasetV2List.find(x => x.key === 'properties/summary/publisher/accessService')
						? datasetV2List.find(x => x.key === 'properties/summary/publisher/accessService').value
						: '',
					accessRequestCost: datasetV2List.find(x => x.key === 'properties/summary/publisher/accessRequestCost')
						? datasetV2List.find(x => x.key === 'properties/summary/publisher/accessRequestCost').value
						: '',
					dataUseLimitation: checkForArray(
						datasetV2List.find(x => x.key === 'properties/summary/publisher/dataUseLimitation')
							? datasetV2List.find(x => x.key === 'properties/summary/publisher/dataUseLimitation').value
							: []
					),
					dataUseRequirements: checkForArray(
						datasetV2List.find(x => x.key === 'properties/summary/publisher/dataUseRequirements')
							? datasetV2List.find(x => x.key === 'properties/summary/publisher/dataUseRequirements').value
							: []
					),
				},
				contactPoint: datasetV2List.find(x => x.key === 'properties/summary/contactPoint')
					? datasetV2List.find(x => x.key === 'properties/summary/contactPoint').value
					: '',
				keywords: checkForArray(
					datasetV2List.find(x => x.key === 'properties/summary/keywords')
						? datasetV2List.find(x => x.key === 'properties/summary/keywords').value
						: []
				),
				alternateIdentifiers: checkForArray(
					datasetV2List.find(x => x.key === 'properties/summary/alternateIdentifiers')
						? datasetV2List.find(x => x.key === 'properties/summary/alternateIdentifiers').value
						: []
				),
				doiName: datasetV2List.find(x => x.key === 'properties/summary/doiName')
					? datasetV2List.find(x => x.key === 'properties/summary/doiName').value
					: '',
			},
			documentation: {
				description: datasetV2List.find(x => x.key === 'properties/documentation/description')
					? datasetV2List.find(x => x.key === 'properties/documentation/description').value
					: '',
				associatedMedia: checkForArray(
					datasetV2List.find(x => x.key === 'properties/documentation/associatedMedia')
						? datasetV2List.find(x => x.key === 'properties/documentation/associatedMedia').value
						: []
				),
				isPartOf: checkForArray(
					datasetV2List.find(x => x.key === 'properties/documentation/isPartOf')
						? datasetV2List.find(x => x.key === 'properties/documentation/isPartOf').value
						: []
				),
			},
			coverage: {
				spatial: datasetV2List.find(x => x.key === 'properties/coverage/spatial')
					? datasetV2List.find(x => x.key === 'properties/coverage/spatial').value
					: '',
				typicalAgeRange: datasetV2List.find(x => x.key === 'properties/coverage/typicalAgeRange')
					? datasetV2List.find(x => x.key === 'properties/coverage/typicalAgeRange').value
					: '',
				physicalSampleAvailability: checkForArray(
					datasetV2List.find(x => x.key === 'properties/coverage/physicalSampleAvailability')
						? datasetV2List.find(x => x.key === 'properties/coverage/physicalSampleAvailability').value
						: []
				),
				followup: datasetV2List.find(x => x.key === 'properties/coverage/followup')
					? datasetV2List.find(x => x.key === 'properties/coverage/followup').value
					: '',
				pathway: datasetV2List.find(x => x.key === 'properties/coverage/pathway')
					? datasetV2List.find(x => x.key === 'properties/coverage/pathway').value
					: '',
			},
			provenance: {
				origin: {
					purpose: checkForArray(
						datasetV2List.find(x => x.key === 'properties/provenance/origin/purpose')
							? datasetV2List.find(x => x.key === 'properties/provenance/origin/purpose').value
							: []
					),
					source: checkForArray(
						datasetV2List.find(x => x.key === 'properties/provenance/origin/source')
							? datasetV2List.find(x => x.key === 'properties/provenance/origin/source').value
							: []
					),
					collectionSituation: checkForArray(
						datasetV2List.find(x => x.key === 'properties/provenance/origin/collectionSituation')
							? datasetV2List.find(x => x.key === 'properties/provenance/origin/collectionSituation').value
							: []
					),
				},
				temporal: {
					accrualPeriodicity: datasetV2List.find(x => x.key === 'properties/provenance/temporal/accrualPeriodicity')
						? datasetV2List.find(x => x.key === 'properties/provenance/temporal/accrualPeriodicity').value
						: '',
					distributionReleaseDate: datasetV2List.find(x => x.key === 'properties/provenance/temporal/distributionReleaseDate')
						? datasetV2List.find(x => x.key === 'properties/provenance/temporal/distributionReleaseDate').value
						: '',
					startDate: datasetV2List.find(x => x.key === 'properties/provenance/temporal/startDate')
						? datasetV2List.find(x => x.key === 'properties/provenance/temporal/startDate').value
						: '',
					endDate: datasetV2List.find(x => x.key === 'properties/provenance/temporal/endDate')
						? datasetV2List.find(x => x.key === 'properties/provenance/temporal/endDate').value
						: '',
					timeLag: datasetV2List.find(x => x.key === 'properties/provenance/temporal/timeLag')
						? datasetV2List.find(x => x.key === 'properties/provenance/temporal/timeLag').value
						: '',
				},
			},
			accessibility: {
				usage: {
					dataUseLimitation: checkForArray(
						datasetV2List.find(x => x.key === 'properties/accessibility/usage/dataUseLimitation')
							? datasetV2List.find(x => x.key === 'properties/accessibility/usage/dataUseLimitation').value
							: []
					),
					dataUseRequirements: checkForArray(
						datasetV2List.find(x => x.key === 'properties/accessibility/usage/dataUseRequirements')
							? datasetV2List.find(x => x.key === 'properties/accessibility/usage/dataUseRequirements').value
							: []
					),
					resourceCreator: datasetV2List.find(x => x.key === 'properties/accessibility/usage/resourceCreator')
						? datasetV2List.find(x => x.key === 'properties/accessibility/usage/resourceCreator').value
						: '',
					investigations: checkForArray(
						datasetV2List.find(x => x.key === 'properties/accessibility/usage/investigations')
							? datasetV2List.find(x => x.key === 'properties/accessibility/usage/investigations').value
							: []
					),
					isReferencedBy: checkForArray(
						datasetV2List.find(x => x.key === 'properties/accessibility/usage/isReferencedBy')
							? datasetV2List.find(x => x.key === 'properties/accessibility/usage/isReferencedBy').value
							: []
					),
				},
				access: {
					accessRights: checkForArray(
						datasetV2List.find(x => x.key === 'properties/accessibility/access/accessRights')
							? datasetV2List.find(x => x.key === 'properties/accessibility/access/accessRights').value
							: []
					),
					accessService: datasetV2List.find(x => x.key === 'properties/accessibility/access/accessService')
						? datasetV2List.find(x => x.key === 'properties/accessibility/access/accessService').value
						: '',
					accessRequestCost: datasetV2List.find(x => x.key === 'properties/accessibility/access/accessRequestCost')
						? datasetV2List.find(x => x.key === 'properties/accessibility/access/accessRequestCost').value
						: '',
					deliveryLeadTime: datasetV2List.find(x => x.key === 'properties/accessibility/access/deliveryLeadTime')
						? datasetV2List.find(x => x.key === 'properties/accessibility/access/deliveryLeadTime').value
						: '',
					jurisdiction: checkForArray(
						datasetV2List.find(x => x.key === 'properties/accessibility/access/jurisdiction')
							? datasetV2List.find(x => x.key === 'properties/accessibility/access/jurisdiction').value
							: []
					),
					dataProcessor: datasetV2List.find(x => x.key === 'properties/accessibility/access/dataProcessor')
						? datasetV2List.find(x => x.key === 'properties/accessibility/access/dataProcessor').value
						: '',
					dataController: datasetV2List.find(x => x.key === 'properties/accessibility/access/dataController')
						? datasetV2List.find(x => x.key === 'properties/accessibility/access/dataController').value
						: '',
				},
				formatAndStandards: {
					vocabularyEncodingScheme: checkForArray(
						datasetV2List.find(x => x.key === 'properties/accessibility/formatAndStandards/vocabularyEncodingScheme')
							? datasetV2List.find(x => x.key === 'properties/accessibility/formatAndStandards/vocabularyEncodingScheme').value
							: []
					),
					conformsTo: checkForArray(
						datasetV2List.find(x => x.key === 'properties/accessibility/formatAndStandards/conformsTo')
							? datasetV2List.find(x => x.key === 'properties/accessibility/formatAndStandards/conformsTo').value
							: []
					),
					language: checkForArray(
						datasetV2List.find(x => x.key === 'properties/accessibility/formatAndStandards/language')
							? datasetV2List.find(x => x.key === 'properties/accessibility/formatAndStandards/language').value
							: []
					),
					format: checkForArray(
						datasetV2List.find(x => x.key === 'properties/accessibility/formatAndStandards/format')
							? datasetV2List.find(x => x.key === 'properties/accessibility/formatAndStandards/format').value
							: []
					),
				},
			},
			enrichmentAndLinkage: {
				qualifiedRelation: checkForArray(
					datasetV2List.find(x => x.key === 'properties/enrichmentAndLinkage/qualifiedRelation')
						? datasetV2List.find(x => x.key === 'properties/enrichmentAndLinkage/qualifiedRelation').value
						: []
				),
				derivation: checkForArray(
					datasetV2List.find(x => x.key === 'properties/enrichmentAndLinkage/derivation')
						? datasetV2List.find(x => x.key === 'properties/enrichmentAndLinkage/derivation').value
						: []
				),
				tools: checkForArray(
					datasetV2List.find(x => x.key === 'properties/enrichmentAndLinkage/tools')
						? datasetV2List.find(x => x.key === 'properties/enrichmentAndLinkage/tools').value
						: []
				),
			},
			observations: [],
		};
	}

	return datasetv2Object;
}

function checkForArray(value) {
	if (typeof value !== 'string') return value;
	try {
		const type = Object.prototype.toString.call(JSON.parse(value));
		if (type === '[object Object]' || type === '[object Array]') return JSON.parse(value);
	} catch (err) {
		return value;
	}
}

function splitString(array) {
	var returnArray = [];
	if (array !== null && array !== '' && array !== 'undefined' && array !== undefined) {
		if (array.indexOf(',') === -1) {
			returnArray.push(array.trim());
		} else {
			array.split(',').forEach(term => {
				returnArray.push(term.trim());
			});
		}
	}
	return returnArray;
}
