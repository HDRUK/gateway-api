import { has, isEmpty, isNil } from 'lodash';
import constants from '../../utilities/constants.util';
import teamController from '../../team/team.controller';
import moment from 'moment';
import { DataRequestSchemaModel } from '../schema/datarequest.schemas.model';
import dynamicForm from '../../utilities/dynamicForms/dynamicForm.util';

const repeatedSectionRegex = /_[a-zA-Z|\d]{5}$/gm;

const injectQuestionActions = (jsonSchema, userType, applicationStatus, role = '', activeParty, isLatestMinorVersion = true) => {
	if (
		userType === constants.userTypes.CUSTODIAN &&
		applicationStatus === constants.applicationStatuses.INREVIEW &&
		activeParty === constants.userTypes.CUSTODIAN &&
		role === constants.roleTypes.MANAGER &&
		isLatestMinorVersion
	)
		return {
			...jsonSchema,
			questionActions: [
				constants.questionActions.guidance,
				constants.questionActions.messages,
				constants.questionActions.notes,
				constants.questionActions.updates,
			],
		};
	else {
		return {
			...jsonSchema,
			questionActions: [constants.questionActions.guidance, constants.questionActions.messages, constants.questionActions.notes],
		};
	}
};

const getUserPermissionsForApplication = (application, userId, _id) => {
	try {
		let authorised = false,
			isTeamMember = false,
			userType = '';
		// Return default unauthorised with no user type if incorrect params passed
		if (!application || !userId || !_id) {
			return { authorised, userType };
		}
		// Check if the user is a custodian team member and assign permissions if so
		if (has(application, 'datasets') && has(application.datasets[0], 'publisher.team')) {
			isTeamMember = teamController.checkTeamPermissions('', application.datasets[0].publisher.team, _id);
		} else if (has(application, 'publisherObj.team')) {
			isTeamMember = teamController.checkTeamPermissions('', application.publisherObj.team, _id);
		}
		if (isTeamMember && (application.applicationStatus !== constants.applicationStatuses.INPROGRESS || application.isShared)) {
			userType = constants.userTypes.CUSTODIAN;
			authorised = true;
		}
		// If user is not authenticated as a custodian, check if they are an author or the main applicant
		if (application.applicationStatus === constants.applicationStatuses.INPROGRESS || isEmpty(userType)) {
			if (application.userId === userId || (application.authorIds && application.authorIds.includes(userId))) {
				userType = constants.userTypes.APPLICANT;
				authorised = true;
			}
		}
		return { authorised, userType };
	} catch (err) {
		process.stdout.write(`DATA REQUEST - getUserPermissionsForApplication : ${err.message}\n`);
		return { authorised: false, userType: '' };
	}
};

const extractApplicantNames = questionAnswers => {
	const fullNameQuestions = ['safepeopleprimaryapplicantfullname', 'safepeopleotherindividualsfullname'];
	const fullNames = [];

	if (isNil(questionAnswers)) return fullNames;

	Object.keys(questionAnswers).forEach(key => {
		if (fullNameQuestions.some(q => key.includes(q))) {
			fullNames.push(questionAnswers[key]);
		}
	});

	return fullNames;
};

const findQuestion = (questionsArr, questionId) => {
	// 1. Define child object to allow recursive calls
	let child;
	// 2. Exit from function if no children are present
	if (!questionsArr) return {};
	// 3. Iterate through questions in the current level to locate question by Id
	for (const questionObj of questionsArr) {
		// 4. Return the question if it is located
		if (questionObj.questionId === questionId) return questionObj;
		// 5. Recursively call the find question function on child elements to find question Id
		if (typeof questionObj.input === 'object' && typeof questionObj.input.options !== 'undefined') {
			questionObj.input.options
				.filter(option => {
					return typeof option.conditionalQuestions !== 'undefined' && option.conditionalQuestions.length > 0;
				})
				.forEach(option => {
					if (!child) {
						child = findQuestion(option.conditionalQuestions, questionId);
					}
				});
		}
		// 6. Return the child question
		if (child) return child;
	}
};

const updateQuestion = (questionsArr, question) => {
	// 1. Extract question Id
	let { questionId } = question;
	let found = false;
	// 2. Recursive function to iterate through each level of questions
	questionsArr.forEach(function iter(currentQuestion, index, currentArray) {
		// 3. Prevent unnecessary computation by exiting loop if question was found
		if (found) {
			return;
		}
		// 4. If the current question matches the target question, replace with updated question
		if (currentQuestion.questionId === questionId) {
			currentArray[index] = { ...question };
			found = true;
			return;
		}
		// 5. If target question has not been identified, recall function with child questions
		if (has(currentQuestion, 'input.options')) {
			currentQuestion.input.options.forEach(option => {
				if (has(option, 'conditionalQuestions')) {
					Array.isArray(option.conditionalQuestions) && option.conditionalQuestions.forEach(iter);
				}
			});
		}
	});
	// 6. Return the updated question array
	return questionsArr;
};

const setQuestionState = (question, questionAlert, readOnly) => {
	// 1. Find input object for question
	const { input = {} } = question;
	// 2. Assemble question in readOnly true/false mode
	question = {
		...question,
		input: {
			...input,
			questionAlert,
			readOnly,
		},
	};
	// 3. Recursively set readOnly mode for children
	if (has(question, 'input.options')) {
		question.input.options.forEach(function iter(currentQuestion) {
			// 4. If current question contains an input, set readOnly mode
			if (has(currentQuestion, 'input')) {
				currentQuestion.input.readOnly = readOnly;
			}
			// 5. Recall the iteration with each child question
			if (has(currentQuestion, 'conditionalQuestions')) {
				currentQuestion.conditionalQuestions.forEach(option => {
					if (has(option, 'input.options')) {
						Array.isArray(option.input.options) && option.input.options.forEach(iter);
					} else {
						option.input.readOnly = readOnly;
					}
				});
			}
		});
	}
	return question;
};

const buildQuestionAlert = (userType, iterationStatus, completed, amendment, user, publisher, includeCompleted = true) => {
	// 1. Use a try catch to prevent conditions where the combination of params lead to no question alert required
	try {
		// 2. Static mapping allows us to determine correct flag to show based on scenario (params)
		const questionAlert = {
			...constants.navigationFlags[userType][iterationStatus][completed],
		};
		// 3. Extract data from amendment
		let { requestedBy, updatedBy, dateRequested, dateUpdated } = amendment;
		// 4. Update audit fields to 'you' if the action was performed by the current user
		requestedBy = matchCurrentUser(user, requestedBy);
		updatedBy = matchCurrentUser(user, updatedBy);
		let relevantActioner;
		// 5. Update the generic question alerts to match the scenario
		if (userType === constants.userTypes.CUSTODIAN)
			if (iterationStatus === 'inProgress' || iterationStatus === 'returned' || !includeCompleted) {
				relevantActioner = requestedBy;
			} else {
				relevantActioner = updatedBy;
			}
		else if (userType === constants.userTypes.APPLICANT) {
			if (!isNil(updatedBy) && includeCompleted) {
				relevantActioner = updatedBy;
			} else {
				relevantActioner = publisher;
			}
		}
		questionAlert.text = questionAlert.text.replace('#NAME#', relevantActioner);
		questionAlert.text = questionAlert.text.replace(
			'#DATE#',
			userType === !isNil(dateUpdated) ? moment(dateUpdated).format('Do MMM YYYY') : moment(dateRequested).format('Do MMM YYYY')
		);
		// 6. Return the built question alert
		return questionAlert;
	} catch (err) {
		return {};
	}
};

const matchCurrentUser = (user, auditField) => {
	// 1. Extract the name of the current user
	const { firstname, lastname } = user;
	// 2. Compare current user to audit field supplied e.g. 'updated by'
	if (auditField === `${firstname} ${lastname}`) {
		// 3. Update audit field value to 'you' if name matches current user
		return 'You';
	}
	// 4. Return updated audit field
	return auditField;
};

const cloneIntoExistingApplication = (appToClone, appToUpdate) => {
	// 1. Extract values required to clone into existing application
	const { questionAnswers, _id } = appToClone;
	const { jsonSchema: schemaToUpdate } = appToUpdate;

	// 2. Extract and append any user repeated sections from the original form
	if (questionAnswers && Object.keys(questionAnswers).length > 0 && containsUserRepeatedSections(questionAnswers)) {
		const updatedSchema = copyUserRepeatedSections(appToClone, schemaToUpdate);
		appToUpdate.jsonSchema = updatedSchema;
	}

	// 3. Return updated application
	return { ...appToUpdate, questionAnswers, originId: _id };
};

const cloneIntoNewApplication = async (appToClone, context) => {
	// 1. Extract values required to clone existing application
	const { userId, datasetIds, datasetTitles, publisher } = context;
	const { questionAnswers, _id } = appToClone;

	// 2. Get latest publisher schema
	const { jsonSchema, version, _id: schemaId, isCloneable = false, formType } = await getLatestPublisherSchema(publisher);

	// 3. Create new application with combined details
	let newApplication = {
		version,
		userId,
		datasetIds,
		datasetTitles,
		isCloneable,
		formType,
		jsonSchema,
		schemaId,
		publisher,
		questionAnswers,
		aboutApplication: {},
		amendmentIterations: [],
		applicationStatus: constants.applicationStatuses.INPROGRESS,
		originId: _id,
	};

	// 4. Extract and append any user repeated sections from the original form
	if (questionAnswers && Object.keys(questionAnswers).length > 0 && containsUserRepeatedSections(questionAnswers)) {
		const updatedSchema = copyUserRepeatedSections(appToClone, jsonSchema);
		newApplication.jsonSchema = updatedSchema;
	}

	// 5. Return the cloned application
	return newApplication;
};

const getLatestPublisherSchema = async publisher => {
	// 1. Find latest schema for publisher
	let schema = await DataRequestSchemaModel.findOne({
		$or: [{ publisher }],
		status: 'active',
	}).sort({ createdAt: -1 });

	// 2. If no schema is found, throw error
	if (!schema) {
		throw new Error('The selected publisher does not have an active application form');
	}

	// 3. Return schema
	return schema;
};

const containsUserRepeatedSections = questionAnswers => {
	// 1. Use regex pattern matching to detect repeated sections (questionId contains _ followed by 5 alphanumeric characters)
	//	  e.g. applicantfirstname_1TV6P
	return Object.keys(questionAnswers).some(key => key.match(repeatedSectionRegex));
};

const copyUserRepeatedSections = (appToClone, schemaToUpdate) => {
	const { questionAnswers } = appToClone;
	const { questionSets } = schemaToUpdate;
	let copiedQuestionSuffixes = [];
	// 1. Extract all answers to repeated sections indicating questions that may need to be carried over
	const repeatedQuestionIds = extractRepeatedQuestionIds(questionAnswers);
	// 2. Iterate through each repeated question id
	repeatedQuestionIds.forEach(qId => {
		// 3. Skip if question has already been copied in by a previous clone operation
		let questionExists = questionSets.some(qS => !isNil(dynamicForm.findQuestionRecursive(qS.questions, qId)));
		if (questionExists) {
			return;
		}
		// 4. Split question id to get original id and unique suffix
		const [questionId, uniqueSuffix] = qId.split('_');
		// 5. Find the question in the new schema
		questionSets.forEach(qS => {
			// 6. Check if related group has already been copied in by this clone operation
			if (copiedQuestionSuffixes.includes(uniqueSuffix)) {
				return;
			}
			let question = dynamicForm.findQuestionRecursive(qS.questions, questionId);
			// 7. Ensure question was found and still exists in new schema
			if (question) {
				schemaToUpdate = insertUserRepeatedSections(questionSets, qS, schemaToUpdate, uniqueSuffix);
				// 8. Update duplicate question groups that have now been processed
				copiedQuestionSuffixes = [...copiedQuestionSuffixes, uniqueSuffix];
			}
		});
	});
	// 9. Return updated schema
	return { ...schemaToUpdate };
};

const insertUserRepeatedSections = (questionSets, questionSet, schemaToUpdate, uniqueSuffix) => {
	const { questionSetId, questions } = questionSet;
	// 1. Determine if question is repeatable via a question set or question group
	const repeatQuestionsId = `add-${questionSetId}`;
	if (questionSets.some(qS => qS.questionSetId === repeatQuestionsId)) {
		// 2. Replicate question set
		let duplicateQuestionSet = dynamicForm.duplicateQuestionSet(repeatQuestionsId, schemaToUpdate, uniqueSuffix);
		schemaToUpdate = dynamicForm.insertQuestionSet(repeatQuestionsId, duplicateQuestionSet, schemaToUpdate);
	} else {
		// 2. Find and replicate the question group
		let duplicateQuestionsButton = dynamicForm.findQuestionRecursive(questions, repeatQuestionsId);
		if (duplicateQuestionsButton) {
			const {
				questionId,
				input: { questionIds, separatorText },
			} = duplicateQuestionsButton;
			let duplicateQuestions = dynamicForm.duplicateQuestions(questionSetId, questionIds, separatorText, schemaToUpdate, uniqueSuffix);
			schemaToUpdate = dynamicForm.insertQuestions(questionSetId, questionId, duplicateQuestions, schemaToUpdate);
		}
	}
	// 3. Return updated schema
	return schemaToUpdate;
};

const extractRepeatedQuestionIds = questionAnswers => {
	// 1. Reduce original question answers to only answers relating to repeating sections
	return Object.keys(questionAnswers).reduce((arr, key) => {
		if (key.match(repeatedSectionRegex)) {
			arr = [...arr, key];
		}
		return arr;
	}, []);
};

const injectMessagesAndNotesCount = (jsonSchema, messages, notes) => {
	let messageNotesArray = [];

	messages.forEach(topic => {
		messageNotesArray.push({ question: topic.subTitle, messageCount: topic.topicMessages.length, notesCount: 0 });
	});

	notes.forEach(topic => {
		if (messageNotesArray.find(x => x.question === topic.subTitle)) {
			let existingTopic = messageNotesArray.find(x => x.question === topic.subTitle);
			existingTopic.notesCount = topic.topicMessages.length;
		} else {
			messageNotesArray.push({ question: topic.subTitle, messageCount: 0, notesCount: topic.topicMessages.length });
		}
	});

	messageNotesArray.forEach(messageNoteQuestion => {
		for (let questionPanel of jsonSchema.questionSets) {
			let question = findQuestion(questionPanel.questions, messageNoteQuestion.question);
			if (question) {
				question.counts = {
					messagesCount: messageNoteQuestion.messageCount,
					notesCount: messageNoteQuestion.notesCount,
				};
				break;
			}
		}
	});

	return jsonSchema;
};

export default {
	injectQuestionActions: injectQuestionActions,
	getUserPermissionsForApplication: getUserPermissionsForApplication,
	extractApplicantNames: extractApplicantNames,
	findQuestion: findQuestion,
	updateQuestion: updateQuestion,
	buildQuestionAlert: buildQuestionAlert,
	setQuestionState: setQuestionState,
	cloneIntoExistingApplication: cloneIntoExistingApplication,
	cloneIntoNewApplication: cloneIntoNewApplication,
	injectMessagesAndNotesCount,
	getLatestPublisherSchema: getLatestPublisherSchema,
	containsUserRepeatedSections: containsUserRepeatedSections,
	copyUserRepeatedSections: copyUserRepeatedSections,
};
