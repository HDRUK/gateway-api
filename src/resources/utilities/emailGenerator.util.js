import _, { isNil, isEmpty, capitalize, groupBy, forEach, isEqual } from 'lodash';
import moment from 'moment';
import { UserModel } from '../user/user.model';
import helper from '../utilities/helper.util';
import constants from '../utilities/constants.util';
import * as Sentry from '@sentry/node';

const sgMail = require('@sendgrid/mail');
let parent, qsId;
let questionList = [];
let excludedQuestionSetIds = ['addRepeatableSection', 'removeRepeatableSection'];
let autoCompleteLookups = { fullname: ['email'] };

const _getStepReviewers = (reviewers = []) => {
	if (!isEmpty(reviewers)) return [...reviewers].map(reviewer => `${reviewer.firstname} ${reviewer.lastname}`).join(', ');

	return '';
};

const _getStepSections = (sections = []) => {
	if (!isEmpty(sections)) return [...sections].map(section => constants.darPanelMapper[section]).join(', ');

	return '';
};

/**
 * [_unNestQuestionPanels]
 *
 * @desc    Un-nests the questions panels removes unused buttons from schema
 * @param   {Array<Object>} [{panelId, pageId, questionSets, ...}]
 * @return  {Array<Object>} [{panel}, {}]
 */
const _unNestQuestionPanels = panels => {
	return [...panels].reduce((arr, panel) => {
		// deconstruct questionPanel:[{panel}]
		let { panelId, pageId, questionSets, questionPanelHeaderText, navHeader } = panel;
		if (typeof questionSets !== 'undefined') {
			if (questionSets.length > 1) {
				// filters excluded questionSetIds
				let filtered = [...questionSets].filter(item => {
					let [questionId] = item.questionSetId.split('_');
					return !excludedQuestionSetIds.includes(questionId);
				});
				// builds new array of [{panelId, pageId, etc}]
				let newPanels = filtered.map(set => {
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
		let qsFullId = typeof uniqueQsId !== 'undefined' ? `${qSId}_${uniqueQsId}` : qSId;
		// remove out unwanted buttons or elements
		if (!excludedQuestionSetIds.includes(qSId) && questionSet.hasOwnProperty('questions')) {
			for (let question of questionSet.questions) {
				//deconstruct quesitonId from question
				let { questionId } = question;

				// split questionId
				let [qId] = questionId.split('_');

				// pass in questionPanels
				let questionPanel = [...questionPanels].find(i => i.panelId === qSId);
				// find page it belongs too
				if (questionPanel) {
					let page = [...pages].find(i => i.pageId === questionPanel.pageId);

					// if page not found skip and the questionId isnt excluded
					if (typeof page !== 'undefined' && !excludedQuestionSetIds.includes(qId)) {
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
	}
	return flatQuestionList;
};

/**
 * [_getAllQuestionsFlattened Build up a full question list recursively]
 *
 * @return  {Array<Object>} [{questionId, question}]
 */
const _getAllQuestionsFlattened = allQuestions => {
	let child;
	if (!allQuestions) return;

	for (let questionObj of allQuestions) {
		if (questionObj.hasOwnProperty('questionId')) {
			if (questionObj.hasOwnProperty('page') && questionObj.hasOwnProperty('section')) {
				let { page, section, questionSetId, questionSetHeader } = questionObj;
				if (typeof questionSetId !== 'undefined') qsId = questionSetId;
				// set the parent page and parent section as nested wont have reference to its parent
				parent = { page, section, questionSetId: qsId, questionSetHeader };
			}
			let { questionId, question, input } = questionObj;
			// split up questionId
			let [qId, uniqueId] = questionId.split('_');
			// actual quesitonId
			let questionTitle = typeof uniqueId !== 'undefined' ? `${qId}_${uniqueId}` : qId;
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
						input,
					},
				];
			}
		}

		if (typeof questionObj.input === 'object' && typeof questionObj.input.options !== 'undefined') {
			questionObj.input.options
				.filter(option => {
					return typeof option.conditionalQuestions !== 'undefined' && option.conditionalQuestions.length > 0;
				})
				.forEach(option => {
					child = _getAllQuestionsFlattened(option.conditionalQuestions);
				});
		}

		if (child) {
			return child;
		}
	}
};

const _formatSectionTitle = value => {
	let [questionId] = value.split('_');
	return capitalize(questionId);
};

const _getSubmissionDetails = (
	userType,
	userName,
	userEmail,
	datasetTitles,
	initialDatasetTitles,
	submissionType,
	projectName,
	isNationalCoreStudies,
	dateSubmitted,
	linkNationalCoreStudies
) => {
	let body = `<table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
  <tr>
      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project</td>
      <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${projectName || 'No project name set'}</td>
    </tr>
    <tr>
      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Related NCS project</td>
      <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
				isNationalCoreStudies ? `<a style="color: #475da7;" href="${linkNationalCoreStudies}">View NCS project</a>` : 'no'
			}</td>
  </tr>  
  <tr>
      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Dataset(s)</td>
      <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${datasetTitles}</td>
    </tr>
    <tr>
      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Date of submission</td>
      <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${dateSubmitted}</td>
    </tr>
    <tr>
      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Applicant</td>
      <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${userName}, ${_displayCorrectEmailAddress(
		userEmail,
		userType
	)}</td>
    </tr>
  </table>`;

	const amendBody = `<table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
  <tr>
      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project</td>
      <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${projectName || 'No project name set'}</td>
    </tr>
    <tr>
      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Date of amendment submission</td>
      <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${dateSubmitted}</td>
  </tr>  
    <tr>
      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Applicant</td>
      <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${userName}, ${_displayCorrectEmailAddress(
		userEmail,
		userType
	)}</td>
    </tr>
  </table>
  <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td>
        <h2 style="font-size: 18px; color: #29235c !important; margin: 30px 0 15px 0;">Datasets requested</h2>
      </td>
    </tr>
    <tr>
      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Previous datasets</td>
      <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${initialDatasetTitles}</td>
    </tr>
    <tr>
      <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">New datasets</td>
      <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${datasetTitles}</td>
  </tr>
  </table>`;

	let heading, subject;
	switch (submissionType) {
		case constants.submissionTypes.INPROGRESS:
			heading = 'Data access request application in progress';
			subject = `You are in progress with a request access to ${datasetTitles}. The custodian will be in contact after you submit the application.`;
			break;
		case constants.submissionTypes.INITIAL:
			heading = 'New data access request application';
			subject = `You have requested access to ${datasetTitles}. The custodian will be in contact about the application.`;
			break;
		case constants.submissionTypes.RESUBMISSION:
			heading = 'Existing data access request application with new updates';
			subject = `You have made updates to your Data Access Request for ${datasetTitles}. The custodian will be in contact about the application.`;
			break;
		case constants.submissionTypes.AMENDED:
			heading = 'New amendment request application';
			subject = `Applicant has submitted an amendment to an approved application.  Please let the applicant know as soon as there is progress in the review of their submission.`;
			body = amendBody;
			break;
	}

	return `<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
  <table
  align="center"
  border="0"
  cellpadding="0"
  cellspacing="40"
  width="700"
  word-break="break-all"
  style="font-family: Arial, sans-serif">
  <thead>
    <tr>
      <th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
       ${heading}
      </th>
    </tr>
    <tr>
      <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
       ${subject}
      </th>
    </tr>
  </thead>
  <tbody style="overflow-y: auto; overflow-x: hidden;">
  <tr style="width: 100%; text-align: left;">
    <td bgcolor="#fff" style="padding: 0; border: 0;">
      ${body}
    </td>
  </tr>
 `;
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
const _buildEmail = (aboutApplication, fullQuestions, questionAnswers, options) => {
	const {
		userType,
		userName,
		userEmail,
		datasetTitles,
		initialDatasetTitles,
		submissionType,
		submissionDescription,
		applicationId,
	} = options;
	const dateSubmitted = moment().format('D MMM YYYY');
	const year = moment().year();
	const { projectName, isNationalCoreStudies = false, nationalCoreStudiesProjectId = '' } = aboutApplication;
	const linkNationalCoreStudies =
		nationalCoreStudiesProjectId === '' ? '' : `${process.env.homeURL}/project/${nationalCoreStudiesProjectId}`;

	let parent;
	let questionTree = { ...fullQuestions };
	let answers = { ...questionAnswers };
	let pages = Object.keys(questionTree);
	let gatewayAttributionPolicy = `We ask that use of the Health Data Research Innovation Gateway (the 'Gateway') be attributed in any resulting research outputs. Please include the following statement in the acknowledgments: 'Data discovery and access was facilitated by the Health Data Research UK Innovation Gateway - HDRUK Innovation Gateway  | Homepage ${year}.'`;

	let table = _getSubmissionDetails(
		userType,
		userName,
		userEmail,
		datasetTitles,
		initialDatasetTitles,
		submissionType,
		projectName || 'No project name set',
		isNationalCoreStudies,
		dateSubmitted,
		linkNationalCoreStudies
	);

	// Create json content payload for attaching to email
	const jsonContent = {
		applicationDetails: { projectName: projectName || 'No project name set', linkNationalCoreStudies, datasetTitles, dateSubmitted, applicantName: userName },
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
                      <h2 style="font-size: 18px; color: #29235c !important; margin: 0 0 15px 0;">${page}</h2>
                    </td>
                  </tr>`;

		// Safe People = [Applicant, Principle Investigator, ...]
		// Safe People to order array for applicant
		let sectionKeys;
		if (page.toUpperCase() === 'SAFE PEOPLE') sectionKeys = Object.keys({ ...parent }).sort();
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
			const excludedInputTypes = ['buttonInput'];
			for (let currentQuestion of questionsArr) {
				let { question, questionId, input: { type = '' } = {} } = currentQuestion;
				if (!excludedInputTypes.includes(type)) {
					let answer = answers[questionId] || `-`;
					table += `<tr>
                    <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom:1px solid #d0d3d4">${question}</td>
                    <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom:1px solid #d0d3d4; word-break: break-all;">${answer}</td>
                  </tr>`;
				}
			}
		}
		table += `</table></td></tr>`;
	}

	if (submissionDescription) {
		table += `
    <tr>
      <td align='left'>
        <h2 style="font-size: 18px; color: #29235c !important; margin: 0 0 5px -5px;">Message to data custodian:</h2>
        <p style="font-size: 14px; color: #3c3c3b; width: 100%; margin-left: -5px;">${submissionDescription}</p>
      </td>
    </tr>`;
	}

	table += `<tr>
  <td align='left'>
    <div style="margin-left: -5px;">
      ${_displayDARLink(applicationId)}
    </div>
  </td>
</tr>`;

	table += `<tr>
			<td align='left'>
				<p style="font-size: 14px; margin-left: -5px;">${gatewayAttributionPolicy}</p>
			</td>
		</tr>`;

	table += ` </tbody></table></div>`;

	return { html: table, jsonContent };
};

/**
 * [_groupByPageSection]
 *
 * @desc    This function will group all the  questions into the correct format for emailBuilder
 * @return  {Object} {Safe People: {Applicant: [], Applicant_U8ad: []}, Safe Project: {}}
 */
const _groupByPageSection = allQuestions => {
	// group by page [Safe People, Safe Project]
	let groupedByPage = groupBy(allQuestions, item => {
		return item.page;
	});

	// within grouped [Safe People: {Applicant, Applicant1, Something}]
	let grouped = forEach(groupedByPage, (value, key) => {
		groupedByPage[key] = groupBy(groupedByPage[key], item => {
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
					typeof uniqueId !== 'undefined' ? (obj[`email_${uniqueId}`] = validEmail) : (obj[`email`] = validEmail);
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
	return userType.toUpperCase() === 'DATACUSTODIAN' ? email : helper.censorEmail(email);
};

/**
 * [_getUserDetails]
 *
 * @desc    This function will return the user infromation from mongodb
 * @param   {Int}  98767876
 * @return  {Object} {fullname: 'James Swallow', email: 'james@gmail.com'}
 */
const _getUserDetails = async userObj => {
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

const _generateEmail = async (aboutApplication, questions, pages, questionPanels, questionAnswers, options) => {
	// reset questionList arr
	questionList = [];
	// set questionAnswers
	let flatQuestionAnswers = await _actualQuestionAnswers(questionAnswers, options);
	// unnest each questionPanel if questionSets
	let flatQuestionPanels = _unNestQuestionPanels(questionPanels);
	// unnest question flat
	let unNestedQuestions = _initalQuestionSpread(questions, pages, flatQuestionPanels);
	// assigns to questionList
	let fullQuestionSet = _getAllQuestionsFlattened(unNestedQuestions);
	// fullQuestions [SafePeople: {Applicant: {}, Applicant_aca: {}}, SafeProject:{}]
	let fullQuestions = _groupByPageSection([...questionList]);
	// build up  email with  values
	let { html, jsonContent } = _buildEmail(aboutApplication, fullQuestions, flatQuestionAnswers, options);
	// return email
	return { html, jsonContent };
};

const _generateAmendEmail = async (
	aboutApplication,
	questions,
	pages,
	questionPanels,
	questionAnswers,
	initialQuestionAnswers,
	options
) => {
	// filter out unchanged answers
	const changedAnswers = Object.keys(questionAnswers).reduce((obj, key) => {
		if (isEqual(questionAnswers[key], initialQuestionAnswers[key])) {
			return obj;
		}
		return { ...obj, [key]: questionAnswers[key] };
	}, {});

	// reset questionList arr
	questionList = [];
	// set questionAnswers
	let flatQuestionAnswers = await _actualQuestionAnswers(changedAnswers, options);
	// unnest each questionPanel if questionSets
	let flatQuestionPanels = _unNestQuestionPanels(questionPanels);
	// unnest question flat
	let unNestedQuestions = _initalQuestionSpread(questions, pages, flatQuestionPanels);
	// assigns to questionList
	_getAllQuestionsFlattened(unNestedQuestions);
	// filter to only changed questions
	let changedQuestions = questionList.filter(q => Object.keys(changedAnswers).some(key => key === q.questionId));
	let fullQuestions = _groupByPageSection([...changedQuestions]);
	// build up  email with  values
	let { html, jsonContent } = _buildEmail(aboutApplication, fullQuestions, flatQuestionAnswers, options);
	// return email
	return { html, jsonContent };
};

const _displayConditionalStatusDesc = (applicationStatus, applicationStatusDesc) => {
	if ((applicationStatusDesc && applicationStatus === 'approved with conditions') || applicationStatus === 'rejected') {
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

const _displayDARLink = accessId => {
	if (!accessId) return '';

	let darLink = `${process.env.homeURL}/data-access-request/${accessId}`;
	return `<a style="color: #475da7;" href="${darLink}">View application</a>`;
};

const _displayActivityLogLink = (accessId, publisher) => {
	if (!accessId) return '';

	const activityLogLink = `${process.env.homeURL}/account?tab=dataaccessrequests&team=${publisher}&id=${accessId}`;
	return `<a style="color: #475da7;" href="${activityLogLink}">View activity log</a>`;
};

const _generateDARStatusChangedEmail = options => {
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
                    Your data access request for ${projectName || datasetTitles} has been approved with conditions by ${publisher}. 
                    Summary information about your approved project will be included in the Gateway data use register. 
                    You will be notified as soon as this becomes visible and searchable on the Gateway.
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
            ${_displayConditionalStatusDesc(applicationStatus, applicationStatusDesc)}
            ${_displayDARLink(id)}
            </div>
          </div>`;
	return body;
};

const _generateDARClonedEmail = options => {
	let { id, projectId, projectName, datasetTitles, dateSubmitted, applicants, firstname, lastname } = options;
	dateSubmitted = isNil(dateSubmitted) || isEmpty(dateSubmitted) ? 'Not yet submitted' : moment(dateSubmitted).format('D MMM YYYY');

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
                      Data access request application has been duplicated
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                     ${firstname} ${lastname} has duplicated the contents of the following application into a new form.  
                     <p>
                        You will have received this message if you were a contributor to the original form, 
                        but you will not have access to the new form unless granted by the creator, 
                        at which point you will receive an additional notification.
                     </p>
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
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${dateSubmitted}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>`;
	return body;
};

const _generateDARDeletedEmail = options => {
	let { publisher, projectName, datasetTitles, applicants, firstname, lastname, createdAt } = options;
	createdAt = moment(createdAt).format('D MMM YYYY');

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
                    Data Access Request Application Deleted
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                    ${firstname} ${lastname} has deleted a data access request application.  
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
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Dataset(s)</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${datasetTitles}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Data custodian</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${publisher}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Applicants</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${applicants}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Created</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${createdAt}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>`;
	return body;
};

const _generateDARReturnedEmail = options => {
	let { id, projectName, publisher, datasetTitles, dateSubmitted, applicants } = options;
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
                      You’ve been requested to update a data access request application 
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                     ${publisher} has requested you update answers provided in a submitted data access request application.
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
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Dataset(s)</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${datasetTitles}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Submitted</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment(
													dateSubmitted
												).format('D MMM YYYY')}</td>
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
            <div style="padding: 0 40px 40px 40px;">
            ${_displayDARLink(id)}
            </div>
          </div>`;
	return body;
};

const _generateContributorEmail = options => {
	let { id, datasetTitles, projectName, projectId, change, actioner, applicants } = options;
	let header = `You've been ${change === 'added' ? 'added to' : 'removed from'} a data access request application`;
	let subheader = `${actioner} ${change} you as a contributor ${change === 'added' ? 'to' : 'from'} a data access request application. ${
		change == 'added' ? 'Contributors can exchange private notes, make edits, invite others and submit the application.' : ''
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

const _generateStepOverrideEmail = options => {
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
              <div style="padding: 0 40px 40px 40px;">${_displayDARLink(id)}</div>
              </div>`;
	return body;
};

const _generateNewReviewPhaseEmail = options => {
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
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
													datasetTitles || 'No dataset titles'
												}</td>
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

const _generateWorkflowCreated = options => {
	let { workflowName, steps, createdAt, actioner } = options;

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
                      A new Workflow has been created.
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                      ${actioner} has created ${workflowName} on ${moment(createdAt).format('D MMM YYYY')}
                    </th>
                  </tr>
                </thead>
                <tbody>`;

	for (let step of steps) {
		let { reviewers = [], sections = [], stepName = '' } = step;
		let stepReviewers = _getStepReviewers(reviewers);
		let stepSections = _getStepSections(sections);
		table += `<tr>
                            <td bgcolor="#fff" style="padding: 10px 0 10px 0; border:0;">
                              <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                  <td>
                                    <h2 style="font-size: 16px; color :#29235c; margin:'10px 0 15px 0'">${stepName}</h2>
                                  </td>
                                </tr>
                                <tr>
                                  <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Review Sections</td>
                                  <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${stepSections}</td>
                                </tr>
                                <tr>
                                  <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Reviewers</td>
                                  <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${stepReviewers}</td>
                                </tr>
                              </table>
                            </td>
                          </tr>`;
	}

	table += `</tbody>
                        </table>
                        </div>`;

	return table;
};

const _generateWorkflowAssigned = options => {
	let { id, projectId, workflowName, projectName, applicants, steps, actioner, datasetTitles, dateSubmitted } = options;

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
                        Workflow has been assigned.
                      </th>
                    </tr>
                    <tr>
                      <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                        ${actioner} has assigned ${workflowName} to a Data Access Request
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td bgcolor="#fff" style="padding: 10px 0 10px 0; border:0;">
                      <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                          <td>
                            <h2 style="font-size: 16px; color :#29235c; margin:'10px 0 15px 0'">Application Details</h2>
                          </td>
                        </tr>
                        <tr>
                          <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project</td>
                          <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
														projectName || 'No project name'
													} </td>
                        </tr>
                        <tr>
                          <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Project Id</td>
                          <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
														projectId || id
													}</td>
                        </tr>
                        <tr>
                          <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Dataset Titles</td>
                          <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
														datasetTitles || 'No dataset titles'
													} </td>
                        </tr>
                        <tr>
                          <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Applicants</td>
                          <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${applicants} </td>
                        </tr>
                        <tr>
                          <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Submitted</td>
                          <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment(
														dateSubmitted
													).format('D MMM YYYY')}</td>
                        </tr>
                      </table>
                    </td>
                  </tr>`;

	for (let step of steps) {
		let { reviewers = [], sections = [], stepName = '' } = step;
		let stepReviewers = _getStepReviewers(reviewers);
		let stepSections = _getStepSections(sections);
		table += `<tr>
                              <td bgcolor="#fff" style="padding: 10px 0 10px 0; border:0;">
                                <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                                  <tr>
                                    <td>
                                      <h2 style="font-size: 16px; color :#29235c; margin:'10px 0 15px 0'">${stepName}</h2>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Review Sections</td>
                                    <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${stepSections}</td>
                                  </tr>
                                  <tr>
                                    <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Reviewers</td>
                                    <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${stepReviewers}</td>
                                  </tr>
                                </table>
                              </td>
                            </tr>`;
	}

	table += `</tbody>
                            </table>
                            <div style="padding: 0 40px 40px 40px;">
                              ${_displayDARLink(id)}
                            </div>
                          </div>`;

	return table;
};

const _generateReviewDeadlineWarning = options => {
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
                     The following data access request application is approaching the review deadline of ${moment(dateDeadline).format(
												'D MMM YYYY'
											)}.
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

const _generateReviewDeadlinePassed = options => {
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

const _generateFinalDecisionRequiredEmail = options => {
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
              <div style="padding: 0 40px 40px 40px;">${_displayDARLink(id)}</div>
              </div>`;
	return body;
};

const _generateRemovedFromTeam = options => {
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

const _displayViewEmailNotifications = publisherId => {
	let link = `${process.env.homeURL}/account?tab=teamManagement&innertab=notifications&team=${publisherId}`;
	return `<table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
            <tr>
              <td style=" font-size: 14px; color: #3c3c3b; padding: 45px 5px 10px 5px; text-align: left; vertical-align: top;">
                <a style="color: #475da7;" href="${link}">View email notifications</a>
              </td>
            </tr>
          </table>`;
};

const _formatEmails = emails => {
	return [...emails].map((email, i) => ` ${email}`);
};

const _generateTeamNotificationEmail = options => {
	let { managerName, notificationRemoved, emailAddresses, header, disabled, publisherId } = options;
	let formattedEmails = _formatEmails(emailAddresses);

	let body = `<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; max-width:700px; margin: 0 auto;">
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
                     ${
												notificationRemoved
													? `${managerName} ${constants.teamNotificationEmailContentTypes.TEAMEMAILSUBHEADEREMOVE}`
													: `${managerName} ${constants.teamNotificationEmailContentTypes.TEAMEMAILSUBHEADERADD}`
											}
                    </th>
                  </tr>
                </thead>
                <tbody>
                <tr>
                  <td bgcolor="#fff" style="padding: 0; border: 0;">
                    <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 140px; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Team email address</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 450px; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">
													${formattedEmails}
												</td>
                      </tr>
                    </table>
                    ${disabled ? _generateTeamEmailRevert(notificationRemoved) : ''}
                    ${_displayViewEmailNotifications(publisherId)}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>`;
	return body;
};

const _generateTeamEmailRevert = notificationRemoved => {
	return `<table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
            <tr>
              <td style=" font-size: 14px; color: #3c3c3b; padding: 45px 5px 10px 5px; text-align: left; vertical-align: top;">
                If you had stopped emails being sent to your gateway log in email address and no team email address is now active, your emails will have reverted back to your gateway log in email.
              </td>
            </tr>
          </table>`;
};

const _generateAddedToTeam = options => {
	let { teamName, role } = options;
	let header = `You've been added to the ${teamName} team as a ${role} on the HDR Innovation Gateway`;
	let subheader = ``;
	if (role === constants.roleTypes.MANAGER) {
		subheader = `You will now be able to create and manage Data Access Request workflows, process applications, send messages, and manage the profile area relating to this team, including the ability to add and remove new members.`;
	} else if (role === constants.roleTypes.REVIEWER) {
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

const _generateNewTeamManagers = options => {
	let { team } = options;

	let body = `<div>
						<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
							<table
							align="center"
							border="0"
							cellpadding="0"
							cellspacing="40"
							width="700"
							word-break="break-all"
							style="font-family: Arial, sans-serif">
								<thead>
									<tr>
										<th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
                      <span>New team added</span>
										</th>
									</tr>
								</thead>
								<tbody style="overflow-y: auto; overflow-x: hidden;">
                  <tr>
                    <td style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                      <p>
                        The team ${team} has been added to the Gateway. You were assigned as a team manager and can now:
                        <br />
                        <ul>
                          <li>Manage members</li>
                          <li>Create and assign workflows</li>
                          <li>Review assigned data access request applications</li>
                          <li>Make the final decision on data access request applications</li>
                        </ul>
                      </p>
                    </td>
                  </tr>
								</tbody>
							</table>
						</div>
					</div>`;
	return body;
};

const _generateNewDARMessage = options => {
	let { id, projectName, datasetTitles, applicants, firstname, lastname, messageBody, questionWithAnswer } = options;
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
                      New message about an application
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                      ${firstname} ${lastname} sent a message regarding their application form
                    </th>
                  </tr>
                </thead>
                <tbody>
                <tr>
                  <td bgcolor="#fff" style="padding: 0; border: 0;">
                    <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Application name</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
													projectName || 'No project name set'
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
            
              <thead>
              <tr>
                <th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
                  Message from ${firstname} ${lastname}
                </th>
              </tr>
            </thead>
            <tbody>
            <tr>
              <td bgcolor="#fff" style="padding: 0; border: 0;">
                <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                  <tr>
                    <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top;">${messageBody}</td>
                  </tr>
                  <tr>
                    <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top;">
                        ${_displayDARLink(id)}
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </tbody>
              
           
                <thead>
                  <tr>
                    <th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
                      ${questionWithAnswer.page}
                    </th>
                  </tr>
                  <tr>
                  <th style="border: 0; color: #29235c; font-size: 18px; text-align: left;">
                    ${questionWithAnswer.questionPanel}
                    </th>
                  </tr>
                </thead>
                <tbody>
                <tr>
                  <td bgcolor="#fff" style="padding: 0; border: 0;">
                    <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Question</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
													questionWithAnswer.question
												}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Answer</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${
													questionWithAnswer.answer
												}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>`;
	return body;
};

const _generateMetadataOnboardingSumbitted = options => {
	let { name, publisher } = options;

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
                    Dataset version available for review
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                    The dataset, ${name}, has been submitted to the Gateway by ${publisher}. You can review and approve or reject this dataset version from application view.
                  </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                    <a style="color: #475da7;" href="${process.env.homeURL}/account?tab=datasets&team=admin">View datasets pending approval</a>
                  </th>
                  </tr>
                </thead>
                </table>
          </div>`;
	return body;
};

const _generateMetadataOnboardingApproved = options => {
	let { name, publisherId, comment } = options;

	let commentHTML = '';

	if (!_.isEmpty(comment)) {
		commentHTML = `<tr>
      <th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
        Approval comment
      </th>
    </tr>
    <tr>
      <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
        ${comment}
      </th>
    </tr>`;
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
                    Your dataset version has been approved and is now active
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                    The submitted version of ${name} has been reviewed and approved by the HDRUK admins. It is now active, searchable and available to request access to on the Innovation Gateway. You may view and create a new version of the dataset in your dataset dashboard.
                  </th>
                  </tr>
                  ${commentHTML}
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                    <a style="color: #475da7;" href="${process.env.homeURL}/account?tab=datasets&team=${publisherId}">View dataset dashboard</a>
                  </th>
                  </tr>
                </thead>
                </table>
          </div>`;
	return body;
};

const _generateMetadataOnboardingRejected = options => {
	let { name, publisherId, comment } = options;

	let commentHTML = '';

	if (!_.isEmpty(comment)) {
		commentHTML = `<tr>
                    <th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
                      Comment from reviewer:
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                      "${comment}"
                    </th>
                  </tr>`;
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
                      Your dataset version requires revision before it can be accepted on the Gateway
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                      Thank you for submitting ${name}, which has been reviewed by the team at HDR UK. The dataset version cannot be approved for release on the Gateway at this time. Please look at the comment from the reviewer below and make any necessary changes on a new version of the dataset before resubmitting.
                    </th>
                  </tr>
                  ${commentHTML}
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                    <a style="color: #475da7;" href="${process.env.homeURL}/account?tab=datasets&team=${publisherId}">View dataset dashboard</a>
                  </th>
                  </tr>
                </thead>
                </table>
          </div>`;
	return body;
};

const _generateMetadataOnboardingDraftDeleted = options => {
	let { draftDatasetName } = options;

	let body = `<div>
						<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
							<table
							align="center"
							border="0"
							cellpadding="0"
							cellspacing="40"
							width="700"
							word-break="break-all"
							style="font-family: Arial, sans-serif">
								<thead>
									<tr>
										<th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
                      <span>Draft dataset deleted</span>
										</th>
									</tr>
								</thead>
								<tbody style="overflow-y: auto; overflow-x: hidden;">
                  <tr>
                    <td style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                      <p>
                        The draft version of ${draftDatasetName} has been deleted.
                      </p>
                    </td>
                  </tr>
								</tbody>
							</table>
						</div>
					</div>`;
	return body;
};

const _generateMetadataOnboardingDuplicated = options => {
	let { name, publisher, version } = options;

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
                      Dataset duplicated
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                      ${publisher.name} has duplicated ${version} of ${name}.
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                    <a style="color: #475da7;" href="${process.env.homeURL}/account?tab=datasets&team=${publisher.identifier}">View dataset dashboard</a>
                  </th>
                  </tr>
                </thead>
                </table>
          </div>`;
	return body;
};

const _generateMessageNotification = options => {
	let { firstMessage, firstname, lastname, messageDescription, openMessagesLink } = options;

	let body = `<div>
						<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
							<table
							align="center"
							border="0"
							cellpadding="0"
							cellspacing="40"
							width="700"
							word-break="break-all"
							style="font-family: Arial, sans-serif">
								<thead>
									<tr>
										<th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
										${_.isEmpty(firstMessage) ? `New message from ${firstname} ${lastname}` : `Data access request enquiry from ${firstname} ${lastname}`}
										</th>
										</tr>
										<tr>
										<th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
											<p>${messageDescription.replace(/\n/g, '<br />')}</p>
										</th>
									</tr>
								</thead>
								<tbody style="overflow-y: auto; overflow-x: hidden;">
									<tr style="width: 100%; text-align: left;">
										<td style=" font-size: 14px; color: #3c3c3b; padding: 5px 5px; width: 50%; text-align: left; vertical-align: top;">
											<a href=${openMessagesLink}>View Messages</a>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>`;
	return body;
};

const _generateEntityNotification = options => {
	let { resourceType, resourceName, resourceLink, subject, rejectionReason, activeflag, type, resourceAuthor } = options;
	let authorBody;
	if (activeflag === 'active') {
		authorBody = `${resourceName} ${resourceType} has been approved by the HDR UK admin team and can be publicly viewed on the gateway, including in search results.`;
	} else if (activeflag === 'archive') {
		authorBody = `${resourceName} ${resourceType} has been archived by the HDR UK admin team.`;
	} else if (activeflag === 'rejected') {
		authorBody = `${resourceName} ${resourceType} has been rejected by the HDR UK admin team. <br /><br />  Reason for rejection: ${rejectionReason}`;
	} else if (activeflag === 'add') {
		authorBody = `${resourceName} ${resourceType} has been submitted to the HDR UK admin team for approval.`;
	} else if (activeflag === 'edit') {
		authorBody = `${resourceName} ${resourceType} has been edited, the updated version can now be viewed on the gateway.`;
	}

	let dashboardLink = process.env.homeURL + '/account?tab=' + resourceType + 's';

	let body = `<div>
						<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
							<table
							align="center"
							border="0"
							cellpadding="0"
							cellspacing="40"
							width="700"
							word-break="break-all"
							style="font-family: Arial, sans-serif">
								<thead>
									<tr>
										<th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
                      ${!_.isEmpty(type) && type === 'admin' ? `A new ${resourceType} has been added and is ready for review` : ``}
                      ${!_.isEmpty(type) && type === 'author' ? `${subject}` : ``}
                      ${
												!_.isEmpty(type) && type === 'co-author'
													? `${resourceAuthor} added you as an author of the ${resourceType} ${resourceName}`
													: ``
											}
										</th>
										</tr>
										<tr>
										<th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
											<p>
                      ${
												!_.isEmpty(type) && type === 'admin'
													? `${resourceName} ${resourceType} has been added and is pending a review. View and then either approve or reject via the link below.`
													: ``
											}
                      ${!_.isEmpty(type) && type === 'author' ? authorBody : ``}
                      ${
												!_.isEmpty(type) && type === 'co-author'
													? `${resourceAuthor} added you as an author of the ${resourceType} ${resourceName}`
													: ``
											}
                      </p>
										</th>
									</tr>
								</thead>
								<tbody style="overflow-y: auto; overflow-x: hidden;">
									<tr style="width: 100%; text-align: left;">
										<td style=" font-size: 14px; color: #3c3c3b; padding: 5px 5px; width: 50%; text-align: left; vertical-align: top;">
                    ${!_.isEmpty(type) && type === 'admin' ? `<a href=${dashboardLink}>View ${resourceType}s dashboard</a>` : ``}
                    ${!_.isEmpty(type) && type === 'author' ? `<a href=${resourceLink}>View ${resourceType}</a>` : ``}
                    ${!_.isEmpty(type) && type === 'co-author' ? `<a href=${resourceLink}>View ${resourceType}</a>` : ``}
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>`;
	return body;
};

const _generateActivityLogManualEventCreated = options => {
	const { id, userName, description, publisher, timestamp, projectName } = options;
	const dateTime = moment(timestamp).format('DD/MM/YYYY, HH:mmA');
	const body = `<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
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
                      A new event has been added to an activity log
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                     ${userName} (${publisher}) has added a new event to the activity log of '${
		projectName || `No project name set`
	}' data access request application.
                    </th>
                  </tr>
                </thead>
                <tbody>
                <tr>
                  <td bgcolor="#fff" style="padding: 0; border: 0;">
                    <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Event</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${description}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Date and time</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${dateTime}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
            <div style="padding: 0 40px 40px 40px;">
            ${_displayActivityLogLink(id, publisher)}
            </div>
          </div>`;
	return body;
};

const _generateActivityLogManualEventDeleted = options => {
	const { id, userName, description, publisher, timestamp, projectName } = options;
	const dateTime = moment(timestamp).format('DD/MM/YYYY, HH:mmA');
	const body = `<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
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
                      An event has been deleted from an activity log
                    </th>
                  </tr>
                  <tr>
                    <th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
                     ${userName} (${publisher}) has deleted the following event from the activity log of '${
		projectName || `No project name set`
	}' data access request application.
                    </th>
                  </tr>
                </thead>
                <tbody>
                <tr>
                  <td bgcolor="#fff" style="padding: 0; border: 0;">
                    <table border="0" border-collapse="collapse" cellpadding="0" cellspacing="0" width="100%">
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Event</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${description}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 30%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Date and time</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 70%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${dateTime}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
            <div style="padding: 0 40px 40px 40px;">
            ${_displayActivityLogLink(id, publisher)}
            </div>
          </div>`;
	return body;
};

/**
 * [_sendEmail]
 *
 * @desc    Send an email to an array of users using Twilio SendGrid
 * @param   {Object}  context
 */
const _sendEmail = async (to, from, subject, html, allowUnsubscribe = true, attachments = []) => {
	// 1. Apply SendGrid API key from environment variable
	sgMail.setApiKey(process.env.SENDGRID_API_KEY);

	// 2. Ensure any duplicates recieve only a single email
	const recipients = [...new Map(to.map(item => [item['email'], item])).values()];

	// 3. Build each email object for SendGrid extracting email addresses from user object with unique unsubscribe link (to)
	for (let recipient of recipients) {
		let body = _generateEmailHeader + html + _generateEmailFooter(recipient, allowUnsubscribe);
		let msg = {
			to: recipient.email,
			from: from,
			subject: subject,
			html: body,
			attachments,
		};

		// 4. Send email using SendGrid
		await sgMail.send(msg, false, err => {
			if (err) {
				Sentry.addBreadcrumb({
					category: 'SendGrid',
					message: 'Sending email failed',
					level: Sentry.Severity.Warning,
				});
				Sentry.captureException(err);
			}
		});
	}
};

/**
 * [_sendIntroEmail]
 *
 * @desc    Send an intro Email upon user registration
 * @param   {Object}  message to from, templateId
 */
const _sendIntroEmail = msg => {
	// 1. Apply SendGrid API key from environment variable
	sgMail.setApiKey(process.env.SENDGRID_API_KEY);
	// 2. Send email using SendGrid
	sgMail.send(msg, false, err => {
		if (err) {
			Sentry.addBreadcrumb({
				category: 'SendGrid',
				message: 'Sending email failed - Intro',
				level: Sentry.Severity.Warning,
			});
			Sentry.captureException(err);
		}
	});
};

const _generateEmailHeader = `
    <img src="https://storage.googleapis.com/hdruk-gateway_prod-cms/web-assets/HDRUK_logo_colour.png" alt="HDR UK Logo" width="127" height="63" style="display: block; margin-left: auto; margin-right: auto; margin-bottom: 24px; margin-top: 24px;"></img>
  `;

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

export default {
	//General
	sendEmail: _sendEmail,
	sendIntroEmail: _sendIntroEmail,
	generateEmailFooter: _generateEmailFooter,
	generateAttachment: _generateAttachment,
	//DAR
	generateEmail: _generateEmail,
	generateAmendEmail: _generateAmendEmail,
	generateDARReturnedEmail: _generateDARReturnedEmail,
	generateDARStatusChangedEmail: _generateDARStatusChangedEmail,
	generateDARClonedEmail: _generateDARClonedEmail,
	generateDARDeletedEmail: _generateDARDeletedEmail,
	generateContributorEmail: _generateContributorEmail,
	generateStepOverrideEmail: _generateStepOverrideEmail,
	generateNewReviewPhaseEmail: _generateNewReviewPhaseEmail,
	generateReviewDeadlineWarning: _generateReviewDeadlineWarning,
	generateReviewDeadlinePassed: _generateReviewDeadlinePassed,
	generateFinalDecisionRequiredEmail: _generateFinalDecisionRequiredEmail,
	generateTeamNotificationEmail: _generateTeamNotificationEmail,
	generateRemovedFromTeam: _generateRemovedFromTeam,
	generateAddedToTeam: _generateAddedToTeam,
	generateNewTeamManagers: _generateNewTeamManagers,
	generateNewDARMessage: _generateNewDARMessage,
	//Workflows
	generateWorkflowAssigned: _generateWorkflowAssigned,
	generateWorkflowCreated: _generateWorkflowCreated,
	//Metadata Onboarding
	generateMetadataOnboardingSumbitted: _generateMetadataOnboardingSumbitted,
	generateMetadataOnboardingApproved: _generateMetadataOnboardingApproved,
	generateMetadataOnboardingRejected: _generateMetadataOnboardingRejected,
	generateMetadataOnboardingDraftDeleted: _generateMetadataOnboardingDraftDeleted,
  generateMetadataOnboardingDuplicated: _generateMetadataOnboardingDuplicated,
	//generateMetadataOnboardingArchived: _generateMetadataOnboardingArchived,
	//generateMetadataOnboardingUnArchived: _generateMetadataOnboardingUnArchived,
	//Messages
	generateMessageNotification: _generateMessageNotification,
	generateEntityNotification: _generateEntityNotification,
	//ActivityLog
	generateActivityLogManualEventCreated: _generateActivityLogManualEventCreated,
	generateActivityLogManualEventDeleted: _generateActivityLogManualEventDeleted,
};
