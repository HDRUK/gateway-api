import sinon from 'sinon';
import DataRequestService from '../datarequest.service';
import DataRequestRepository from '../datarequest.repository';
import { dataRequestDarId, loggedInUser, contributors, darContributorsInfo, darContributorsReturnedInfo } from '../__mocks__/datarequestMock';

jest.setTimeout(30000);

describe('DataRequestService', function () {
	describe('getDarContributors', function () {
		it('should return DAR contributors sanitisied information', async function () {
			const dataRequestRepository = new DataRequestRepository();
			const getDarContributorsStub = sinon.stub(dataRequestRepository, 'getDarContributors').returns(contributors);

            // mocks the call to 'getDarContributorsInfo' 3 times, returning a different response for each call
            const getDarContributorsInfoStub = sinon.stub(dataRequestRepository, 'getDarContributorsInfo');
            getDarContributorsInfoStub.onCall(0).returns([darContributorsInfo[0]]);
            getDarContributorsInfoStub.onCall(1).returns([darContributorsInfo[1]]);
            getDarContributorsInfoStub.returns([darContributorsInfo[2]]);

			const dataRequestService = new DataRequestService(dataRequestRepository);
			const dataRequest = await dataRequestService.getDarContributors(dataRequestDarId.darId, loggedInUser.userId);


			expect(getDarContributorsStub.calledOnce).toBe(true);
			expect(getDarContributorsInfoStub.calledThrice).toBe(true);

			expect(dataRequest).toEqual(darContributorsReturnedInfo);

            // for a contributor that isn't the logged in user, organisation is hidden where 'showOrganisation' is false
            expect(dataRequest[0].showOrcid).toBe(true);
            expect(dataRequest[0].orcid).toEqual(darContributorsReturnedInfo[0].orcid);
            expect(dataRequest[0].showOrganisation).toBe(false);
            expect(dataRequest[0].organisation).toEqual('');

            // for a contributor that is the logged in user, all values are returned regardless of show being set as false
            expect(dataRequest[1].showOrcid).toBe(false);
            expect(dataRequest[1].orcid).toEqual(darContributorsReturnedInfo[1].orcid);
            expect(dataRequest[1].showOrganisation).toBe(false);
            expect(dataRequest[1].organisation).toEqual(darContributorsReturnedInfo[1].organisation);
		});
	});
});


