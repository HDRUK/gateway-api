import _ from 'lodash';
import moment from 'moment';

let questionList = [];
let parent;

/**
 * [_initalQuestionSpread Un-nests the questions from each object[questions]]
 *
 * @return  {Array<Object>} [{question}, {}]
 */
const _initalQuestionSpread = (questions, pages, questionPanels) => {
  let flatQuestionList = [];

  if (!questions) return;
  for (let questionSet of questions) {
    let { questionSetId } = questionSet;
    if (questionSet.hasOwnProperty('questions')) {
      for (let question of questionSet.questions) {
        // pass in questionPanels
        let questionPanel = [...questionPanels].find(
          (i) => i.panelId === questionSetId
        );
        let page = [...pages].find((i) => i.pageId === questionPanel.pageId);
        let obj = {
          page: page.title,
          section: questionPanel.navHeader,
          questionSetId,
          ...question
        };
        flatQuestionList = [...flatQuestionList, obj];
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
      // console.log(questionObj);
      if (
        questionObj.hasOwnProperty('page') &&
        questionObj.hasOwnProperty('section')
      ) {
        let { page, section } = questionObj;
        // set the parent page and parent section as nested wont have reference to its parent
        parent = { page, section };
      }
      let { questionId, question } = questionObj;
      questionList = [
        ...questionList,
        { questionId, question, page: parent.page, section: parent.section }
      ];
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


const _buildSubjectTitle = (user, title) => {
  if (user === 'dataCustodian') {
    return `Someone has submitted an application to access ${title} dataset. Please let the applicant know as soon as there is progress in the review of their submission.`;
  } else {
    return `You have requested access to ${title}. The custodian will be in contact about the application.`;
  }
};

/**
 * [_buildEmail Build a string for the email template]
 *
 * @param   {<Object>}  questions
 * @param   {<Object>}  answers
 * @param   {<Object>}  options
 * @return  {<String>} Questions Answered
 */
const _buildEmail = (fullQuestions, questionAnswers, options) => {
  let parent;
  let { userType, userName, userEmail, custodianEmail, dataSetTitle } = options;
  const hdrukEmail = `enquiry@healthdatagateway.org`;
  const dataCustodianEmail = process.env.DATA_CUSTODIAN_EMAIL || custodianEmail;
  let subject = _buildSubjectTitle(userType, dataSetTitle);
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
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Dataset</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${dataSetTitle}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Date of submission</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${moment().format('D MMM YYYY HH:mm')}</td>
                      </tr>
                      <tr>
                        <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">Applicant</td>
                        <td style=" font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom: 1px solid #d0d3d4;">${userName}, ${userEmail}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
               `;

  let pageCount = 0;
  // [Safe People, SafeProject]
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
    let sectionKeys = Object.keys(parent);
    // styling for last child
    let sectionCount = 0;
    // console.log(`SECTIONS: ${sectionKeys}`);
    for (let section of sectionKeys) {
      let questionsArr = questionTree[page][section];
      sectionCount++;
      table += `<tr style="width: 600">
                    <!-- Key Section --> 
                    <td><h3 style="font-size: 16px; color :#29235c; margin: ${
                      sectionCount !== 1 ? '25px 0 0 0;' : '10px 0 0 0;'
                    }">${section}</h3></td>
                </tr>`;
      // console.log(`${section} ${JSON.stringify(questionsArr, null, 2)}`);
      for (let question of questionsArr) {
        let answer = answers[question.questionId] || `{{empty}}`;
        table += `<tr>
                    <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom:1px solid #d0d3d4">${question.question}</td>
                    <td style="font-size: 14px; color: #3c3c3b; padding: 10px 5px; width: 50%; text-align: left; vertical-align: top; border-bottom:1px solid #d0d3d4">${answer}</td>
                  </tr>`;
      }
    }
    table += `</table></td></tr>`;
  }

  table += ` </tbody></table></div>`;

  let msg = {
    from: hdrukEmail,
    to: userType === 'dataCustodian' ? dataCustodianEmail : userEmail,
    subject: `Enquires for ${dataSetTitle} dataset healthdatagateway.org`,
    templateId: 'd-29307d4931ba4b5585d4fc274f9371e7',
    dynamicTemplateData: {
      body: table
    }
  };

  return msg;
};

const _groupByPageSection = (allQuestions) => {
  let groupedByPage = _.groupBy(allQuestions, (item) => {
    return item.page;
  });

  let grouped = _.forEach(groupedByPage, (value, key) => {
    groupedByPage[key] = _.groupBy(groupedByPage[key], (item) => {
      return item.section;
    });
  });

  return grouped;
};

const _generateEmail = ( 
  questions,
  pages,
  questionPanels,
  questionAnswers,
  options
) => {
  let unNestedQuestions = _initalQuestionSpread(
    questions,
    pages,
    questionPanels
  );
  questionList = [];
  let fullQuestionSet = _getAllQuestionsFlattened(unNestedQuestions);
  let fullQuestions = _groupByPageSection([...questionList]);
  let email = _buildEmail(fullQuestions, questionAnswers, options);
  return email;
};

export default {
  generateEmail: _generateEmail
};
