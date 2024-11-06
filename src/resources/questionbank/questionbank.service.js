import { isEmpty, has } from 'lodash';

export default class QuestionbankService {
	constructor(publisherService, globalService, dataRequestRepository, datasetService) {
		this.publisherService = publisherService;
		this.globalService = globalService;
		this.dataRequestRepository = dataRequestRepository;
		this.datasetService = datasetService;
	}

	async getQuestionBankInfo(publisherId) {
		const publisher = await this.publisherService.getPublisher(publisherId);
		const global = await this.globalService.getMasterSchema({ localeId: 'en-gb' });
		const masterSchema = global.masterSchema;

		let dataRequestSchemas = await this.dataRequestRepository.getApplicationFormSchemas(publisher);

		if (isEmpty(dataRequestSchemas)) {
			let questionStatus = await this.getDefaultQuestionStates();
			let questionSetStatus = await this.getDefaultQuestionSetStates();

			const newSchema = {
				publisher: publisher.name,
				status: 'draft',
				isCloneable: true,
				questionStatus,
				questionSetStatus,
				guidance: {},
				countOfChanges: 0,
				unpublishedGuidance: [],
			};

			const schema = await this.dataRequestRepository.createApplicationFormSchema(newSchema);

			return {
				masterSchema,
				questionStatus: schema.questionStatus,
				questionSetStatus: schema.questionSetStatus,
				guidance: schema.guidance,
				countOfChanges: schema.countOfChanges,
				schemaId: schema._id,
				unpublishedGuidance: schema.unpublishedGuidance,
			};
		}

		const latestSchemaVersion = dataRequestSchemas[0];
		if (latestSchemaVersion.status === 'draft') {
			let newQuestionStatus = latestSchemaVersion.questionStatus;
			let newQuestionSetStatus = latestSchemaVersion.questionSetStatus;

			this.addQuestionsFromMasterSchema(masterSchema, latestSchemaVersion, newQuestionStatus);
			this.addQuestionSetsFromMasterSchema(masterSchema, latestSchemaVersion, newQuestionSetStatus);

			await this.dataRequestRepository.updateApplicationFormSchemaById(latestSchemaVersion._id, {
				questionStatus: newQuestionStatus,
				questionSetStatus: newQuestionSetStatus,
			});

			return {
				masterSchema,
				questionStatus: newQuestionStatus,
				questionSetStatus: newQuestionSetStatus,
				guidance: latestSchemaVersion.guidance,
				countOfChanges: latestSchemaVersion.countOfChanges,
				schemaId: latestSchemaVersion._id,
				unpublishedGuidance: latestSchemaVersion.unpublishedGuidance,
			};
		}

		if (latestSchemaVersion.status === 'active') {
			if (!isEmpty(latestSchemaVersion.questionStatus)) {
				let newQuestionStatus = latestSchemaVersion.questionStatus;
				let newQuestionSetStatus = latestSchemaVersion.questionSetStatus;

				//Add new questions from the master schema if any
				this.addQuestionsFromMasterSchema(masterSchema, latestSchemaVersion, newQuestionStatus);
				this.addQuestionSetsFromMasterSchema(masterSchema, latestSchemaVersion, newQuestionSetStatus);

				const newSchema = {
					publisher: publisher.name,
					status: 'draft',
					isCloneable: true,
					questionStatus: newQuestionStatus,
					questionSetStatus: newQuestionSetStatus,
					guidance: latestSchemaVersion.guidance,
					version: latestSchemaVersion.version + 1,
					countOfChanges: 0,
					unpublishedGuidance: [],
				};

				const schema = await this.dataRequestRepository.createApplicationFormSchema(newSchema);

				return {
					masterSchema,
					questionStatus: newSchema.questionStatus,
					questionSetStatus: newSchema.questionSetStatus,
					guidance: newSchema.guidance,
					countOfChanges: newSchema.countOfChanges,
					schemaId: schema._id,
					unpublishedGuidance: schema.unpublishedGuidance,
				};
			} else {
				let questionStatus = {};
				let questionSetStatus = {};

				//Add questions from the publisher schema
				this.addQuestionsFromPublisherSchema(latestSchemaVersion, questionStatus);

				//Add question from master schema if not in the publisher schema
				this.addQuestionsFromMasterSchema(masterSchema, latestSchemaVersion, questionStatus);
				this.addQuestionSetsFromMasterSchema(masterSchema, latestSchemaVersion, questionSetStatus);

				const newSchema = {
					publisher: publisher.name,
					status: 'draft',
					isCloneable: true,
					questionStatus,
					questionSetStatus,
					guidance: {},
					countOfChanges: 0,
					version: latestSchemaVersion.version + 1,
					unpublishedGuidance: [],
				};

				const schema = await this.dataRequestRepository.createApplicationFormSchema(newSchema);

				return {
					masterSchema,
					questionStatus: newSchema.questionStatus,
					questionSetStatus: newSchema.questionSetStatus,
					guidance: newSchema.guidance,
					countOfChanges: newSchema.countOfChanges,
					schemaId: schema._id,
					unpublishedGuidance: newSchema.unpublishedGuidance,
				};
			}
		}
	}

	async publishSchema(schema, userId) {
		const global = await this.globalService.getMasterSchema({ localeId: 'en-gb' });
		const masterSchema = global.masterSchema;
		const { guidance, questionStatus } = schema;

		masterSchema.questionSets.forEach((questionSet, questionSetIndex) => {
			let questionsArray = masterSchema.questionSets[questionSetIndex].questions;
			questionSet.questions.forEach(question => {
				if (questionStatus[question.questionId] === 0) {
					questionsArray = questionsArray.filter(q => q.questionId !== question.questionId);
				} else {
					if (has(guidance, question.questionId)) {
						question.guidance = guidance[question.questionId];
					}
					delete question.lockedQuestion;
					delete question.defaultQuestion;
				}
			});
			masterSchema.questionSets[questionSetIndex].questions = questionsArray;
		});

		const jsonSchema = masterSchema;

		const publishedSchema = await this.dataRequestRepository.updateApplicationFormSchemaById(schema._id, { jsonSchema, status: 'active' });

		//if its not already a 5 safes publisher then set the flags to true on the publisher and also on the datasets
		const publisher = await this.publisherService.getPublisher(schema.publisher, { lean: true });
		if (!has(publisher, 'uses5Safes') || publisher.uses5Safes === false) {
			await this.publisherService.update(publisher._id, {
				allowsMessaging: true,
				workflowEnabled: true,
				allowAccessRequestManagement: true,
				uses5Safes: true,
			});

			await this.datasetService.updateMany({ 'datasetfields.publisher': schema.publisher }, { is5Safes: true });
		}

		await this.publisherService.update(publisher._id, {
			applicationFormUpdatedOn: Date.now(),
			applicationFormUpdatedBy: userId,
		});

		return publishedSchema;
	}

	addQuestionsFromPublisherSchema(publisherSchema, questionStatus) {
		const jsonSchema = publisherSchema.jsonSchema;
		jsonSchema.questionSets.forEach(questionSet => {
			questionSet.questions.forEach(question => {
				questionStatus[question.questionId] = 1;
			});
		});
	}

	async addQuestionsFromMasterSchema(masterSchema, publisherSchema, questionStatus) {
		masterSchema.questionSets.forEach(questionSet => {
			questionSet.questions.forEach(question => {
				if (!has(publisherSchema.questionStatus, question.questionId)) {
					if (question.lockedQuestion === 1) questionStatus[question.questionId] = 2;
					else questionStatus[question.questionId] = question.defaultQuestion;
				}
			});
		});

		return questionStatus;
	}

	async addQuestionSetsFromMasterSchema(masterSchema, publisherSchema, questionSetStatus) {
		masterSchema.questionSets.forEach(questionSet => {
			if (!has(publisherSchema.questionSetStatus, questionSet.questionSetId)) {
				questionSetStatus[questionSet.questionSetId] = typeof questionSet.active !== 'undefined' ? questionSet.active : 1;
			}
		});

		return questionSetStatus;
	}

	async revertChanges(publisherId, target) {
		const publisher = await this.publisherService.getPublisher(publisherId);
		const dataRequestSchemas = await this.dataRequestRepository.getApplicationFormSchemas(publisher);

		if (dataRequestSchemas.length === 0) {
			throw new Error('This publisher has no data request schemas');
		}

		// Default previous state is the master schema
		let previousQuestionStatus = await this.getDefaultQuestionStates();
		let previousQuestionSetStatus = await this.getDefaultQuestionSetStates();
		let guidance = {};
		let unpublishedGuidance = [];

		// Is previous version exists, previousState is last schema version
		if (dataRequestSchemas.length > 1) {
			previousQuestionStatus = dataRequestSchemas[1].questionStatus;
			previousQuestionSetStatus = dataRequestSchemas[1].questionSetStatus;
			guidance = dataRequestSchemas[1].guidance || {};
		}

		// Revert updates for a given question panel ELSE revert all updates
		let countOfChanges = 0;
		if (target) {
			const panelQuestions = await this.getPanelQuestions(target);
			const updateQuestionStatus = Object.keys(previousQuestionStatus).filter(key => !panelQuestions.includes(key));

			const global = await this.globalService.getMasterSchema({ localeId: 'en-gb' });
			const questionSets = global.masterSchema.formPanels.filter(({ pageId }) => pageId !== target);

			questionSets.forEach(({ panelId }) => {
				previousQuestionSetStatus[panelId] = dataRequestSchemas[0].questionSetStatus[panelId];
			});

			updateQuestionStatus.forEach(key => {
				if (previousQuestionStatus[key] !== dataRequestSchemas[0].questionStatus[key]) countOfChanges += 1;

				previousQuestionStatus[key] = dataRequestSchemas[0].questionStatus[key];

				if (dataRequestSchemas[0].unpublishedGuidance.includes(key)) {
					unpublishedGuidance.push(key);
				}

				if (Object.keys(dataRequestSchemas[0].guidance).includes(key)) {
					guidance[key] = dataRequestSchemas[0].guidance[key];
				}
			});
		}

		await this.dataRequestRepository.updateApplicationFormSchemaById(dataRequestSchemas[0]._id, {
			questionStatus: previousQuestionStatus,
			questionSetStatus: previousQuestionSetStatus,
			unpublishedGuidance,
			guidance,
			countOfChanges,
		});

		return;
	}

	async getDefaultQuestionStates() {
		const global = await this.globalService.getMasterSchema({ localeId: 'en-gb' });
		const masterSchema = global.masterSchema;

		let defaultQuestionStates = {};

		masterSchema.questionSets.forEach(questionSet => {
			questionSet.questions.forEach(question => {
				if (question.lockedQuestion === 1) defaultQuestionStates[question.questionId] = 2;
				else defaultQuestionStates[question.questionId] = question.defaultQuestion;
			});
		});

		return defaultQuestionStates;
	}

	async getDefaultQuestionSetStates() {
		const global = await this.globalService.getMasterSchema({ localeId: 'en-gb' });
		const masterSchema = global.masterSchema;

		let defaultQuestionSetStates = {};

		masterSchema.questionSets.forEach(questionSet => {
			defaultQuestionSetStates[questionSet.questionSetId] = typeof questionSet.active !== 'undefined' ? questionSet.active : 1;
		});

		return defaultQuestionSetStates;
	}

	async getPanelQuestions(target) {
		const global = await this.globalService.getMasterSchema({ localeId: 'en-gb' });
		const questionSets = global.masterSchema.questionSets;

		const panelQuestions = questionSets.filter(questionSet => questionSet.questionSetId.includes(target));

		if (!panelQuestions) {
			throw new Error('Invalid page identifier: ' + target);
		}

		let questions = [];
		panelQuestions.forEach(panel => {
			questions.push(...panel.questions);
		});

		const questionIds = questions.map(question => question.questionId);

		return questionIds;
	}
}
