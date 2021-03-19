// <DAR related enums>
const _userTypes = {
	CUSTODIAN: 'custodian',
	APPLICANT: 'applicant',
};

const _formTypes = Object.freeze({
  Enquiry : 'enquiry',
  Extended5Safe : '5 safes'
});

const _enquiryFormId = '5f0c4af5d138d3e486270031';

const _userQuestionActions = {
	custodian: {
		reviewer: {
			submitted: [
				{
					key: 'guidance',
					icon: 'far fa-question-circle',
					color: '#475da7',
					toolTip: 'Guidance',
					order: 1,
				},
			],
			inReview: [
				{
					key: 'guidance',
					icon: 'far fa-question-circle',
					color: '#475da7',
					toolTip: 'Guidance',
					order: 1,
				},
			],
			approved: [
				{
					key: 'guidance',
					icon: 'far fa-question-circle',
					color: '#475da7',
					toolTip: 'Guidance',
					order: 1,
				},
			],
			['approved with conditions']: [
				{
					key: 'guidance',
					icon: 'far fa-question-circle',
					color: '#475da7',
					toolTip: 'Guidance',
					order: 1,
				},
			],
			rejected: [
				{
					key: 'guidance',
					icon: 'far fa-question-circle',
					color: '#475da7',
					toolTip: 'Guidance',
					order: 1,
				},
			],
			withdrawn: [
				{
					key: 'guidance',
					icon: 'far fa-question-circle',
					color: '#475da7',
					toolTip: 'Guidance',
					order: 1,
				},
			],
		},
		manager: {
			submitted: [
				{
					key: 'guidance',
					icon: 'far fa-question-circle',
					color: '#475da7',
					toolTip: 'Guidance',
					order: 1,
				},
				{
					key: 'requestAmendment',
					icon: 'fas fa-exclamation-circle',
					color: '#F0BB24',
					toolTip: 'Request applicant updates answer',
					order: 2,
				},
			],
			inReview: [
				{
					key: 'guidance',
					icon: 'far fa-question-circle',
					color: '#475da7',
					toolTip: 'Guidance',
					order: 1,
				},
				{
					key: 'requestAmendment',
					icon: 'fas fa-exclamation-circle',
					color: '#F0BB24',
					toolTip: 'Request applicant updates answer',
					order: 2,
				},
			],
			approved: [
				{
					key: 'guidance',
					icon: 'far fa-question-circle',
					color: '#475da7',
					toolTip: 'Guidance',
					order: 1,
				},
			],
			['approved with conditions']: [
				{
					key: 'guidance',
					icon: 'far fa-question-circle',
					color: '#475da7',
					toolTip: 'Guidance',
					order: 1,
				},
			],
			rejected: [
				{
					key: 'guidance',
					icon: 'far fa-question-circle',
					color: '#475da7',
					toolTip: 'Guidance',
					order: 1,
				},
			],
			withdrawn: [
				{
					key: 'guidance',
					icon: 'far fa-question-circle',
					color: '#475da7',
					toolTip: 'Guidance',
					order: 1,
				},
			],
		},
	},
	applicant: {
		inProgress: [
			{
				key: 'guidance',
				icon: 'far fa-question-circle',
				color: '#475da7',
				toolTip: 'Guidance',
				order: 1,
			},
		],
		submitted: [
			{
				key: 'guidance',
				icon: 'far fa-question-circle',
				color: '#475da7',
				toolTip: 'Guidance',
				order: 1,
			},
		],
		inReview: [
			{
				key: 'guidance',
				icon: 'far fa-question-circle',
				color: '#475da7',
				toolTip: 'Guidance',
				order: 1,
			},
		],
		approved: [
			{
				key: 'guidance',
				icon: 'far fa-question-circle',
				color: '#475da7',
				toolTip: 'Guidance',
				order: 1,
			},
		],
		['approved with conditions']: [
			{
				key: 'guidance',
				icon: 'far fa-question-circle',
				color: '#475da7',
				toolTip: 'Guidance',
				order: 1,
			},
		],
		rejected: [
			{
				key: 'guidance',
				icon: 'far fa-question-circle',
				color: '#475da7',
				toolTip: 'Guidance',
				order: 1,
			},
		],
		withdrawn: [
			{
				key: 'guidance',
				icon: 'far fa-question-circle',
				color: '#475da7',
				toolTip: 'Guidance',
				order: 1,
			},
		],
	},
};

const _navigationFlags = {
	custodian: {
		submitted: {
			completed: { status: 'SUCCESS', options: [], text: '#NAME# updated this answer on #DATE#' },
		},
		returned: {
			completed: { status: 'WARNING', options: [], text: '#NAME# requested an update on #DATE#' },
			incomplete: { status: 'WARNING', options: [], text: '#NAME# requested an update on #DATE#' },
		},
		inProgress: {
			incomplete: {
				status: 'WARNING',
				options: [
					{
						text: 'Cancel request',
						action: 'cancelRequest',
						icon: '',
						displayOrder: 1,
					},
				],
				text: '#NAME# requested an update on #DATE#',
			},
		},
	},
	applicant: {
		submitted: {
			completed: { status: 'SUCCESS', options: [], text: '#NAME# updated this answer on #DATE#' },
			incomplete: { status: 'DANGER', options: [], text: '#NAME# requested an update on #DATE#' },
		},
		returned: {
			completed: {
				status: 'SUCCESS',
				options: [
					{
						text: 'Revert to previous answer',
						action: 'revertToPreviousAnswer',
						icon: '',
						displayOrder: 1,
					},
				],
				text: '#NAME# updated this answer on #DATE#',
			},
			incomplete: { status: 'DANGER', options: [], text: '#NAME# requested an update on #DATE#' },
		},
	},
};

const _submissionEmailRecipientTypes = ['applicant', 'dataCustodian'];

const _amendmentStatuses = {
	AWAITINGUPDATES: 'AWAITINGUPDATES',
	UPDATESSUBMITTED: 'UPDATESSUBMITTED',
	UPDATESREQUESTED: 'UPDATESREQUESTED',
	UPDATESRECEIVED: 'UPDATESRECEIVED',
};

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
	RETURNED: 'Returned',
	MEMBERADDED: 'MemberAdded',
	MEMBERREMOVED: 'MemberRemoved',
	MEMBERROLECHANGED: 'MemberRoleChanged',
	WORKFLOWASSIGNED: 'WorkflowAssigned',
	WORKFLOWCREATED: 'WorkflowCreated',
	INPROGRESS: 'InProgress'
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
	REMOVED: 'removed',
	REVERTED: 'reverted',
};

const _submissionTypes = {
	INPROGRESS: 'inProgress',
	INITIAL: 'initial',
	RESUBMISSION: 'resubmission',
};

const _formActions = {
	ADDREPEATABLESECTION: 'addRepeatableSection',
	REMOVEREPEATABLESECTION: 'removeRepeatableSection',
	ADDREPEATABLEQUESTIONS: 'addRepeatableQuestions',
	REMOVEREPEATABLEQUESTIONS: 'removeRepeatableQuestions',
}

const _darPanelMapper = {
	safesettings: 'Safe settings',
	safeproject: 'Safe project',
	safepeople: 'Safe people',
	safedata: 'Safe data',
	safeoutputs: 'Safe outputs',
};

// </DAR related enums>

// <Team related enums>
const _roleTypes = {
	MANAGER: 'manager',
	REVIEWER: 'reviewer',
};

// </DAR related enums>

const _hdrukEmail = 'enquiry@healthdatagateway.org';

export default {
	userTypes: _userTypes,
	enquiryFormId: _enquiryFormId,
	FormTypes: _formTypes,
	userQuestionActions: _userQuestionActions,
	navigationFlags: _navigationFlags,
	amendmentStatuses: _amendmentStatuses,
	notificationTypes: _notificationTypes,
	applicationStatuses: _applicationStatuses,
	amendmentModes: _amendmentModes,
	submissionTypes: _submissionTypes,
	formActions: _formActions,
	roleTypes: _roleTypes,
	darPanelMapper: _darPanelMapper,
	submissionEmailRecipientTypes: _submissionEmailRecipientTypes,
	hdrukEmail: _hdrukEmail,
};
