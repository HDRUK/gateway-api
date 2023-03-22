import Repository from '../base/repository';
import { DataRequestModel } from './datarequest.model';
import { DataRequestSchemaModel } from './schema/datarequest.schemas.model';
import { TopicModel } from '../topic/topic.model';
import { Data as ToolModel } from '../tool/data.model';
import constants from '../utilities/constants.util';
const { ObjectId } = require('mongodb');

export default class DataRequestRepository extends Repository {
	constructor() {
		super(DataRequestModel);
		this.dataRequestModel = DataRequestModel;
	}

	getAccessRequestsByUser(userId, query) {
		if (!userId) return [];

		return DataRequestModel.find({
			$and: [{ ...query }, { $or: [{ userId }, { authorIds: userId }] }],
		})
			.select('-jsonSchema -files')
			.populate([{ path: 'mainApplicant', select: 'firstname lastname -id' }, { path: 'datasets' }])
			.lean();
	}

	getApplicationById(id) {
		return DataRequestModel.findOne({
			_id: id,
		})
			.populate([
				{ path: 'mainApplicant', select: 'firstname lastname -id' },
				{
					path: 'publisherObj',
					populate: {
						path: 'team',
					},
				},
				{
					path: 'datasets dataset',
					populate: { path: 'publisher', populate: { path: 'team' } },
				},
				{ path: 'authors', select: 'firstname lastname -id' },
				{ path: 'workflow.steps.reviewers', select: 'firstname lastname' },
				{ path: 'files.owner', select: 'firstname lastname' },
			])
			.lean();
	}

	getApplicationByDatasets(datasetIds, applicationStatus, userId) {
		return DataRequestModel.findOne({
			datasetIds: { $all: datasetIds },
			userId,
			applicationStatus,
		})
			.populate([
				{
					path: 'mainApplicant',
					select: 'firstname lastname -id -_id',
				},
				{ path: 'files.owner', select: 'firstname lastname' },
			])
			.sort({ createdAt: -1 })
			.lean();
	}

	getApplicationWithTeamById(id, options = {}) {
		return DataRequestModel.findOne({ _id: { $eq: id } }, null, options).populate([
			//lgtm [js/sql-injection]
			{
				path: 'datasets dataset authors',
			},
			{
				path: 'mainApplicant',
			},
			{
				path: 'publisherObj',
				populate: {
					path: 'team',
					populate: {
						path: 'users',
					},
				},
			},
		]);
	}

	getApplicationWithWorkflowById(id, options = {}) {
		return DataRequestModel.findOne({ _id: id }, null, options).populate([
			{
				path: 'publisherObj',
				populate: {
					path: 'team',
					populate: {
						path: 'users',
					},
				},
			},
			{
				path: 'workflow.steps.reviewers',
				select: 'firstname lastname id email',
			},
			{
				path: 'datasets dataset',
			},
			{
				path: 'mainApplicant authors',
			},
		]);
	}

	getApplicationToSubmitById(id) {
		return DataRequestModel.findOne({ _id: id }).populate([
			{
				path: 'datasets dataset initialDatasets',
				populate: {
					path: 'publisher',
					populate: {
						path: 'team',
						populate: {
							path: 'users',
							populate: {
								path: 'additionalInfo',
							},
						},
					},
				},
			},
			{
				path: 'mainApplicant authors',
				populate: {
					path: 'additionalInfo',
				},
			},
			{
				path: 'publisherObj',
			},
		]);
	}

	getApplicationToUpdateById(id) {
		return DataRequestModel.findOne({
			_id: id,
		}).lean();
	}

	getFilesForApplicationById(id) {
		return DataRequestModel.findOne({
			_id: id,
		}).populate([
			{
				path: 'publisherObj',
				populate: {
					path: 'team',
				},
			},
			{
				path: 'datasets dataset authors',
				populate: { path: 'publisher', populate: { path: 'team' } },
			},
		]);
	}

	getApplicationFormSchema(publisher) {
		return DataRequestSchemaModel.findOne({
			$or: [{ publisher }, { dataSetId: 'default' }],
			status: 'active',
		}).sort({ createdAt: -1 });
	}

	getApplicationFormSchemas(publisher) {
		return DataRequestSchemaModel.find({ publisher: publisher.name }).sort({ version: -1 });
	}

	createApplicationFormSchema(newSchema) {
		const newSchemaModel = new DataRequestSchemaModel(newSchema);
		return DataRequestSchemaModel.create(newSchemaModel);
	}

	updateApplicationFormSchemaById(id, data, options = {}) {
		return DataRequestSchemaModel.findByIdAndUpdate(id, data, { ...options });
	}

	getDatasetsForApplicationByIds(datasetIds) {
		return ToolModel.find({
			datasetid: { $in: datasetIds },
		}).populate('publisher');
	}

	getApplicationForUpdateRequest(id) {
		return DataRequestModel.findOne({ _id: id })
			.select({
				_id: 1,
				publisher: 1,
				amendmentIterations: 1,
				datasetIds: 1,
				dataSetId: 1,
				userId: 1,
				authorIds: 1,
				applicationStatus: 1,
				aboutApplication: 1,
				dateSubmitted: 1,
			})
			.populate([
				{
					path: 'datasets dataset mainApplicant authors',
				},
				{
					path: 'publisherObj',
					select: '_id',
					populate: {
						path: 'team',
						populate: {
							path: 'users',
						},
					},
				},
			]);
	}

	getPermittedUsersForVersions(versionIds) {
		return DataRequestModel.find({ $or: [{ _id: { $in: versionIds } }, { 'amendmentIterations._id': { $in: versionIds } }] })
			.select(
				'userId authorIds publisher majorVersion applicationType applicationStatus dateSubmitted dateFinalStatus amendmentIterations._id amendmentIterations.dateSubmitted amendmentIterations.dateCreated amendmentIterations.dateReturned versionTree aboutApplication.projectName isShared'
			)
			.populate([
				{
					path: 'publisherObj',
					select: '_id',
					populate: {
						path: 'team',
						select: 'members',
						populate: {
							path: 'users',
						},
					},
				},
			]);
	}

	getRelatedPresubmissionTopic(userObjectId, datasetIds) {
		return TopicModel.findOne({
			recipients: userObjectId,
			'datasets.datasetId': { $all: datasetIds },
			linkedDataAccessApplication: { $exists: false },
		})
			.select('_id')
			.populate({ path: 'topicMessages', populate: { path: 'createdBy' } });
	}

	linkRelatedApplicationByMessageContext(topicId, userId, datasetIds, applicationStatus) {
		return DataRequestModel.findOneAndUpdate(
			{
				userId,
				datasetIds: { $all: datasetIds },
				presubmissionTopic: { $exists: false },
				applicationType: constants.submissionTypes.INITIAL,
				...(applicationStatus && { applicationStatus }),
			},
			{ $set: { presubmissionTopic: topicId } },
			{ upsert: false, new: true }
		).select('_id');
	}

	updateApplicationById(id, data, options = {}) {
		return DataRequestModel.findByIdAndUpdate(id, data, { ...options }); //lgtm [js/sql-injection]
	}

	replaceApplicationById(id, newDoc) {
		return DataRequestModel.replaceOne({ _id: id }, newDoc);
	}

	deleteApplicationById(id) {
		return DataRequestModel.findOneAndDelete({ _id: id });
	}

	createApplication(data) {
		return DataRequestModel.create(data);
	}

	async saveFileUploadChanges(accessRecord) {
		await accessRecord.save();
		return DataRequestModel.populate(accessRecord, {
			path: 'files.owner',
			select: 'firstname lastname id',
		});
	}

	async syncRelatedVersions(versionIds, versionTree) {
		const majorVersions = await DataRequestModel.find().where('_id').in(versionIds).select({ versionTree: 1 });

		for (const version of majorVersions) {
			version.versionTree = versionTree;

			await version.save();
		}
	}

	async updateFileStatus(versionIds, fileId, status) {
		const majorVersions = await DataRequestModel.find({ _id: { $in: [versionIds] } }).select({ files: 1 });

		for (const version of majorVersions) {
			const fileIndex = version.files.findIndex(file => file.fileId === fileId);

			if (fileIndex === -1) continue;

			version.files[fileIndex].status = status;

			await version.save();
		}
	}

	getDarContributors(darId) {
		return DataRequestModel.find({ _id: ObjectId(darId) })
			.select('userId authorIds -_id')
			.lean();
	}

	getDarContributorsInfo(id, userId) {
		let additionalInformation = ToolModel.find(
			{ id: id },
			{ id: 1, firstname: 1, lastname: 1, orcid: 1, showOrcid: 1, organisation: 1, showOrganisation: 1 }
		).lean();

		if (userId === id) {
			additionalInformation = additionalInformation.populate({ path: 'user', select: 'email -_id -id' });
		}

		return additionalInformation;
	}
}
