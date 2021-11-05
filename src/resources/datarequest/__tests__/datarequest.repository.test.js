import sinon from 'sinon';
import DataRequestRepository from '../datarequest.repository';
import { dataRequestDarId, contributors, darContributors, darContributorsInfo } from '../__mocks__/datarequestMock';

jest.setTimeout(30000);

describe('DataRequestRepository', function () {
	describe('getDarContributors', function () {
		it('should return contributors from a specified DAR id', async function () {
			const dataRequestRepository = new DataRequestRepository();
			const stub = sinon.stub(dataRequestRepository, 'getDarContributors').returns(contributors);

			const dataRequest = await dataRequestRepository.getDarContributors(dataRequestDarId.darId);

			expect(stub.calledOnce).toBe(true);

			expect(dataRequest[0].userId).toEqual(contributors[0].userId);
			expect(dataRequest[0].authorIds).toEqual(contributors[0].authorIds);
		});
	});

	describe('getDarContributorsInfo', function () {
		it('should return additional info about another DAR contributor', async function () {
			const dataRequestRepository = new DataRequestRepository();
			const stub = sinon.stub(dataRequestRepository, 'getDarContributorsInfo').returns(darContributorsInfo[0]);

			const dataRequest = await dataRequestRepository.getDarContributorsInfo(darContributors[0].id, darContributors[0].userId);

			expect(stub.calledOnce).toBe(true);

			expect(dataRequest.id).toEqual(darContributorsInfo[0].id);
			expect(dataRequest.firstname).toEqual(darContributorsInfo[0].firstname);
			expect(dataRequest.lastname).toEqual(darContributorsInfo[0].lastname);
			expect(dataRequest.orcid).toEqual(darContributorsInfo[0].orcid);
			expect(dataRequest.organisation).toEqual(darContributorsInfo[0].organisation);
            expect(dataRequest.user).toBe(undefined);
		});
	});

    describe('getDarContributorsInfo', function () {
		it('should return additional info including email about the logged in user who is a DAR contributor', async function () {
			const dataRequestRepository = new DataRequestRepository();
			const stub = sinon.stub(dataRequestRepository, 'getDarContributorsInfo').returns(darContributorsInfo[1]);

			const dataRequest = await dataRequestRepository.getDarContributorsInfo(darContributors[1].id, darContributors[1].userId);

			expect(stub.calledOnce).toBe(true);

			expect(dataRequest.id).toEqual(darContributorsInfo[1].id);
			expect(dataRequest.firstname).toEqual(darContributorsInfo[1].firstname);
			expect(dataRequest.lastname).toEqual(darContributorsInfo[1].lastname);
			expect(dataRequest.orcid).toEqual(darContributorsInfo[1].orcid);
			expect(dataRequest.organisation).toEqual(darContributorsInfo[1].organisation);
            expect(dataRequest.user).toEqual(darContributorsInfo[1].user);
		});
	});
});
