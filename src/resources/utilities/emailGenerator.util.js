import _ from 'lodash';
import moment from 'moment';
import { UserModel } from '../user/user.model';
import helper from '../utilities/helper.util';
import teamController from '../team/team.controller';

const sgMail = require('@sendgrid/mail');
let parent, qsId;
let questionList = [];
let excludedQuestionSetIds = ['addApplicant', 'removeApplicant'];
let autoCompleteLookups = { fullname: ['email'] };

/**
 * [_unNestQuestionPanels]
 *
 * @desc    Un-nests the questions panels removes unused buttons from schema
 * @param   {Array<Object>} [{panelId, pageId, questionSets, ...}]
 * @return  {Array<Object>} [{panel}, {}]
 */
const _unNestQuestionPanels = (panels) => {
	return [...panels].reduce((arr, panel) => {
		// deconstruct questionPanel:[{panel}]
		let {
			panelId,
			pageId,
			questionSets,
			questionPanelHeaderText,
			navHeader,
		} = panel;
		if (typeof questionSets !== 'undefined') {
			if (questionSets.length > 1) {
				// filters excluded questionSetIds
				let filtered = [...questionSets].filter((item) => {
					let [questionId, uniqueId] = item.questionSetId.split('_');
					return !excludedQuestionSetIds.includes(questionId);
				});
				// builds new array of [{panelId, pageId, etc}]
				let newPanels = filtered.map((set) => {
					return {
						panelId,
						pageId,
						questionPanelHeaderText,
						navHeader,
						questionSetId: set.questionSetId,
					};
				});
				// update the arr reducer result
				arr = [...arr, ...newPanels];
			} else {
				// deconstruct
				let [{ questionSetId }] = questionSets;
				// update the arr reducer result
				arr = [
					...arr,
					{
						panelId,
						pageId,
						questionSetId,
						questionPanelHeaderText,
						navHeader,
					},
				];
			}
		}
		return arr;
	}, []);
};

/**
 * [_initalQuestionSpread]
 *
 * @desc    Un-nests the questions from each object[questions]
 * @param   {Object}        {'questionId', ...}
 * @return  {Array<Object>} [{question}, {}]
 */
const _initalQuestionSpread = (questions, pages, questionPanels) => {
	let flatQuestionList = [];
	if (!questions) return;
	for (let questionSet of questions) {
		let { questionSetId, questionSetHeader } = questionSet;

		let [qSId, uniqueQsId] = questionSetId.split('_');

		// question set full Id ie: applicant_hUad8
		let qsFullId =
			typeof uniqueQsId !== 'undefined' ? `${qSId}_${uniqueQsId}` : qSId;
		// remove out unwanted buttons or elements
		if (
			!excludedQuestionSetIds.includes(qSId) &&
			questionSet.hasOwnProperty('questions')
		) {
			for (let question of questionSet.questions) {
				//deconstruct quesitonId from question
				let { questionId } = question;

				// split questionId
				let [qId, uniqueQId] = questionId.split('_');

				// pass in questionPanels
				let questionPanel = [...questionPanels].find((i) => i.panelId === qSId);
				// find page it belongs too
				let page = [...pages].find((i) => i.pageId === questionPanel.pageId);

				// if page not found skip and the questionId isnt excluded
				if (
					typeof page !== 'undefined' &&
					!excludedQuestionSetIds.includes(qId)
				) {
					// if it is a generated field ie ui driven add back on uniqueId
					let obj = {
						page: page.title,
						section: questionPanel.navHeader,
						questionSetId: qsFullId,
						questionSetHeader,
						...question,
					};
					// update flatQuestionList array, spread previous add new object
					flatQuestionList = [...flatQuestionList, obj];
				}
			}
		}
	}
	return flatQuestionList;
};

/**
 * [_getAllQuestionsFlattened Build up a full question list recursively]
 *
 * @return  {Array<Object>} [{questionId, question}]
 */
const _getAllQuestionsFlattened = (allQuestions) => {
	let child;
	if (!allQuestions) return;

	for (let questionObj of allQuestions) {
		if (questionObj.hasOwnProperty('questionId')) {
			if (
				questionObj.hasOwnProperty('page') &&
				questionObj.hasOwnProperty('section')
			) {
				let { page, section, questionSetId, questionSetHeader } = questionObj;
				if (typeof questionSetId !== 'undefined') qsId = questionSetId;
				// set the parent page and parent section as nested wont have reference to its parent
				parent = { page, section, questionSetId: qsId, questionSetHeader };
			}
			let { questionId, question } = questionObj;
			// split up questionId
			let [qId, uniqueId] = questionId.split('_');
			// actual quesitonId
			let questionTitle =
				typeof uniqueId !== 'undefined' ? `${qId}_${uniqueId}` : qId;
			// if not in exclude list
			if (!excludedQuestionSetIds.includes(questionTitle)) {
				questionList = [
					...questionList,
					{
						questionId: questionTitle,
						question,
						questionSetHeader: parent.questionSetHeader,
						questionSetId: qsId,
						page: parent.page,
						section: parent.section,
					},
				];
			}
		}

		if (
			typeof questionObj.input === 'object' &&
			typeof questionObj.input.options !== 'undefined'
		) {
			questionObj.input.options
				.filter((option) => {
					return (
						typeof option.conditionalQuestions !== 'undefined' &&
						option.conditionalQuestions.length > 0
					);
				})
				.forEach((option) => {
					child = _getAllQuestionsFlattened(option.conditionalQuestions);
				});
		}

		if (child) {
			return child;
		}
	}
};

const _formatSectionTitle = (value) => {
	let [questionId] = value.split('_');
	return _.capitalize(questionId);
};

const _buildSubjectTitle = (user, title) => {
	if (user.toUpperCase() === 'DATACUSTODIAN') {
		return `Someone has submitted an application to access ${title} dataset. Please let the applicant know as soon as there is progress in the review of their submission.`;
	} else {
		return `You have requested access to ${title}. The custodian will be in contact about the application.`;
	}
};

/**
 * [_buildEmail]
 *
 * @desc    Build email template for Data access request
 * @param   {Object}  questions
 * @param   {Object}  answers
 * @param   {Object}  options
 * @return  {String} Questions Answered
 */
const _buildEmail = (fullQuestions, questionAnswers, options) => {
	let parent;
	let { userType, userName, userEmail, datasetTitles } = options;
	let subject = _buildSubjectTitle(userType, datasetTitles);
	let questionTree = { ...fullQuestions };
	let answers = { ...questionAnswers };
	let pages = Object.keys(questionTree);
	let table = `<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
                <table
                align="center"
                border="0"
                cellpadding="0"
                cellspacing="40"
                width="700"
                style="font-family: Arial, sans-serif">
                <thead>
                  <tr>
                    <th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
                      New data access request application
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                     ${subject}
                    </th>
                  </tr>
                </thead>
                <tbody>
                <tr>
                  <td bgcolor="#fff" style="padding: 0; border: 0;">
                    <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Dataset(s)</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${datasetTitles}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Date of submission</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment().format(
													'D MMM YYYY'
												)}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Applicant</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${userName}, ${_displayCorrectEmailAddress(
		userEmail,
		userType
	)}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
               `;

	// Create json content payload for attaching to email
	const jsonContent = {
		questions: { ...fullQuestions },
		answers: { ...questionAnswers },
	};

	let pageCount = 0;
	// render page [Safe People, SafeProject]
	for (let page of pages) {
		// page count for styling
		pageCount++;
		// {SafePeople: { Applicant:[], ...}}
		parent = questionTree[page];
		table += `<tr> 
                <td bgcolor="#fff" style="padding: 0 0 0 0; border:0;">
                  <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                  <tr>
                    <td>
                      <h2 style="font-size: 18px; color: #29235c !important; margin: -25px 0 15px 0;">${page}</h2>
                    </td>
                  </tr>`;

		// Safe People = [Applicant, Principle Investigator, ...]
		// Safe People to order array for applicant
		let sectionKeys;
		if (page.toUpperCase() === 'SAFE PEOPLE')
			sectionKeys = Object.keys({ ...parent }).sort();
		else sectionKeys = Object.keys({ ...parent });

		// styling for last child
		let sectionCount = 0;
		// render section
		for (let section of sectionKeys) {
			let questionsArr = questionTree[page][section];
			let [questionObj] = questionsArr;
			let sectionTitle = _formatSectionTitle(questionObj.questionSetHeader);
			sectionCount++;
			table += `<tr style="width: 600">
                    <!-- Key Section --> 
                    <td><h3 style="font-size: 16px; color :#29235c; margin: ${
											sectionCount !== 1 ? '25px 0 0 0;' : '10px 0 0 0;'
										}">${sectionTitle}</h3></td>
                </tr>`;
			// render question
			for (let question of questionsArr) {
				let answer = answers[question.questionId] || `-`;
				table += `<tr>
                    <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom:1px solid #d0d3d4">${question.question}</td>
                    <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom:1px solid #d0d3d4">${answer}</td>
                  </tr>`;
			}
		}
		table += `</table></td></tr>`;
	}
	table += ` </tbody></table></div>`;

	return { html: table, jsonContent };
};

/**
 * [_groupByPageSection]
 *
 * @desc    This function will group all the  questions into the correct format for emailBuilder
 * @return  {Object} {Safe People: {Applicant: [], Applicant_U8ad: []}, Safe Project: {}}
 */
const _groupByPageSection = (allQuestions) => {
	// group by page [Safe People, Safe Project]
	let groupedByPage = _.groupBy(allQuestions, (item) => {
		return item.page;
	});

	// within grouped [Safe People: {Applicant, Applicant1, Something}]
	let grouped = _.forEach(groupedByPage, (value, key) => {
		groupedByPage[key] = _.groupBy(groupedByPage[key], (item) => {
			return item.questionSetId;
		});
	});

	return grouped;
};

/**
 * [_actualQuestionAnswers]
 *
 * @desc    This function will repopulate any fiels populated by autoFill answers
 * @param   {Object} questionAnswers {fullname: '', ...}
 * @param   {Object} options {userType, ...}
 * @return  {Object} {fullname: 'James Swallow', email: 'james@gmail.com'}
 */
const _actualQuestionAnswers = async (questionAnswers, options) => {
	let obj = {};
	// test for user type custodian || user
	let { userType } = options;
	// spread questionAnswers to new var
	let qa = { ...questionAnswers };
	// get object keys of questionAnswers
	let keys = Object.keys(qa);
	// loop questionAnswer keys
	for (const key of keys) {
		// get value of key
		let value = qa[key];
		// split the key up for unique purposes
		let [qId, uniqueId] = key.split('_');
		// check if key in lookup
		let lookup = autoCompleteLookups[`${qId}`];
		// if key exists and it has an object do relevant data setting
		if (typeof lookup !== 'undefined' && typeof value === 'object') {
			switch (qId) {
				case 'fullname':
					// get user by :id {fullname, email}
					const response = await _getUserDetails(value);
					// deconstruct response
					let { fullname, email } = response;
					// set fullname: 'James Swallow'
					obj[key] = fullname;
					// show  full email for custodian or redacted for non custodians
					let validEmail = _displayCorrectEmailAddress(email, userType);
					// check  if uniqueId and set email field
					typeof uniqueId !== 'undefined'
						? (obj[`email_${uniqueId}`] = validEmail)
						: (obj[`email`] = validEmail);
					break;
				default:
					obj[key] = value;
			}
		}
	}
	// return out the update values write over questionAnswers;
	return { ...qa, ...obj };
};

/**
 * [_displayCorrectEmailAddress]
 *
 * @desc    This function will return a obfuscated email based on user role
 * @param   {String}  'your@gmail.com'
 * @param   {String}  'dataCustodian'
 * @return  {String}  'r********@**********m'
 */
const _displayCorrectEmailAddress = (email, userType) => {
	return userType.toUpperCase() === 'DATACUSTODIAN'
		? email
		: helper.censorEmail(email);
};

/**
 * [_getUserDetails]
 *
 * @desc    This function will return the user infromation from mongodb
 * @param   {Int}  98767876
 * @return  {Object} {fullname: 'James Swallow', email: 'james@gmail.com'}
 */
const _getUserDetails = async (userObj) => {
	return new Promise(async (resolve, reject) => {
		try {
			let { id } = userObj;
			const doc = await UserModel.findOne({ id }).exec();
			let { firstname = '', lastname = '', email = '' } = doc;
			resolve({ fullname: `${firstname} ${lastname}`, email });
		} catch (err) {
			reject({ fullname: '', email: '' });
		}
	});
};

const _generateEmail = async (
	questions,
	pages,
	questionPanels,
	questionAnswers,
	options
) => {
	// reset questionList arr
	questionList = [];
	// set questionAnswers
	let flatQuestionAnswers = await _actualQuestionAnswers(
		questionAnswers,
		options
	);
	// unnest each questionPanel if questionSets
	let flatQuestionPanels = _unNestQuestionPanels(questionPanels);
	// unnest question flat
	let unNestedQuestions = _initalQuestionSpread(
		questions,
		pages,
		flatQuestionPanels
	);
	// assigns to questionList
	let fullQuestionSet = _getAllQuestionsFlattened(unNestedQuestions);
	// fullQuestions [SafePeople: {Applicant: {}, Applicant_aca: {}}, SafeProject:{}]
	let fullQuestions = _groupByPageSection([...questionList]);
	// build up  email with  values
	let { html, jsonContent } = _buildEmail(
		fullQuestions,
		flatQuestionAnswers,
		options
	);
	// return email
	return { html, jsonContent };
};

const _displayConditionalStatusDesc = (
	applicationStatus,
	applicationStatusDesc
) => {
	if (
		(applicationStatusDesc &&
			applicationStatus === 'approved with conditions') ||
		applicationStatus === 'rejected'
	) {
		let conditionalTitle = '';
		switch (applicationStatus) {
			case 'approved with conditions':
				conditionalTitle = 'Approved with conditions:';
				break;
			case 'rejected':
				conditionalTitle = 'Reason for rejection:';
				break;
		}
		return `
      <p style="color: #29235c; font-size: 18px; font-weight:500; padding-bottom:5px">${conditionalTitle}</p>
      <p style="font-size: 14px; color: #3c3c3b; width: 100%;">${applicationStatusDesc}</p>
    `;
	}
	return '';
};

const _displayDARLink = (accessId) => {
	if (!accessId) return '';

	let darLink = `${process.env.homeURL}/data-access-request/${accessId}`;
	return `<a style="color: #475da7;" href="${darLink}">View application</a>`;
};

const _generateDARStatusChangedEmail = (options) => {
	let {
		id,
		applicationStatus,
		applicationStatusDesc,
		projectId,
		projectName,
		publisher,
		datasetTitles,
		dateSubmitted,
		applicants,
	} = options;
	let body = `<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
                <table
                align="center"
                border="0"
                cellpadding="0"
                cellspacing="40"
                width="700"
                style="font-family: Arial, sans-serif">
                <thead>
                  <tr>
                    <th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
                      Data access request application ${applicationStatus} 
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                     ${publisher} has ${applicationStatus} your data access request application.
                    </th>
                  </tr>
                </thead>
                <tbody>
                <tr>
                  <td bgcolor="#fff" style="padding: 0; border: 0;">
                    <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
													projectName || 'No project name set'
												}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project ID</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
													projectId || id
												}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Dataset(s)</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${datasetTitles}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Applicants</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${applicants}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Submitted</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment(
													dateSubmitted
												).format('D MMM YYYY')}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
            <div style="padding: 0 40px 40px 40px;">
            ${_displayConditionalStatusDesc(
							applicationStatus,
							applicationStatusDesc
						)}
            ${_displayDARLink(id)}
            </div>
          </div>`;
	return body;
};

const _generateContributorEmail = (options) => {
	let {
		id,
		datasetTitles,
		projectName,
		projectId,
		change,
		actioner,
		applicants,
	} = options;
	let header = `You've been ${
		change === 'added' ? 'added to' : 'removed from'
	} a data access request application`;
	let subheader = `${actioner} ${change} you as a contributor ${
		change === 'added' ? 'to' : 'from'
	} a data access request application. ${
		change == 'added'
			? 'Contributors can exchange private notes, make edits, invite others and submit the application.'
			: ''
	}`;

	let body = `<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
                <table
                align="center"
                border="0"
                cellpadding="0"
                cellspacing="40"
                width="700"
                style="font-family: Arial, sans-serif">
                <thead>
                  <tr>
                    <th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
                      ${header}
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                     ${subheader}
                    </th>
                  </tr>
                </thead>
                <tbody>
                <tr>
                  <td bgcolor="#fff" style="padding: 0; border: 0;">
                    <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
													projectName || 'No project name set'
												}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project ID</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
													projectId || id
												}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Dataset(s)</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${datasetTitles}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Applicants</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${applicants}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
            ${
							change === 'added'
								? ` 
            <div style="padding: 0 40px 40px 40px;">
            ${_displayDARLink(id)}
            </div>`
								: ''
						}
          </div>`;

	return body;
};

const _generateStepOverrideEmail = (options) => {
	let {
		id,
		projectName,
		projectId,
		datasetTitles,
		actioner,
		applicants,
		workflowName,
		stepName,
		nextStepName,
		nextReviewSections,
		nextReviewerNames,
		nextDeadline,
		reviewSections,
		reviewerNames,
		dateSubmitted,
		startDateTime,
		endDateTime,
		duration,
	} = options;
	let body = `<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
              <table style="font-family: Arial, sans-serif;" border="0" width="700" cellspacing="40" cellpadding="0" align="center">
              <thead>
              <tr>
              <th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">Data access request application review phase completed</th>
              </tr>
              <tr>
              <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">${actioner} has manually completed the review phase '${stepName}' for the following data access request application.</th>
              </tr>
              </thead>
              <tbody>
              <tr>
              <td style="padding: 0; border: 0;" bgcolor="#fff">
              <table border="0" width="100%" cellspacing="0" cellpadding="0">
              <tbody>
              <tr>
              <td style="font-size: 16px; color: #29235c; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4; font-weight:bold" colspan="2">Application details</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
								projectName || 'No project name set'
							}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project ID</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
								projectId || id
							}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Dataset(s)</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${datasetTitles}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Applicants</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${applicants}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Submitted</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment(
								dateSubmitted
							).format('D MMM YYYY')}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Workflow</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${workflowName}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">&nbsp;</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">&nbsp;</td>
              </tr>
              <tr>
              <td style="font-size: 16px; color: #29235c; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4; font-weight:bold" colspan="2">Completed review phase</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Phase name</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${stepName}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Phase commenced</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment(
								startDateTime
							).format('D MMM YYYY')}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Phase completed</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment(
								endDateTime
							).format('D MMM YYYY')}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Phase duration</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${duration}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Review sections</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${reviewSections}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Reviewers</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${reviewerNames}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;" colspan="2">&nbsp;</td>
              </tr>
              <tr>
              <td style="font-size: 16px; color: #29235c; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4; font-weight:bold;" colspan="2">Next review phase</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Phase name</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${nextStepName}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Review sections</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${nextReviewSections}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Reviewers&nbsp;</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${nextReviewerNames}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Deadline</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment(
								nextDeadline
							).format('D MMM YYYY')}</td>
              </tr>
              </tbody>
              </table>
              </td>
              </tr>
              </tbody>
              </table>
              <div style="padding: 0 40px 40px 40px;">${_displayDARLink(
								id
							)}</div>
              </div>`;
	return body;
};

const _generateNewReviewPhaseEmail = (options) => {
	let {
		id,
		projectName,
		projectId,
		datasetTitles,
		applicants,
		workflowName,
		stepName,
		currentDeadline,
		reviewSections,
		reviewerNames,
		dateSubmitted,
	} = options;
	let body = `<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
                <table
                align="center"
                border="0"
                cellpadding="0"
                cellspacing="40"
                width="700"
                style="font-family: Arial, sans-serif">
                <thead>
                  <tr>
                    <th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
                      Data access request application review phase commenced 
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                     You are now required to complete the review phase '${stepName}' for the following data access request application.
                    </th>
                  </tr>
                </thead>
                <tbody>
                <tr>
                  <td bgcolor="#fff" style="padding: 0; border: 0;">
                    <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                      <tr>
                        <td style="font-size: 16px; color: #29235c; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4; font-weight:bold" colspan="2">Application details</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
													projectName || 'No project name set'
												}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project ID</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
													projectId || id
												}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Dataset(s)</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${datasetTitles}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Applicants</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${applicants}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Submitted</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment(
													dateSubmitted
												).format('D MMM YYYY')}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Workflow</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${workflowName}</td>
                      </tr>
                      <tr>
                      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;" colspan="2">&nbsp;</td>
                      </tr>
                      <tr>
                      <td style="font-size: 16px; color: #29235c; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4; font-weight:bold;" colspan="2">Current review phase</td>
                      </tr>
                      <tr>
                      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Phase name</td>
                      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${stepName}</td>
                      </tr>
                      <tr>
                      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Review sections</td>
                      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${reviewSections}</td>
                      </tr>
                      <tr>
                      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Reviewers&nbsp;</td>
                      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${reviewerNames}</td>
                      </tr>
                      <tr>
                      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Deadline</td>
                      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment(
												currentDeadline
											).format('D MMM YYYY')}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
            <div style="padding: 0 40px 40px 40px;">
            ${_displayDARLink(id)}
            </div>
          </div>`;
	return body;
};

const _generateReviewDeadlineWarning = (options) => {
	let {
		id,
		projectName,
		projectId,
		datasetTitles,
		applicants,
		workflowName,
		stepName,
		reviewSections,
		reviewerNames,
		dateSubmitted,
		dateDeadline,
	} = options;
	let body = `<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
                <table
                align="center"
                border="0"
                cellpadding="0"
                cellspacing="40"
                width="700"
                style="font-family: Arial, sans-serif">
                <thead>
                  <tr>
                    <th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
                      The deadline is approaching for a Data Access Request application you are reviewing
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                     The following data access request application is approaching the review deadline of ${moment(
												dateDeadline
											).format('D MMM YYYY')}.
                    </th>
                  </tr>
                </thead>
                <tbody>
                <tr>
                  <td bgcolor="#fff" style="padding: 0; border: 0;">
                    <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
													projectName || 'No project name set'
												}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project ID</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
													projectId || id
												}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Dataset(s)</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${datasetTitles}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Applicants</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${applicants}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Submitted</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment(
													dateSubmitted
												).format('D MMM YYYY')}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Review phase</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${workflowName} - ${stepName}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Review sections</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${reviewSections}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Reviewers</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${reviewerNames}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Deadline</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment(
													dateDeadline
												).format('D MMM YYYY')}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
            <div style="padding: 0 40px 40px 40px;">
            ${_displayDARLink(id)}
            </div>
          </div>`;
	return body;
};

const _generateReviewDeadlinePassed = (options) => {
	let {
		id,
		projectName,
		projectId,
		datasetTitles,
		applicants,
		workflowName,
		stepName,
		reviewSections,
		reviewerNames,
		dateSubmitted,
		dateDeadline,
	} = options;
	let body = `<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
                <table
                align="center"
                border="0"
                cellpadding="0"
                cellspacing="40"
                width="700"
                style="font-family: Arial, sans-serif">
                <thead>
                  <tr>
                    <th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
                      Data access request application review phase deadlined passed
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                     The review phase '${stepName}' deadline has now passed for the following data access request application.
                    </th>
                  </tr>
                </thead>
                <tbody>
                <tr>
                  <td bgcolor="#fff" style="padding: 0; border: 0;">
                    <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
													projectName || 'No project name set'
												}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project ID</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
													projectId || id
												}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Dataset(s)</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${datasetTitles}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Applicants</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${applicants}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Submitted</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment(
													dateSubmitted
												).format('D MMM YYYY')}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Review phase</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${workflowName} - ${stepName}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Review sections</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${reviewSections}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Reviewers</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${reviewerNames}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Deadline</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment(
													dateDeadline
												).format('D MMM YYYY')}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
            <div style="padding: 0 40px 40px 40px;">
            ${_displayDARLink(id)}
            </div>
          </div>`;
	return body;
};

const _generateFinalDecisionRequiredEmail = (options) => {
	let {
		id,
		projectName,
		projectId,
		datasetTitles,
		actioner,
		applicants,
		workflowName,
		stepName,
		reviewSections,
		reviewerNames,
		dateSubmitted,
		startDateTime,
		endDateTime,
		duration,
		totalDuration,
	} = options;
	let body = `<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
              <table style="font-family: Arial, sans-serif;" border="0" width="700" cellspacing="40" cellpadding="0" align="center">
              <thead>
              <tr>
              <th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">Data access request application is now awaiting final approval</th>
              </tr>
              <tr>
              <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">The final phase ${stepName} of workflow ${workflowName} has now been completed for the following data access request application.</th>
              </tr>
              </thead>
              <tbody>
              <tr>
              <td style="padding: 0; border: 0;" bgcolor="#fff">
              <table border="0" width="100%" cellspacing="0" cellpadding="0">
              <tbody>
              <tr>
              <td style="font-size: 16px; color: #29235c; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4; font-weight:bold" colspan="2">Application details</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
								projectName || 'No project name set'
							}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project ID</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
								projectId || id
							}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Dataset(s)</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${datasetTitles}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Applicants</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${applicants}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Submitted</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment(
								dateSubmitted
							).format('D MMM YYYY')}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Workflow</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${workflowName}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">&nbsp;</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">&nbsp;</td>
              </tr>
              <tr>
              <td style="font-size: 16px; color: #29235c; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4; font-weight:bold" colspan="2">Completed review phase</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Phase name</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${stepName}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Phase commenced</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment(
								startDateTime
							).format('D MMM YYYY')}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Phase completed</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment(
								endDateTime
							).format('D MMM YYYY')}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Phase duration</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${duration}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Review sections</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${reviewSections}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Reviewers</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${reviewerNames}</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">&nbsp;</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">&nbsp;</td>
              </tr>
              <tr>
              <td style="font-size: 16px; color: #29235c; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4; font-weight:bold" colspan="2">Workflow details</td>
              </tr>
              <tr>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Duration</td>
              <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${totalDuration}</td>
              </tr>
              </tbody>
              </table>
              </td>
              </tr>
              </tbody>
              </table>
              <div style="padding: 0 40px 40px 40px;">${_displayDARLink(
								id
							)}</div>
              </div>`;
	return body;
};

const _generateRemovedFromTeam = (options) => {
	let { teamName } = options;
	let header = `You've been removed from the ${teamName} team on the HDR Innovation Gateway`;
	let subheader = `You will no longer be able to access Data Access Requests, messages or the profile area relating to this team.`;

	let body = `<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
                <table
                align="center"
                border="0"
                cellpadding="0"
                cellspacing="40"
                width="700"
                style="font-family: Arial, sans-serif">
                <thead>
                  <tr>
                    <th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
                      ${header}
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                     ${subheader}
                    </th>
                  </tr>
                </thead>
                <tbody>
                <tr>
                  <td bgcolor="#fff" style="padding: 0; border: 0;">
                    <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Team</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">
													${teamName}
												</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>`;
	return body;
};

const _generateAddedToTeam = (options) => {
	let { teamName, role } = options;
	let header = `You've been added to the ${teamName} team as a ${role} on the HDR Innovation Gateway`;
	let subheader = ``;
	if (role === teamController.roleTypes.MANAGER) {
		subheader = `You will now be able to create and manage Data Access Request workflows, process applications, send messages, and manage the profile area relating to this team, including the ability to add and remove new members.`;
	} else if (role === teamController.roleTypes.REVIEWER) {
		subheader = `You will now be able to review assigned Data Access Requests, send messages and visit the profile area relating to this team.`;
	}
	let body = `<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
                <table
                align="center"
                border="0"
                cellpadding="0"
                cellspacing="40"
                width="700"
                style="font-family: Arial, sans-serif">
                <thead>
                  <tr>
                    <th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
                      ${header}
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                     ${subheader}
                    </th>
                  </tr>
                </thead>
                <tbody>
                <tr>
                  <td bgcolor="#fff" style="padding: 0; border: 0;">
                    <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Team</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">
													${teamName}
												</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>`;
	return body;
};

/**
 * [_sendEmail]
 *
 * @desc    Send an email to an array of users using Twilio SendGrid
 * @param   {Object}  context
 */
const _sendEmail = async (
	to,
	from,
	subject,
	html,
	allowUnsubscribe = true,
	attachments = []
) => {
	// 1. Apply SendGrid API key from environment variable
	sgMail.setApiKey(process.env.SENDGRID_API_KEY);

	// 2. Ensure any duplicates recieve only a single email
	const recipients = [
		...new Map(to.map((item) => [item['email'], item])).values(),
	];

	// 3. Build each email object for SendGrid extracting email addresses from user object with unique unsubscribe link (to)
	for (let recipient of recipients) {
		let body = html + _generateEmailFooter(recipient, allowUnsubscribe);
		let msg = {
			to: recipient.email,
			from: from,
			subject: subject,
			html: body,
			attachments,
		};

		// 4. Send email using SendGrid
		await sgMail.send(msg);
	}
};

const _generateEmailFooter = (recipient, allowUnsubscribe) => {
	// 1. Generate HTML for unsubscribe link if allowed depending on context

	let unsubscribeHTML = '';

	if (allowUnsubscribe) {
		const baseURL = process.env.homeURL;
		const unsubscribeRoute = '/account/unsubscribe/';
		let userObjectId = recipient._id;
		let unsubscribeLink = baseURL + unsubscribeRoute + userObjectId;
		unsubscribeHTML = `<tr>
                        <td align="center">
                          <p>You're receiving this message because you have an account in the Innovation Gateway.</p>
                          <p><a style="color: #475da7;" href="${unsubscribeLink}">Unsubscribe</a> if you want to stop receiving these.</p>
                        </td>
                      </tr>`;
	}

	// 2. Generate generic HTML email footer
	return `<div style="margin-top: 23px; font-size:12px; text-align: center; line-height: 18px; color: #3c3c3b; width: 100%">
            <table
            align="center"
            border="0"
            cellpadding="0"
            cellspacing="16"
            style="font-family: Arial, sans-serif; 
            width:100%; 
            max-width:700px">
              <tbody>
                <tr>
                  <td align="center">
                    <a style="color: #475da7;" href="https://www.healthdatagateway.org">www.healthdatagateway.org</a>
                  </td>
                </tr>
                ${unsubscribeHTML}
                <tr>
                  <td align="center">
                    <span>©️HDR UK ${moment().year()}. All rights reserved.<span/>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>`;
};

const _generateAttachment = (filename, content, type) => {
  return {
		content,
		filename,
		type,
		disposition: 'attachment',
  };
};
const _generateNewWorkflowCreatedEmail = (manager, workflowName, workflowReviewers) => {
  
  let emailRecipients = [];
  let subject = `${manager.firstname} ${manager.lastname} has created a new workflow ${workflowName}`;
  let html = `<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
  <table style="font-family: Arial, sans-serif;" border="0" width="700" cellspacing="15" cellpadding="0" align="center">
  <thead>
  <tr>
  <th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">${manager.firstname} ${manager.lastname} has created a new workflow ${workflowName}</th>
  </tr>
    <td style="font-size: 16px; color: #29235c; padding: 10px 5px 0px 5px; width: 30%; text-align: left; vertical-align: top; font-weight:bold">Phases in workflow:</td>
  </thead>`;
  
  workflowReviewers.forEach(phase => {
    html += `<td style="font-size: 16px; color: #29235c; padding: 15px 5px 0px 5px; width: 30%; text-align: left; vertical-align: top; font-weight:bold" >${phase.phase} . ${phase.phaseName}</td>
    <tr>
      <td style="font-size: 14px; color: #29235c; padding: 0px 5px; width: 30%; text-align: left; vertical-align: top; font-weight:bold" >Reviewers in phase:</td>                 
    </tr>`;
    phase.reviewers.forEach(reviewer => {
      html += `<tr>
              <td style="font-size: 14px; padding: 0px 5px; width: 30%; text-align: left; vertical-align: top;" >${reviewer.firstName} ${reviewer.lastName}</td>
              </tr>`;
      emailRecipients.push(reviewer);
    });
  });

  html+= `</table> </div>`;

  return {
            emailRecipients: emailRecipients, 
            subject: subject,
            html: html
          }
};

const _generateWorkflowAssignedToApplicationEmail = (manager, workflowName, workflowReviewers) => {
  
  let emailRecipients = [];
  let subject = `${manager.firstname} ${manager.lastname} has assigned a ${workflowName} workflow to a Data Access Request`;
  let html = `<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
  <table style="font-family: Arial, sans-serif;" border="0" width="700" cellspacing="15" cellpadding="0" align="center">
  <thead>
  <tr>
  <th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">${manager.firstname} ${manager.lastname} has assigned a workflow (${workflowName}) you are part of to a data access request</th>
  </tr>
    <td style="font-size: 16px; color: #29235c; padding: 10px 5px 0px 5px; width: 30%; text-align: left; vertical-align: top; font-weight:bold">Phases in workflow:</td>
  </thead>`;
  
  workflowReviewers.forEach(phase => {
    html += `<td style="font-size: 16px; color: #29235c; padding: 15px 5px 0px 5px; width: 30%; text-align: left; vertical-align: top; font-weight:bold" >${phase.phase} . ${phase.phaseName}</td>
    <tr>
      <td style="font-size: 14px; color: #29235c; padding: 0px 5px; width: 30%; text-align: left; vertical-align: top; font-weight:bold" >Reviewers in phase:</td>                 
    </tr>`;
    phase.reviewers.forEach(reviewer => {
      html += `<tr>
              <td style="font-size: 14px; padding: 0px 5px; width: 30%; text-align: left; vertical-align: top;" >${reviewer.firstName} ${reviewer.lastName}</td>
              </tr>`;
      emailRecipients.push(reviewer);
    });
  });

  html+= `</table> </div>`;

  return {
            emailRecipients: emailRecipients, 
            subject: subject,
            html: html
          }
};

export default {
	generateEmail: _generateEmail,
	generateDARStatusChangedEmail: _generateDARStatusChangedEmail,
	generateContributorEmail: _generateContributorEmail,
	generateStepOverrideEmail: _generateStepOverrideEmail,
	generateNewReviewPhaseEmail: _generateNewReviewPhaseEmail,
	generateReviewDeadlineWarning: _generateReviewDeadlineWarning,
	generateReviewDeadlinePassed: _generateReviewDeadlinePassed,
	generateFinalDecisionRequiredEmail: _generateFinalDecisionRequiredEmail,
	generateRemovedFromTeam: _generateRemovedFromTeam,
	generateAddedToTeam: _generateAddedToTeam,
	sendEmail: _sendEmail,
	generateAttachment: _generateAttachment,
  generateEmailFooter: _generateEmailFooter,
  generateNewWorkflowCreatedEmail: _generateNewWorkflowCreatedEmail,
	generateWorkflowAssignedToApplicationEmail: _generateWorkflowAssignedToApplicationEmail
};
