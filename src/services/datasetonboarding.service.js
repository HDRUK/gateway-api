import _ from 'lodash';
import moment from 'moment';
import { v4 as uuidv4 } from 'uuid';

import { Data } from '../resources/tool/data.model';
import constants from '../resources/utilities/constants.util';
import { PublisherModel } from '../resources/publisher/publisher.model';
import datasetonboardingUtil from '../utils/datasetonboarding.util';

export default class DatasetOnboardingService {
	constructor(datasetOnboardingRepository) {
		this.datasetOnboardingRepository = datasetOnboardingRepository;
	}

	getDatasetsByPublisherCounts = async publisherID => {
		const activeflagOptions = Object.values(constants.datasetStatuses);

		let searchQuery = {
			activeflag: {
				$in: activeflagOptions,
			},
			type: 'dataset',
			...(publisherID !== constants.teamTypes.ADMIN && { 'datasetv2.summary.publisher.identifier': publisherID }),
		};

		const allPublishersDatasetVersions = await Data.find(searchQuery)
			.select(
				'_id pid name datasetVersion activeflag timestamps applicationStatusDesc applicationStatusAuthor percentageCompleted datasetv2.summary.publisher.name counter'
			)
			.sort({ 'timestamps.updated': -1 })
			.lean();

		const allPublisherDatasets = await this.versioningHelper(allPublishersDatasetVersions);

		let totalCounts = {
			inReview: 0,
			active: 0,
			rejected: 0,
			draft: 0,
			archive: 0,
		};

		activeflagOptions.forEach(activeflag => {
			totalCounts[activeflag] = allPublisherDatasets.filter(dataset => dataset.activeflag === activeflag).length;
		});

		if (publisherID === constants.teamTypes.ADMIN) {
			delete totalCounts.active;
			delete totalCounts.rejected;
			delete totalCounts.draft;
			delete totalCounts.archive;
		}

		return totalCounts;
	};

	getDatasetsByPublisher = async (status, publisherID, sortBy, sortDirection, search) => {
		const activeflagOptions = Object.values(constants.datasetStatuses);

		let searchQuery = {
			activeflag: {
				$in: activeflagOptions,
			},
			type: 'dataset',
			...(publisherID !== constants.teamTypes.ADMIN && { 'datasetv2.summary.publisher.identifier': publisherID }),
		};

		if (search.length > 0)
			searchQuery['$or'] = [
				{ name: { $regex: search, $options: 'i' } },
				{ 'datasetv2.summary.publisher.name': { $regex: search, $options: 'i' } },
				{ 'datasetv2.summary.abstract': { $regex: search, $options: 'i' } },
			];

		const datasets = await Data.find(searchQuery)
			.select(
				'_id pid name datasetVersion activeflag timestamps applicationStatusDesc applicationStatusAuthor percentageCompleted datasetv2.summary.publisher.name counter'
			)
			.sort({ 'timestamps.updated': -1 })
			.lean();

		let versionedDatasets = await this.versioningHelper(datasets);

		if (status) versionedDatasets = versionedDatasets.filter(dataset => dataset.activeflag === status);

		const count = versionedDatasets.length;

		versionedDatasets = await datasetonboardingUtil.datasetSortingHelper(versionedDatasets, sortBy, sortDirection);

		return [versionedDatasets, count];
	};

	getDatasetVersion = async id => {
		let dataset = await Data.findOne({ _id: id });

		if (dataset.questionAnswers) {
			dataset.questionAnswers = JSON.parse(dataset.questionAnswers);
		} else {
			dataset.questionAnswers = datasetonboardingUtil.populateQuestionAnswers(dataset.datasetv2);
			await this.datasetOnboardingRepository.updateByQuery({ _id: id }, { questionAnswers: JSON.stringify(dataset.questionAnswers) });
		}

		if (_.isEmpty(dataset.structuralMetadata)) {
			dataset.structuralMetadata = datasetonboardingUtil.populateStructuralMetadata(dataset.datasetfields.technicaldetails);
			await this.datasetOnboardingRepository.updateByQuery({ _id: id }, { structuralMetadata: dataset.structuralMetadata });
		}

		return dataset;
	};

	createNewDatasetVersion = async (publisherID, pid, currentVersionId) => {
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

		let data = null;
		let error = null;

		if (!pid) {
			[data, error] = await this.initialDatasetVersion(publisherObject, publisherData);
		} else {
			[data, error] = await this.newVersionForExistingDataset(currentVersionId, publisherData, pid);
		}

		return [data, error];
	};

	submitDatasetVersion = async id => {
		let dataset = await Data.findOne({ _id: id });

		dataset.questionAnswers = JSON.parse(dataset.questionAnswers);

		let datasetv2Object = await datasetonboardingUtil.buildv2Object(dataset);

		let updatedDataset = await Data.findOneAndUpdate(
			{ _id: id },
			{
				datasetv2: datasetv2Object,
				activeflag: constants.datasetStatuses.INREVIEW,
				'timestamps.updated': Date.now(),
				'timestamps.submitted': Date.now(),
			}
		);

		return [updatedDataset, dataset];
	};

	checkUniqueTitle = async (regex, pid) => {
		let dataset = await Data.findOne({ name: regex, pid: { $ne: pid } });

		return dataset;
	};

	getMetadataQuality = async (pid, datasetID, recalculate) => {
		let dataset = await Data.findOne({ datasetid: { datasetID } }).lean();

		if (!isEmpty(pid) && isEmpty(datasetID)) {
			dataset = await Data.findOne({ pid: { $eq: pid }, activeflag: constants.datasetStatuses.ACTIVE }).lean();
		}

		if (!isEmpty(pid) && !isEmpty(datasetID)) {
			dataset = await Data.findOne({ pid: { $eq: datasetID }, activeflag: constants.datasetStatuses.ARCHIVE }).sort({ createdAt: -1 });
		}

		if (isEmpty(dataset)) throw new Error('Dataset could not be found.');

		let metadataQuality = dataset.datasetfields.metadataquality;

		if (recalculate) {
			metadataQuality = await datasetonboardingUtil.buildMetadataQuality(dataset, dataset.datasetv2, dataset.pid);
			await Data.findOneAndUpdate({ _id: dataset._id }, { 'datasetfields.metadataquality': metadataQuality });
		}

		return metadataQuality;
	};

	deleteDraftDataset = async id => {
		let dataset = await Data.findOneAndRemove({ _id: id, activeflag: constants.datasetStatuses.DRAFT });
		let draftDatasetName = dataset.name;

		return [dataset, draftDatasetName];
	};

	duplicateDataset = async id => {
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

		return dataset;
	};

	updateDatasetVersionDataElement = async (dataset, updateObj, id) => {
		await datasetonboardingUtil.updateDataset(dataset, updateObj);

		let data = {
			status: 'success',
		};

		if (updateObj.updatedQuestionId === 'properties/summary/title') {
			let questionAnswers = JSON.parse(updateObj.questionAnswers);
			let title = questionAnswers['properties/summary/title'];

			if (title && title.length >= 2) {
				await Data.findByIdAndUpdate({ _id: id }, { name: title, 'timestamps.updated': Date.now() }, { new: true });
				data.name = title;
			}
		}

		return data;
	};

	updateStructuralMetadata = async (structuralMetadata, id) => {
		await Data.findByIdAndUpdate(
			{ _id: id },
			{
				structuralMetadata,
				percentageCompleted,
				'timestamps.updated': Date.now(),
			},
			{ new: true }
		);
	};

	newVersionForExistingDataset = async (currentVersionId, publisherData, pid) => {
		let isDraftDataset = await Data.findOne({ pid, activeflag: 'draft' }, { _id: 1 });

		if (!_.isNil(isDraftDataset)) {
			return [null, 'existingDataset'];
		}

		let datasetToCopy = await Data.findOne({ _id: currentVersionId });

		if (_.isNil(datasetToCopy)) {
			return [null, 'missingVersion'];
		}

		let uniqueID = '';
		while (uniqueID === '') {
			uniqueID = parseInt(Math.random().toString().replace('0.', ''));
			if ((await Data.find({ id: uniqueID }).length) === 0) uniqueID = '';
		}

		let newVersion = datasetonboardingUtil.incrementVersion([1, 0, 0], datasetToCopy.datasetVersion);

		datasetToCopy.questionAnswers = JSON.parse(datasetToCopy.questionAnswers);

		if (!datasetToCopy.questionAnswers['properties/documentation/description'] && datasetToCopy.description) {
			datasetToCopy.questionAnswers['properties/documentation/description'] = datasetToCopy.description;
		}

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

		return [data, null];
	};

	//Create a new version for a new dataset
	initialDatasetVersion = async (publisherObject, publisherData) => {
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

		return [data, null];
	};

	getAssociatedVersions = async pid => {
		let datasets = await Data.find({ pid: pid }, { _id: 1, datasetVersion: 1, activeflag: 1 }).sort({
			'timestamps.created': -1,
		});
		return datasets;
	};

	buildCountObject = (versionedDatasets, publisherID) => {
		const activeflagOptions = Object.values(constants.datasetStatuses);

		let counts = {
			inReview: 0,
			active: 0,
			rejected: 0,
			draft: 0,
			archive: 0,
		};

		activeflagOptions.forEach(activeflag => {
			counts[activeflag] = versionedDatasets.filter(dataset => dataset.activeflag === activeflag).length;
		});

		if (publisherID === constants.teamTypes.ADMIN) {
			delete counts.active;
			delete counts.rejected;
			delete counts.draft;
			delete counts.archive;
		}

		return counts;
	};

	versioningHelper = datasets => {
		let versionedDatasets = datasets.reduce((arr, dataset) => {
			dataset.listOfVersions = [];
			const datasetIdx = arr.findIndex(item => item.pid === dataset.pid);
			if (datasetIdx === -1) {
				arr = [...arr, dataset];
			} else {
				const { _id, datasetVersion, activeflag } = dataset;
				const versionDetails = { _id, datasetVersion, activeflag };
				arr[datasetIdx].listOfVersions = [...arr[datasetIdx].listOfVersions, versionDetails];
			}
			return arr;
		}, []);
		return versionedDatasets;
	};
}
