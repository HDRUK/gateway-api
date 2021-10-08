import sinon from 'sinon';
import { cloneDeep } from 'lodash';

import dataUseRegisterUtil from '../dataUseRegister.util';
import {
	datasets,
	relatedObjectDatasets,
	nonGatewayDatasetNames,
	gatewayDatasetNames,
	expectedGatewayDatasets,
	nonGatewayApplicantNames,
	gatewayApplicantNames,
	expectedGatewayApplicants,
	applications,
	authors
} from '../__mocks__/dataUseRegisters';
import { uploader } from '../__mocks__/dataUseRegisterUsers';
import * as userRepository from '../../user/user.repository';
import { datasetService } from '../../dataset/dependency';

describe('DataUseRegisterUtil', function () {
	beforeAll(function () {
		process.env.homeURL = 'http://localhost:3000';
	});

	describe('getLinkedDatasets', function () {
		it('returns the names of the datasets that could not be found on the Gateway as named datasets', async function () {
			// Act
			const result = await dataUseRegisterUtil.getLinkedDatasets(nonGatewayDatasetNames);

			// Assert
			expect(result).toEqual({ linkedDatasets: [], namedDatasets: nonGatewayDatasetNames });
		});
		it('returns the details of datasets that could be found on the Gateway when valid URLs are given', async function () {
			// Arrange
			const getDatasetsByPidsStub = sinon.stub(datasetService, 'getDatasetsByPids');
			getDatasetsByPidsStub.returns(expectedGatewayDatasets);

			// Act
			const result = await dataUseRegisterUtil.getLinkedDatasets(gatewayDatasetNames);

			// Assert
			expect(getDatasetsByPidsStub.calledOnce).toBe(true);
			expect(result).toEqual({ linkedDatasets: expectedGatewayDatasets, namedDatasets: [] });
		});
	});

	describe('getLinkedApplicants', function () {
		it('returns the names of the applicants that could not be found on the Gateway', async function () {
			// Act
			const result = await dataUseRegisterUtil.getLinkedApplicants(nonGatewayApplicantNames);

			// Assert
			expect(result).toEqual({ gatewayApplicants: [], nonGatewayApplicants: nonGatewayApplicantNames });
		});
		it('returns the details of applicants that could be found on the Gateway when valid profile URLs are given', async function () {
			// Arrange
			const getUsersByIdsStub = sinon.stub(userRepository, 'getUsersByIds');
			getUsersByIdsStub.returns([{ _id: '89e57932-ac48-48ac-a6e5-29795bc38b94' }, { _id: '0cfe60cd-038d-4c03-9a95-894c52135922' }]);

			// Act
			const result = await dataUseRegisterUtil.getLinkedApplicants(gatewayApplicantNames);

			// Assert
			expect(getUsersByIdsStub.calledOnce).toBe(true);
			expect(result).toEqual({ gatewayApplicants: expectedGatewayApplicants, nonGatewayApplicants: [] });
		});
	});

	describe('buildRelatedDatasets', function () {
		it('filters out data uses that are found to already exist in the database', async function () {
			// Arrange
			const data = cloneDeep(datasets);
			sinon.stub(Date, 'now').returns('2021-24-09T11:01:58.135Z');

			// Act
			const result = dataUseRegisterUtil.buildRelatedDatasets(uploader, data);

			// Assert
			expect(result.length).toBe(data.length);
			expect(result).toEqual(relatedObjectDatasets);
		});

		afterEach(function () {
			sinon.restore();
		});
	});

	describe('extractFormApplicants', function () {
		it('identifies and combines gateway and non gateway applicants in the correct format', function () {
			// Arrange
			const questionAnswersStub = cloneDeep(applications[0].questionAnswers);
			const authorsStub = cloneDeep(authors);

			// Act
			const result = dataUseRegisterUtil.extractFormApplicants(authorsStub, questionAnswersStub);

			// Assert
			expect(result.gatewayApplicants.length).toBe(2);
			expect(result.gatewayApplicants).toEqual(expect.arrayContaining(['607db9c6e1f9d3704d570d93', '5fb628de6f3f9767bd2d9281']));

			expect(result.nonGatewayApplicants.length).toBe(2);
			expect(result.nonGatewayApplicants).toEqual(expect.arrayContaining(['Colin Devlin', 'Graham Patterson']));
		});

		it('removes duplicate applicants who are both authors of the application and named in the questions answers', function () {
			// Arrange
			const questionAnswersStub = cloneDeep(applications[0].questionAnswers);
			const authorsStub = cloneDeep(authors);

			// Act
			const result = dataUseRegisterUtil.extractFormApplicants(authorsStub, questionAnswersStub);

			// Assert
			expect(result.gatewayApplicants.length).toBe(2);
			expect(result.gatewayApplicants).toEqual(expect.arrayContaining(['607db9c6e1f9d3704d570d93', '5fb628de6f3f9767bd2d9281']));

			expect(result.nonGatewayApplicants.length).toBe(2);
			expect(result.nonGatewayApplicants).toEqual(expect.arrayContaining(['Colin Devlin', 'Graham Patterson']));
		});
	});

	describe('extractFundersAndSponsors', function () {
		it('identifies and combines funder and sponsor organisations named in the question answers ', function () {
			// Arrange
			const questionAnswersStub = cloneDeep(applications[0].questionAnswers);

			// Act
			const result = dataUseRegisterUtil.extractFundersAndSponsors(questionAnswersStub);

			// Assert
			expect(result.length).toBe(4);
			expect(result).toEqual(expect.arrayContaining(['funder 1', 'funder 2', 'sponsor 1', 'sponsor 2']));
		});
	});

	afterAll(function () {
		delete process.env.homeURL;
	});
});
