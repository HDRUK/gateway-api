import { authUtils } from '../../utils';
import { DataRequestModel } from '../datarequest/datarequest.model';
import { Data } from '../tool/data.model';

const _buildGa4ghVisas = async user => {
	let passportDecoded = [],
		passportEncoded = [];

	//AffiliationAndRole
	if (user.provider === 'oidc') {
		passportDecoded.push({
			iss: 'https://www.healthdatagateway.org',
			sub: user.id,
			ga4gh_visa_v1: {
				type: 'AffiliationAndRole',
				asserted: user.createdAt.getTime(),
				value: user.affiliation || 'no.organization', //open athens EDUPersonRole
				source: 'https://www.healthdatagateway.org', //TODO: update when value confirmed
			},
		});
	}

	//AcceptTermsAndPolicies
	passportDecoded.push({
		iss: 'https://www.healthdatagateway.org',
		sub: user.id,
		ga4gh_visa_v1: {
			type: 'AcceptTermsAndPolicies',
			asserted: user.createdAt.getTime(),
			value: 'https://www.hdruk.ac.uk/infrastructure/gateway/terms-and-conditions/',
			source: 'https://www.healthdatagateway.org',
			by: 'self',
		},
	});

	if (user.acceptedAdvancedSearchTerms) {
		passportDecoded.push({
			iss: 'https://www.healthdatagateway.org',
			sub: user.id,
			ga4gh_visa_v1: {
				type: 'AcceptTermsAndPolicies',
				asserted: user.createdAt.getTime(),
				value: 'https://www.healthdatagateway.org/advanced-search-terms/',
				source: 'https://www.healthdatagateway.org',
				by: 'self',
			},
		});
	}

	//ResearcherStatus
	passportDecoded.push({
		iss: 'https://www.healthdatagateway.org',
		sub: user.id,
		ga4gh_visa_v1: {
			type: 'ResearcherStatus',
			asserted: user.createdAt.getTime(),
			value: getResearchStatus(user),
			source: 'https://www.healthdatagateway.org',
		},
	});

	//ControlledAccessGrants
	let approvedDARApplications = await getApprovedDARApplications(user);
	approvedDARApplications.forEach(dar => {
		passportDecoded.push({
			iss: 'https://www.healthdatagateway.org',
			sub: user.id,
			ga4gh_visa_v1: {
				type: 'ControlledAccessGrants',
				asserted: dar.dateFinalStatus.getTime(), //date DAR was approved
				value: dar.pids.map(pid => {
					return 'https://web.www.healthdatagateway.org/dataset/' + pid;
				}), //URL to each dataset that they have been approved for
				source: 'https://www.healthdatagateway.org',
				by: 'dac',
			},
		});
	});

	passportDecoded.forEach(visa => {
		const expires_in = 900;
		const jwt = authUtils.signToken(visa, expires_in);
		passportEncoded.push(jwt);
	});

	return passportEncoded;
};

const getApprovedDARApplications = async user => {
	let approvedApplications = await DataRequestModel.find(
		{ $and: [{ userId: user.id }, { applicationStatus: { $in: ['approved', 'approved with conditions'] } }] },
		{ datasetIds: 1, dateFinalStatus: 1 }
	).lean();

	let approvedApplicationsWithPIDs = Promise.all(
		approvedApplications.map(async dar => {
			let pids = await Promise.all(
				dar.datasetIds.map(async datasetId => {
					let result = await Data.findOne({ datasetid: datasetId }, { pid: 1 }).lean();
					return result.pid;
				})
			);
			return { pids, dateFinalStatus: dar.dateFinalStatus };
		})
	);

	return approvedApplicationsWithPIDs;
};

const getResearchStatus = user => {
	const statuses = {
		UNKNOWN: 'unknown',
		BONAFIDE: 'bona fide',
		ACCREDITED: 'accredited',
		APPROVED: 'approved',
	};
	if (user.provider != 'oidc') {
		return statuses.UNKNOWN;
	} else {
		return statuses.BONAFIDE;
	}
	// TODO: Integrate with ONS API when it becomes available
};

export default {
	buildGa4ghVisas: _buildGa4ghVisas,
};
