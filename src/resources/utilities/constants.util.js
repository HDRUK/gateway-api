// <DAR related enums>
const _userTypes = {
	CUSTODIAN: 'custodian',
	APPLICANT: 'applicant',
};

const _formTypes = Object.freeze({
	Enquiry: 'enquiry',
	Extended5Safe: '5 safe',
});

const _teamNotificationTypes = Object.freeze({
	DATAACCESSREQUEST: 'dataAccessRequest',
	METADATAONBOARDING: 'metaDataOnboarding',
});

const _teamNotificationMessages = {
	DATAACCESSREQUEST: 'A team manager removed team email addresses. Your email notifications are now being sent to your gateway email',
};

const _teamNotificationEmailContentTypes = {
	TEAMEMAILHEADERADD: 'A team manager has added a new team email address',
	TEAMEMAILHEADEREMOVE: 'A team manager has removed a team email address',
	TEAMEMAILSUBHEADERADD:
		'has added a new team email address. All emails relating to pre-submission messages from researchers will be sent to the following email addresses:',
	TEAMEMAILSUBHEADEREMOVE:
		'has removed a team email address. All emails relating to pre-submission messages from researchers will no longer be sent to the following email addresses:',
	TEAMEMAILFOOTERREMOVE:
		'If you had stopped emails being sent to your gateway log in email address and no team email address is now active, your emails will have reverted back to your gateway log in email.',
};

const _teamNotificationTypesHuman = Object.freeze({
	dataAccessRequest: 'Data access request',
	metaDataOnboarding: 'Meta data on-boarding',
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
				{
					key: 'messages',
					icon: 'far fa-comment-alt',
					color: '#475da7',
					toolTip: 'Messages',
					order: 2,
				},
				{
					key: 'notes',
					icon: 'far fa-edit',
					color: '#475da7',
					toolTip: 'Notes',
					order: 3,
				},
			],
			inReview: {
				custodian: [
					{
						key: 'guidance',
						icon: 'far fa-question-circle',
						color: '#475da7',
						toolTip: 'Guidance',
						order: 1,
					},
					{
						key: 'messages',
						icon: 'far fa-comment-alt',
						color: '#475da7',
						toolTip: 'Messages',
						order: 2,
					},
					{
						key: 'notes',
						icon: 'far fa-edit',
						color: '#475da7',
						toolTip: 'Notes',
						order: 3,
					},
				],
				applicant: [
					{
						key: 'guidance',
						icon: 'far fa-question-circle',
						color: '#475da7',
						toolTip: 'Guidance',
						order: 1,
					},
					{
						key: 'messages',
						icon: 'far fa-comment-alt',
						color: '#475da7',
						toolTip: 'Messages',
						order: 2,
					},
					{
						key: 'notes',
						icon: 'far fa-edit',
						color: '#475da7',
						toolTip: 'Notes',
						order: 3,
					},
				],
			},
			approved: [
				{
					key: 'guidance',
					icon: 'far fa-question-circle',
					color: '#475da7',
					toolTip: 'Guidance',
					order: 1,
				},
				{
					key: 'messages',
					icon: 'far fa-comment-alt',
					color: '#475da7',
					toolTip: 'Messages',
					order: 2,
				},
				{
					key: 'notes',
					icon: 'far fa-edit',
					color: '#475da7',
					toolTip: 'Notes',
					order: 3,
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
				{
					key: 'messages',
					icon: 'far fa-comment-alt',
					color: '#475da7',
					toolTip: 'Messages',
					order: 2,
				},
				{
					key: 'notes',
					icon: 'far fa-edit',
					color: '#475da7',
					toolTip: 'Notes',
					order: 3,
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
				{
					key: 'messages',
					icon: 'far fa-comment-alt',
					color: '#475da7',
					toolTip: 'Messages',
					order: 2,
				},
				{
					key: 'notes',
					icon: 'far fa-edit',
					color: '#475da7',
					toolTip: 'Notes',
					order: 3,
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
				{
					key: 'messages',
					icon: 'far fa-comment-alt',
					color: '#475da7',
					toolTip: 'Messages',
					order: 2,
				},
				{
					key: 'notes',
					icon: 'far fa-edit',
					color: '#475da7',
					toolTip: 'Notes',
					order: 3,
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
					key: 'messages',
					icon: 'far fa-comment-alt',
					color: '#475da7',
					toolTip: 'Messages',
					order: 2,
				},
				{
					key: 'notes',
					icon: 'far fa-edit',
					color: '#475da7',
					toolTip: 'Notes',
					order: 3,
				},
			],
			inReview: {
				custodian: [
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
					{
						key: 'messages',
						icon: 'far fa-comment-alt',
						color: '#475da7',
						toolTip: 'Messages',
						order: 3,
					},
					{
						key: 'notes',
						icon: 'far fa-edit',
						color: '#475da7',
						toolTip: 'Notes',
						order: 4,
					},
				],
				applicant: [
					{
						key: 'guidance',
						icon: 'far fa-question-circle',
						color: '#475da7',
						toolTip: 'Guidance',
						order: 1,
					},
					{
						key: 'messages',
						icon: 'far fa-comment-alt',
						color: '#475da7',
						toolTip: 'Messages',
						order: 2,
					},
					{
						key: 'notes',
						icon: 'far fa-edit',
						color: '#475da7',
						toolTip: 'Notes',
						order: 3,
					},
				],
			},
			approved: [
				{
					key: 'guidance',
					icon: 'far fa-question-circle',
					color: '#475da7',
					toolTip: 'Guidance',
					order: 1,
				},
				{
					key: 'messages',
					icon: 'far fa-comment-alt',
					color: '#475da7',
					toolTip: 'Messages',
					order: 2,
				},
				{
					key: 'notes',
					icon: 'far fa-edit',
					color: '#475da7',
					toolTip: 'Notes',
					order: 3,
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
				{
					key: 'messages',
					icon: 'far fa-comment-alt',
					color: '#475da7',
					toolTip: 'Messages',
					order: 2,
				},
				{
					key: 'notes',
					icon: 'far fa-edit',
					color: '#475da7',
					toolTip: 'Notes',
					order: 3,
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
				{
					key: 'messages',
					icon: 'far fa-comment-alt',
					color: '#475da7',
					toolTip: 'Messages',
					order: 2,
				},
				{
					key: 'notes',
					icon: 'far fa-edit',
					color: '#475da7',
					toolTip: 'Notes',
					order: 3,
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
				{
					key: 'messages',
					icon: 'far fa-comment-alt',
					color: '#475da7',
					toolTip: 'Messages',
					order: 2,
				},
				{
					key: 'notes',
					icon: 'far fa-edit',
					color: '#475da7',
					toolTip: 'Notes',
					order: 3,
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
			{
				key: 'messages',
				icon: 'far fa-comment-alt',
				color: '#475da7',
				toolTip: 'Messages',
				order: 2,
			},
			{
				key: 'notes',
				icon: 'far fa-edit',
				color: '#475da7',
				toolTip: 'Notes',
				order: 3,
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
			{
				key: 'messages',
				icon: 'far fa-comment-alt',
				color: '#475da7',
				toolTip: 'Messages',
				order: 2,
			},
			{
				key: 'notes',
				icon: 'far fa-edit',
				color: '#475da7',
				toolTip: 'Notes',
				order: 3,
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
				key: 'messages',
				icon: 'far fa-comment-alt',
				color: '#475da7',
				toolTip: 'Messages',
				order: 2,
			},
			{
				key: 'notes',
				icon: 'far fa-edit',
				color: '#475da7',
				toolTip: 'Notes',
				order: 3,
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
			{
				key: 'messages',
				icon: 'far fa-comment-alt',
				color: '#475da7',
				toolTip: 'Messages',
				order: 2,
			},
			{
				key: 'notes',
				icon: 'far fa-edit',
				color: '#475da7',
				toolTip: 'Notes',
				order: 3,
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
			{
				key: 'messages',
				icon: 'far fa-comment-alt',
				color: '#475da7',
				toolTip: 'Messages',
				order: 2,
			},
			{
				key: 'notes',
				icon: 'far fa-edit',
				color: '#475da7',
				toolTip: 'Notes',
				order: 3,
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
			{
				key: 'messages',
				icon: 'far fa-comment-alt',
				color: '#475da7',
				toolTip: 'Messages',
				order: 2,
			},
			{
				key: 'notes',
				icon: 'far fa-edit',
				color: '#475da7',
				toolTip: 'Notes',
				order: 3,
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
			{
				key: 'messages',
				icon: 'far fa-comment-alt',
				color: '#475da7',
				toolTip: 'Messages',
				order: 2,
			},
			{
				key: 'notes',
				icon: 'far fa-edit',
				color: '#475da7',
				toolTip: 'Notes',
				order: 3,
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
	INPROGRESS: 'InProgress',
	APPLICATIONCLONED: 'ApplicationCloned',
	APPLICATIONDELETED: 'ApplicationDeleted',
	DATASETSUBMITTED: 'DatasetSubmitted',
	DATASETAPPROVED: 'DatasetApproved',
	DATASETREJECTED: 'DatasetRejected',
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
};

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
	METADATA_EDITOR: 'metadata_editor',
	ADMIN_DATASET: 'admin_dataset',
};

// </Team related enums>

// <Dataset onboarding related enums>

const _datatsetStatuses = {
	DRAFT: 'draft',
	INPROGRESS: 'inProgress',
	INREVIEW: 'inReview',
	APPROVED: 'approved',
	REJECTED: 'rejected',
	APPROVEDWITHCONDITIONS: 'approved with conditions',
	ARCHIVE: 'archive',
};

// </Dataset onboarding related enums>

const _hdrukEmail = 'enquiry@healthdatagateway.org';

const _mailchimpSubscriptionStatuses = {
	SUBSCRIBED: 'subscribed',
	UNSUBSCRIBED: 'unsubscribed',
	CLEANED: 'cleaned',
	PENDING: 'pending',
};

const _logTypes = {
	SYSTEM: 'System',
	USER: 'User',
};

export default {
	userTypes: _userTypes,
	enquiryFormId: _enquiryFormId,
	formTypes: _formTypes,
	teamNotificationTypes: _teamNotificationTypes,
	teamNotificationMessages: _teamNotificationMessages,
	teamNotificationTypesHuman: _teamNotificationTypesHuman,
	teamNotificationEmailContentTypes: _teamNotificationEmailContentTypes,
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
	mailchimpSubscriptionStatuses: _mailchimpSubscriptionStatuses,
	datatsetStatuses: _datatsetStatuses,
	logTypes: _logTypes,
};
