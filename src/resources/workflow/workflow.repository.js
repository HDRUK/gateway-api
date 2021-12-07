import Repository from '../base/repository';
import { WorkflowModel } from './workflow.model';

export default class WorkflowRepository extends Repository {
	constructor() {
		super(WorkflowModel);
		this.workflowModel = WorkflowModel;
	}

	getWorkflowsByPublisher(id) {
		return WorkflowModel.find({
			publisher: id,
		})
			.populate([
				{
					path: 'publisher',
					select: 'team',
					populate: {
						path: 'team',
						select: 'members -_id',
					},
				},
				{
					path: 'steps.reviewers',
					model: 'User',
					select: '_id id firstname lastname',
				},
				{
					path: 'applications',
					select: 'aboutApplication',
					match: { applicationStatus: 'inReview' },
				},
			])
			.lean();
	}

	getWorkflowById(id, options = {}) {
		return WorkflowModel.findOne(
			{
				_id: { $eq: id },
			},
			null,
			options
		)
			.populate([
				{
					path: 'steps.reviewers',
					model: 'User',
					select: '_id id firstname lastname email',
				},
			])
			.lean();
	}

	async assignWorkflowToApplication(accessRecord, workflowId) {
		// Retrieve workflow using ID from database
		const workflow = await this.getWorkflowById(workflowId, { lean: false });
		if (!workflow) {
			throw new Error('Workflow could not be found');
		}
		// Set first workflow step active and ensure all others are false
		const workflowObj = workflow;
		workflowObj.steps = workflowObj.steps.map(step => {
			return { ...step, active: false };
		});
		workflowObj.steps[0].active = true;
		workflowObj.steps[0].startDateTime = new Date();
		// Update application with attached workflow
		accessRecord.workflowId = workflowId;
		accessRecord.workflow = workflowObj;
		await accessRecord.save();

		return accessRecord;
	}
}
