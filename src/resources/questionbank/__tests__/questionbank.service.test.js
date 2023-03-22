import sinon from 'sinon';
import DataRequestRepository from '../../datarequest/datarequest.repository';
import QuestionBankService from '../questionbank.service';
import GlobalService from '../../global/global.service';
import * as questionBank from '../__mocks__/questionbank';
import PublisherService from '../../publisher/publisher.service';
import PublisherRepository from '../../publisher/publisher.repository';
import * as activeSchemaNotCreatedThroughForm from '../__mocks__/activeSchemaNotCreatedThroughForm';
import * as activeSchemaCreatedThroughForm from '../__mocks__/activeSchemaCreatedThroughForm';
import * as draftSchemaNotCreatedThroughForm from '../__mocks__/draftSchemaCreatedThroughForm';
import * as noSchemaExists from '../__mocks__/noSchemaExists';

describe('Question Bank Service', function () {
	const dataRequestRepository = new DataRequestRepository();
	const globalService = new GlobalService();
	sinon.stub(globalService, 'getMasterSchema').returns(questionBank.globalDocument);
	const publisherRepository = new PublisherRepository();
	sinon.stub(publisherRepository, 'getPublisher').returns(questionBank.publisherDocument);
	const publisherService = new PublisherService(publisherRepository);
	const questionBankService = new QuestionBankService(publisherService, globalService, dataRequestRepository);
	let dataRequestRepositoryStubGet;
	let dataRequestRepositoryStubCreate;

	afterEach(function () {
		dataRequestRepositoryStubGet.restore();
		dataRequestRepositoryStubCreate.restore();
	});

	describe('getQuestionBankInfo', () => {
		it('No data request schema exists', async function () {
			dataRequestRepositoryStubGet = sinon.stub(dataRequestRepository, 'getApplicationFormSchemas').returns([]);
			dataRequestRepositoryStubCreate = sinon
				.stub(dataRequestRepository, 'createApplicationFormSchema')
				.returns(noSchemaExists.expectedSchema);

			const result = await questionBankService.getQuestionBankInfo(questionBank.publisherDocument._id);

			expect(result.questionStatus).toEqual(noSchemaExists.expectedSchema.questionStatus);
			expect(result.guidance).toEqual(noSchemaExists.expectedSchema.guidance);
			expect(result.countOfChanges).toEqual(noSchemaExists.expectedSchema.countOfChanges);
			expect(result.masterSchema).toEqual(questionBank.globalDocument.masterSchema);
		});

		it('Draft data request schema exists created through the customize form', async function () {
			dataRequestRepositoryStubGet = sinon
				.stub(dataRequestRepository, 'getApplicationFormSchemas')
				.returns([draftSchemaNotCreatedThroughForm.dataRequestSchema]);

			const result = await questionBankService.getQuestionBankInfo(questionBank.publisherDocument._id);

			expect(result.questionStatus).toEqual(draftSchemaNotCreatedThroughForm.dataRequestSchema.questionStatus);
			expect(result.guidance).toEqual(draftSchemaNotCreatedThroughForm.dataRequestSchema.guidance);
			expect(result.countOfChanges).toEqual(draftSchemaNotCreatedThroughForm.dataRequestSchema.countOfChanges);
			expect(result.masterSchema).toEqual(questionBank.globalDocument.masterSchema);
		});

		it('Active data request schema exists created through the customize form', async function () {
			dataRequestRepositoryStubGet = sinon
				.stub(dataRequestRepository, 'getApplicationFormSchemas')
				.returns([activeSchemaCreatedThroughForm.dataRequestSchema]);

			dataRequestRepositoryStubCreate = sinon
				.stub(dataRequestRepository, 'createApplicationFormSchema')
				.returns(activeSchemaCreatedThroughForm.expectedSchema);

			const result = await questionBankService.getQuestionBankInfo(questionBank.publisherDocument._id);

			expect(result.masterSchema).toEqual(questionBank.globalDocument.masterSchema);
			expect(result.guidance).toEqual(activeSchemaCreatedThroughForm.expectedSchema.guidance);
			expect(result.questionStatus).toEqual(activeSchemaCreatedThroughForm.expectedSchema.questionStatus);
			expect(result.countOfChanges).toEqual(activeSchemaCreatedThroughForm.expectedSchema.countOfChanges);
		});

		it('Active data request schema exists not created through the customize form', async function () {
			dataRequestRepositoryStubGet = sinon
				.stub(dataRequestRepository, 'getApplicationFormSchemas')
				.returns([activeSchemaNotCreatedThroughForm.dataRequestSchema]);

			dataRequestRepositoryStubCreate = sinon
				.stub(dataRequestRepository, 'createApplicationFormSchema')
				.returns(activeSchemaNotCreatedThroughForm.expectedSchema);

			const result = await questionBankService.getQuestionBankInfo(questionBank.publisherDocument._id);

			expect(result.questionStatus).toEqual(activeSchemaNotCreatedThroughForm.expectedSchema.questionStatus);
			expect(result.guidance).toEqual(activeSchemaNotCreatedThroughForm.expectedSchema.guidance);
			expect(result.countOfChanges).toEqual(activeSchemaNotCreatedThroughForm.expectedSchema.countOfChanges);
			expect(result.masterSchema).toEqual(questionBank.globalDocument.masterSchema);
		});
	});

	describe('revertChanges', () => {
		let publisherStub;

		const updateSchemaStub = sinon.stub(dataRequestRepository, 'updateApplicationFormSchemaById');

		afterEach(function () {
			publisherStub.restore();
		});

		it('should throw an error if no data request schemas are found for a given publisher', async () => {
			publisherStub = sinon.stub(publisherService, 'getPublisher').resolves();
			sinon.stub(dataRequestRepository, 'getApplicationFormSchemas').resolves([]);

			try {
				await questionBankService.revertChanges('testId');
			} catch (err) {
				expect(err.message).toEqual('This publisher has no data request schemas');
			}
		});

		it('should reset the entire form if no questionSetIds param passed', async () => {
			publisherStub = sinon.stub(publisherService, 'getPublisher').resolves();
			dataRequestRepositoryStubGet = sinon.stub(dataRequestRepository, 'getApplicationFormSchemas').returns([
				{
					_id: '5f3f98068af2ef61552e1d01',
					guidance: {
						safepeopleprimaryapplicantfullname: 'What is your full name?',
					},
					questionStatus: {
						safepeopleprimaryapplicantfullname: 0,
						safepeopleprimaryapplicantjobtitle: 0,
						safepeopleprimaryapplicanttelephone: 0,
						safepeopleotherindividualsfullname: 0,
						safepeopleotherindividualsjobtitle: 0,
						safepeopleotherindividualsorganisation: 0,
					},
					questionSetStatus: {
						'safepeople-primaryapplicant': 0,
						'safepeople-otherindividuals': 0,
					},
					unpublishedGuidance: ['safepeopleprimaryapplicantfullname'],
				},
				{
					_id: '5f3f98068af2ef61552e1d02',
					guidance: {},
					questionStatus: {
						safepeopleprimaryapplicantfullname: 1,
						safepeopleprimaryapplicantjobtitle: 1,
						safepeopleprimaryapplicanttelephone: 1,
						safepeopleotherindividualsfullname: 1,
						safepeopleotherindividualsjobtitle: 1,
						safepeopleotherindividualsorganisation: 1,
					},
					questionSetStatus: {
						'safepeople-primaryapplicant': 1,
						'safepeople-otherindividuals': 1,
					},
					unpublishedGuidance: [],
				},
			]);

			await questionBankService.revertChanges(questionBank.publisherDocument._id);

			expect(
				updateSchemaStub.calledWith('5f3f98068af2ef61552e1d01', {
					questionStatus: {
						safepeopleprimaryapplicantfullname: 1,
						safepeopleprimaryapplicantjobtitle: 1,
						safepeopleprimaryapplicanttelephone: 1,
						safepeopleotherindividualsfullname: 1,
						safepeopleotherindividualsjobtitle: 1,
						safepeopleotherindividualsorganisation: 1,
					},
					questionSetStatus: {
						'safepeople-primaryapplicant': 1,
						'safepeople-otherindividuals': 1,
					},
					unpublishedGuidance: [],
					guidance: {},
					countOfChanges: 0,
				})
			).toBe(true);
		});

		it('should reset one section if questionSetIds param passed', async () => {
			publisherStub = sinon.stub(publisherService, 'getPublisher').resolves();
			dataRequestRepositoryStubGet = sinon.stub(dataRequestRepository, 'getApplicationFormSchemas').returns([
				{
					_id: '5f3f98068af2ef61552e1d01',
					guidance: {
						safepeopleprimaryapplicantfullname: 'What is your full name?',
					},
					questionStatus: {
						safepeopleprimaryapplicantfullname: 0,
						safepeopleprimaryapplicantjobtitle: 0,
						safepeopleprimaryapplicanttelephone: 0,
						safeprojectprojectdetailstitle: 0,
						safeprojectprojectdetailstype: 0,
						safeprojectprojectdetailsneworexisting: 0,
					},
					questionSetStatus: {
						'safepeople-primaryapplicant': 0,
						'safeproject-projectdetails': 0,
					},
					unpublishedGuidance: ['safepeopleprimaryapplicantfullname'],
				},
				{
					_id: '5f3f98068af2ef61552e1d02',
					guidance: {},
					questionStatus: {
						safepeopleprimaryapplicantfullname: 1,
						safepeopleprimaryapplicantjobtitle: 1,
						safepeopleprimaryapplicanttelephone: 1,
						safeprojectprojectdetailstitle: 1,
						safeprojectprojectdetailstype: 1,
						safeprojectprojectdetailsneworexisting: 1,
					},
					questionSetStatus: {
						'safepeople-primaryapplicant': 1,
						'safeproject-projectdetails': 1,
					},
					unpublishedGuidance: [],
				},
			]);

			await questionBankService.revertChanges(questionBank.publisherDocument._id, 'safepeople');

			expect(
				updateSchemaStub.calledWith('5f3f98068af2ef61552e1d01', {
					questionStatus: {
						safepeopleprimaryapplicantfullname: 1,
						safepeopleprimaryapplicantjobtitle: 1,
						safepeopleprimaryapplicanttelephone: 1,
						safeprojectprojectdetailstitle: 0,
						safeprojectprojectdetailstype: 0,
						safeprojectprojectdetailsneworexisting: 0,
					},
					questionSetStatus: {
						'safepeople-primaryapplicant': 1,
						'safeproject-projectdetails': 0,
					},
					unpublishedGuidance: [],
					guidance: {},
					countOfChanges: 3,
				})
			).toBe(true);
		});
	});
});
