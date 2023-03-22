import { isEmpty, findIndex } from 'lodash';

export default class PublisherService {
	constructor(publisherRepository) {
		this.publisherRepository = publisherRepository;
	}

	async getPublisher(id, options = {}) {
		return this.publisherRepository.getPublisher(id, options);
	}

	getPublishersAndIds() {
		return this.publisherRepository.getPublishersAndIds();
	}

	async getPublisherDatasets(id) {
		const datasets = this.publisherRepository.getPublisherDatasets(id);

		return [...datasets].map(dataset => {
			const {
				_id,
				datasetid: datasetId,
				name,
				description,
				publisher: publisherObj,
				datasetfields: { abstract, publisher, contactPoint },
			} = dataset;
			return {
				_id,
				datasetId,
				name,
				description,
				abstract,
				publisher,
				publisherObj,
				contactPoint,
			};
		});
	}

	async getPublisherDataAccessRequests(id, requestingUserId, isManager) {
		const excludedApplicationStatuses = [];
		if (!isManager) {
			excludedApplicationStatuses.push('submitted');
		}
		const query = { publisher: id, applicationStatus: { $nin: excludedApplicationStatuses } };

		let applications = await this.publisherRepository.getPublisherDataAccessRequests(query);

		applications = this.filterInProgressApplications(applications);

		if (!isManager) {
			applications = this.filterApplicationsForReviewer(applications, requestingUserId);
		}

		return applications;
	}

	filterApplicationsForReviewer(applications, reviewerUserId) {
		const filteredApplications = [...applications].filter(app => {
			let { workflow = {} } = app;
			if (isEmpty(workflow)) {
				return;
			}

			let { steps = [] } = workflow;
			if (isEmpty(steps)) {
				return;
			}

			let activeStepIndex = findIndex(steps, function (step) {
				return step.active === true;
			});

			let elapsedSteps = [...steps].slice(0, activeStepIndex + 1);
			let found = elapsedSteps.some(step => step.reviewers.some(reviewer => reviewer._id.equals(reviewerUserId)));

			if (found) {
				return app;
			}
		});

		return filteredApplications;
	}

	filterInProgressApplications(applications) {
		const filteredApplications = [...applications].filter(app => {
			if (app.applicationStatus !== 'inProgress') return app;

			if (app.isShared) return app;

			return;
		});

		return filteredApplications;
	}

	async updateDataUseWidget(publisherId, content) {
		const publisher = await this.publisherRepository.getPublisher(publisherId);
		const data = { ...publisher.publisherDetails.dataUse.widget, ...content };
		await this.publisherRepository.updateByQuery(
			{ _id: publisherId },
			{
				'publisherDetails.dataUse': {
					widget: {
						...data,
						acceptedDate: Date.now(),
					},
				},
			}
		);
	}

	async update(document, body = {}) {
		return this.publisherRepository.update(document, body);
	}

	async updateDataRequestModalContent(publisherId, requestingUserId, content) {
		await this.publisherRepository.updatePublisher(
			{ _id: publisherId },
			{
				dataRequestModalContentUpdatedOn: Date.now(),
				dataRequestModalContentUpdatedBy: requestingUserId,
				dataRequestModalContent: { header: '', body: content, footer: '' },
			}
		);
	}

	async updateQuestionBank(publisherId, data) {
		await this.publisherRepository.updatePublisher(
			{ _id: publisherId },
			{
				'publisherDetails.questionBank': data,
			}
		);
	}
}
