// <DAR related enums>
const _userTypes = {
	CUSTODIAN: 'custodian',
	APPLICANT: 'applicant',
	ADMIN: 'admin',
};

const _formTypes = Object.freeze({
	Enquiry: 'enquiry',
	Extended5Safe: '5 safe',
});

const _activityLogNotifications = Object.freeze({
	MANUALEVENTADDED: 'manualEventAdded',
	MANUALEVENTREMOVED: 'manualEventRemoved',
});

const _dataUseRegisterNotifications = Object.freeze({
	DATAUSEAPPROVED: 'dataUseApproved',
	DATAUSEREJECTED: 'dataUseRejected',
	DATAUSEPENDING: 'dataUsePending',
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

const _questionActions = {
	guidance: {
		key: 'guidance',
		icon: 'far fa-question-circle',
		color: '#475da7',
		toolTip: 'Guidance',
		order: 1,
	},
	messages: {
		key: 'messages',
		icon: 'far fa-comment-alt',
		color: '#475da7',
		toolTip: 'Messages',
		order: 2,
	},
	notes: {
		key: 'notes',
		icon: 'far fa-edit',
		color: '#475da7',
		toolTip: 'Notes',
		order: 3,
	},
	updates: {
		key: 'requestAmendment',
		icon: 'fas fa-exclamation-circle',
		color: '#F0BB24',
		toolTip: 'Request applicant updates answer',
		order: 4,
	},
};

const _navigationFlags = {
	custodian: {
		submitted: {
			completed: { status: 'SUCCESS', options: [], text: '#NAME# made this change on #DATE#' },
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
			completed: { status: 'SUCCESS', options: [], text: '#NAME# made this change on #DATE#' },
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
				text: '#NAME# made this change on #DATE#',
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
	APPLICATIONAMENDED: 'ApplicationAmended',
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
	TEAMADDED: 'TeamAdded',
	MESSAGESENT: 'MessageSent',
	DATASETDUPLICATED: 'DatasetDuplicated',
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
	AMENDED: 'amendment',
	EXTENDED: 'extension',
	RENEWAL: 'renewal',
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

const _DARMessageTypes = {
	DARMESSAGE: 'DAR_Message',
	DARNOTESAPPLICANT: 'DAR_Notes_Applicant',
	DARNOTESCUSTODIAN: 'DAR_Notes_Custodian',
};

// </DAR related enums>

// <Team related enums>
const _teamTypes = {
	PUBLISHER: 'publisher',
	ADMIN: 'admin',
};

const _roleTypes = {
	MANAGER: 'manager',
	REVIEWER: 'reviewer',
	METADATA_EDITOR: 'metadata_editor',
	ADMIN_DATASET: 'admin_dataset',
	ADMIN_DATA_USE: 'admin_data_use',
};

// </Team related enums>

// <Dataset onboarding related enums>

const _datasetStatuses = {
	DRAFT: 'draft',
	INREVIEW: 'inReview',
	ACTIVE: 'active',
	REJECTED: 'rejected',
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

const _dataUseRegisterStatus = {
	ACTIVE: 'active',
	INREVIEW: 'inReview',
	REJECTED: 'rejected',
	ARCHIVED: 'archived',
};

// Activity log related enums
const _activityLogEvents = {
	data_access_request: {
		APPLICATION_SUBMITTED: 'applicationSubmitted',
		REVIEW_PROCESS_STARTED: 'reviewProcessStarted',
		UPDATES_SUBMITTED: 'updatesSubmitted',
		AMENDMENT_SUBMITTED: 'amendmentSubmitted',
		APPLICATION_APPROVED: 'applicationApproved',
		APPLICATION_APPROVED_WITH_CONDITIONS: 'applicationApprovedWithConditions',
		APPLICATION_REJECTED: 'applicationRejected',
		COLLABORATOR_ADDEDD: 'collaboratorAdded',
		COLLABORATOR_REMOVED: 'collaboratorRemoved',
		PRESUBMISSION_MESSAGE: 'presubmissionMessage',
		UPDATE_REQUESTED: 'updateRequested',
		UPDATE_SUBMITTED: 'updateSubmitted',
		WORKFLOW_ASSIGNED: 'workflowAssigned',
		REVIEW_PHASE_STARTED: 'reviewPhaseStarted',
		RECOMMENDATION_WITH_ISSUE: 'reccomendationWithIssue',
		RECOMMENDATION_WITH_NO_ISSUE: 'reccomendationWithNoIssue',
		FINAL_DECISION_REQUIRED: 'finalDecisionRequired',
		DEADLINE_PASSED: 'deadlinePassed',
		MANUAL_EVENT: 'manualEvent',
		CONTEXTUAL_MESSAGE: 'contextualMessage',
		NOTE: 'note',
	},
	dataset: {
		DATASET_VERSION_SUBMITTED: 'newDatasetVersionSubmitted',
		DATASET_VERSION_APPROVED: 'datasetVersionApproved',
		DATASET_VERSION_REJECTED: 'datasetVersionRejected',
		DATASET_VERSION_ARCHIVED: 'datasetVersionArchived',
		DATASET_VERSION_UNARCHIVED: 'datasetVersionUnarchived',
		DATASET_UPDATES_SUBMITTED: 'datasetUpdatesSubmitted',
	},
	data_use_register: {
		DATA_USE_REGISTER_UPDATED: 'dataUseRegisterUpdated',
	},
};

const _activityLogTypes = {
	DATA_ACCESS_REQUEST: 'data_request',
	DATA_USE_REGISTER: 'data_use_register',
	DATASET: 'dataset',
};

const _systemGeneratedUser = {
	FIRSTNAME: 'System',
	LASTNAME: 'Generated',
};

const _datasetSortOptions = {
	latest: 'timestamps.updated',
	alphabetic: 'name',
	metadata: 'metadataQualityScore',
	recentlyadded: 'timestamps.published',
	popularity: 'counter',
	relevance: 'weights',
};

const _datasetSortOptionsKeys = {
	LATEST: 'latest',
	ALPHABETIC: 'alphabetic',
	METADATA: 'metadata',
	RECENTLYADDED: 'recentlyadded',
	POPULARITY: 'popularity',
	RELEVANCE: 'relevance',
};

const _datasetSortDirections = {
	asc: 1,
	desc: -1,
};

export default {
	userTypes: _userTypes,
	enquiryFormId: _enquiryFormId,
	formTypes: _formTypes,
	teamNotificationTypes: _teamNotificationTypes,
	teamNotificationMessages: _teamNotificationMessages,
	teamNotificationTypesHuman: _teamNotificationTypesHuman,
	teamNotificationEmailContentTypes: _teamNotificationEmailContentTypes,
	questionActions: _questionActions,
	navigationFlags: _navigationFlags,
	amendmentStatuses: _amendmentStatuses,
	notificationTypes: _notificationTypes,
	applicationStatuses: _applicationStatuses,
	amendmentModes: _amendmentModes,
	submissionTypes: _submissionTypes,
	formActions: _formActions,
	teamTypes: _teamTypes,
	roleTypes: _roleTypes,
	darPanelMapper: _darPanelMapper,
	submissionEmailRecipientTypes: _submissionEmailRecipientTypes,
	hdrukEmail: _hdrukEmail,
	mailchimpSubscriptionStatuses: _mailchimpSubscriptionStatuses,
	datasetStatuses: _datasetStatuses,
	logTypes: _logTypes,
	activityLogEvents: _activityLogEvents,
	activityLogTypes: _activityLogTypes,
	systemGeneratedUser: _systemGeneratedUser,
	activityLogNotifications: _activityLogNotifications,
	DARMessageTypes: _DARMessageTypes,
	datasetSortOptions: _datasetSortOptions,
	datasetSortOptionsKeys: _datasetSortOptionsKeys,
	datasetSortDirections: _datasetSortDirections,
	dataUseRegisterStatus: _dataUseRegisterStatus,
	dataUseRegisterNotifications: _dataUseRegisterNotifications,
};
