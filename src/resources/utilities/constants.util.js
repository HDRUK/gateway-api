// <DAR related enums>
const _userTypes = {
	CUSTODIAN: 'custodian',
	APPLICANT: 'applicant',
};

const _submissionEmailRecipientTypes = [
	'applicant', 
	'dataCustodian'
];

const _notificationTypes = {
	STATUSCHANGE: 'StatusChange',
	SUBMITTED: 'Submitted',
	RESUBMITTED: 'Resubmitted',
	CONTRIBUTORCHANGE: 'ContributorChange',
	STEPOVERRIDE: 'StepOverride',
	REVIEWSTEPSTART: 'ReviewStepStart',
	FINALDECISIONREQUIRED: 'FinalDecisionRequired',
	DEADLINEWARNING: 'DeadlineWarning',
	DEADLINEPASSED: 'DeadlinePassed',
};

const _applicationStatuses = {
	SUBMITTED: 'submitted',
	INPROGRESS: 'inProgress',
	INREVIEW: 'inReview',
	APPROVED: 'approved',
	REJECTED: 'rejected',
	APPROVEDWITHCONDITIONS: 'approved with conditions',
	WITHDRAWN: 'withdrawn',
};

const _amendmentModes = {
	ADDED: 'added',
	REMOVED: 'removed'
};

const _submissionTypes = {
	INITIAL: 'initial',
	RESUBMISSION: 'resubmission'
};

const _darPanelMapper = {
	safesettings: 'Safe settings',
	safeproject: 'Safe project',
	safepeople: 'Safe people',
	safedata: 'Safe data',
	safeoutputs: 'Safe outputs'
};


// </DAR related enums>

// <Team related enums>
const _roleTypes = {
	MANAGER: 'manager',
	REVIEWER: 'reviewer',
}

// </DAR related enums>

export default {
	userTypes: _userTypes,
	notificationTypes: _notificationTypes,
	applicationStatuses: _applicationStatuses,
	amendmentModes: _amendmentModes,
	submissionTypes: _submissionTypes,
	roleTypes: _roleTypes,
	darPanelMapper: _darPanelMapper,
	submissionEmailRecipientTypes: _submissionEmailRecipientTypes
};