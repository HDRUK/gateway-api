export const publisherDocument = {
	_id: '5f3f98068af2ef61552e1d75',
	name: 'ALLIANCE > SAIL',
	active: true,
	imageURL: '',
	allowsMessaging: true,
	workflowEnabled: true,
	allowAccessRequestManagement: true,
	publisherDetails: { name: 'SAIL', memberOf: 'ALLIANCE' },
	uses5Safes: true,
	mdcFolderId: 'c4f50de0-2188-426b-a6cd-6b11a8d6c3cb',
};

export const globalDocument = {
	masterSchema: {
		questionSets: [
			{
				questionSetId: 'safepeople-primaryapplicant',
				questionSetHeader: 'Primary applicant',
				questions: [
					{
						guidance: 'Please insert your full name.',
						validations: [{ message: 'Please enter a value', type: 'isLength', params: [{ $numberDouble: '1.0' }] }],
						questionId: 'safepeopleprimaryapplicantfullname',
						input: { type: 'textInput', required: true },
						question: 'Full name',
						lockedQuestion: 1,
						defaultQuestion: 0,
					},
					{
						question: 'Job title',
						questionId: 'safepeopleprimaryapplicantjobtitle',
						validations: [{ params: [{ $numberDouble: '1.0' }], type: 'isLength', message: 'Please enter a value' }],
						input: { required: true, type: 'textInput' },
						lockedQuestion: 1,
						defaultQuestion: 1,
					},
					{
						questionId: 'safepeopleprimaryapplicanttelephone',
						input: { type: 'textInput' },
						question: 'Telephone',
						lockedQuestion: 1,
						defaultQuestion: 0,
					},
					{
						question: 'ORCID',
						input: { type: 'textInput' },
						questionId: 'safepeopleprimaryapplicantorcid',
						guidance:
							'ORCID provides a persistent digital identifier (an ORCID iD) that you own and control, and that distinguishes you from every other researcher. You can create an ORCID profile at  https://orcid.org/. If you have an ORCID iD please include it here. ',
						lockedQuestion: 0,
						defaultQuestion: 1,
					},
					{
						question: 'Email',
						validations: [{ message: 'Please enter a value', type: 'isLength', params: [{ $numberDouble: '1.0' }] }, { type: 'isEmail' }],
						input: { required: true, type: 'textInput' },
						questionId: 'safepeopleprimaryapplicantemail',
						lockedQuestion: 1,
						defaultQuestion: 0,
					},
				],
			},
			{
				questionSetHeader: 'Other individuals',
				questionSetId: 'safepeople-otherindividuals',
				questions: [
					{
						question: 'Full name',
						input: { type: 'textInput' },
						questionId: 'safepeopleotherindividualsfullname',
						guidance: "Full name is the individual's first and last name",
						lockedQuestion: 1,
						defaultQuestion: 1,
					},
					{
						question: 'Job title',
						input: { type: 'textInput' },
						questionId: 'safepeopleotherindividualsjobtitle',
						guidance: 'Job Title is the name of the position the individual holds within their organisation.',
						lockedQuestion: 1,
						defaultQuestion: 0,
					},
					{
						question: 'Organisation',
						questionId: 'safepeopleotherindividualsorganisation',
						input: { type: 'textInput' },
						guidance: "Please include the individual's organisation.",
						lockedQuestion: 1,
						defaultQuestion: 0,
					},
					{
						input: {
							type: 'checkboxOptionsInput',
							label: 'Role',
							options: [
								{ value: 'Principal investigator', text: 'Principal investigator' },
								{ text: 'Collaborator', value: 'Collaborator' },
								{ value: 'Team member', text: 'Team member' },
								{
									value: 'Other',
									text: 'Other',
									conditionalQuestions: [
										{
											questionId: 'safepeopleotherindividualsroleotherdetails',
											input: { type: 'textareaInput' },
											question: 'If other, please specify',
										},
									],
								},
							],
						},
						questionId: 'safepeopleotherindividualsrole',
						guidance:
							'A role is a function that the applicant plays. It might include role types and accreditation for those that are accessing the secure data and those that are not but would see cleared outputs from the project. \r\n (i.e. project lead, deputy lead, accrediter, researcher, peer reviewer)',
						question: 'Role',
						lockedQuestion: 1,
						defaultQuestion: 0,
					},
					{
						question: 'Will this person access the data requested?',
						questionId: 'safepeopleotherindividualsaccessdata',
						input: {
							options: [
								{ value: 'Yes', text: 'Yes' },
								{ text: 'No', value: 'No' },
							],
							label: 'Will this person access the data requested?',
							type: 'radioOptionsInput',
						},
						lockedQuestion: 1,
						defaultQuestion: 1,
					},
					{
						questionId: 'safepeopleotherindividualsaccreditedresearcher',
						input: {
							options: [
								{
									conditionalQuestions: [
										{
											question: 'If yes, please provide details',
											questionId: 'safepeopleotherindividualsaccreditedresearcherdetails',
											input: { type: 'textareaInput' },
										},
									],
									value: 'Yes',
									text: 'Yes',
								},
								{ value: 'No', text: 'No' },
							],
							type: 'radioOptionsInput',
							label: 'Is this person an accredited researcher under the Digital Economy Act 2017?',
						},
						question: 'Is this person an accredited researcher under the Digital Economy Act 2017?',
						lockedQuestion: 1,
						defaultQuestion: 0,
					},
					{
						input: {
							label: 'Has this person undertaken professional training or education on the topic of Information Governance?',
							type: 'radioOptionsInput',
							options: [
								{
									text: 'Yes',
									value: 'Yes',
									conditionalQuestions: [
										{
											input: { type: 'textareaInput' },
											questionId: 'safepeopleotherindividualstraininginformationgovernancerecent',
											question: 'Please provide full details regarding the most recent training',
										},
									],
								},
								{
									conditionalQuestions: [
										{
											questionId: 'safepeopleotherindividualstraininginformationgovernanceintention',
											input: { type: 'textareaInput' },
											question: 'Please provide any details of plans to attend training, if applicable',
										},
									],
									value: 'No',
									text: 'No',
								},
							],
						},
						questionId: 'safepeopleotherindividualstraininginformationgovernance',
						question: 'Has this person undertaken professional training or education on the topic of Information Governance?',
						lockedQuestion: 1,
						defaultQuestion: 0,
					},
					{
						question: "Please provide evidence of this person's expertise and experience relevant to delivering the project",
						input: { type: 'textareaInput' },
						questionId: 'safepeopleotherindividualsexperience',
						lockedQuestion: 1,
						defaultQuestion: 1,
					},
				],
			},
		],
		formPanels: [
			{ panelId: 'safepeople-primaryapplicant', index: 1, pageId: 'safepeople' },
			{
				panelId: 'safepeople-otherindividuals',
				pageId: 'safepeople',
				index: 2,
			},
			{
				panelId: 'safeproject-projectdetails',
				pageId: 'safeproject',
				index: 3,
			},
		],
	},
};
