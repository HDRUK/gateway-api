import { Storage } from '@google-cloud/storage';

const PizZip = require('pizzip');
const Docxtemplater = require('docxtemplater');
const fs = require('fs');
const path = require('path');

const _getTemplate = async templateName => {
	new Promise(async resolve => {
		// new storage obj
		const storage = new Storage();
		//  set option for file dest
		let options = {
			// the path to which the file should be downloaded
			destination: __dirname + '/template.docx',
		};
    // get file from google bucket
		resolve(storage.bucket(process.env.GOOGLE_WORD_OUTBOUND_STORAGE_BUCKET).file(templateName).download(options));
	});
};

const _generateRestructuredQuestionAnswers = async questionAnswers => {
	let formatedQuestionAnswers = {};

	let repeatableSectionTitles = [
		'safepeopleotherindividuals',
		'safeprojectfunderinformation',
		'safeprojectsponsorinformation',
		'safeprojectdeclarationofinterest',
	];

	// pull all non-repeatable sections from questionAnswers into 'flatQuestionAnswers' object
	let flatQuestionAnswers = getFlatSections(questionAnswers);
	function getFlatSections(obj) {
		return Object.fromEntries(
			Object.entries(obj).filter(
				([key, val]) =>
					!key.includes('safepeopleotherindividuals') &&
					!key.includes('safeprojectfunderinformation') &&
					!key.includes('safeprojectsponsorinformation') &&
					!key.includes('safeprojectdeclarationofinterest')
			)
		);
	}

	// pull all repeatable sections from questionAnswers into 'repeatableSectionsAnswers' object
	let repeatableSectionsAnswers = getRepeatableSections(questionAnswers);
	function getRepeatableSections(obj) {
		return Object.fromEntries(
			Object.entries(obj).filter(([key, val]) => repeatableSectionTitles.some(sectionTitle => key.includes(sectionTitle)))
		);
	}

	let formattedRepeatableSectionQuestionAnswers = new Map();
	// loop through each repeatable section question/answer - format it and push into correct object in correct section array
	for (const question in repeatableSectionsAnswers) {
		// get the title of the section aka array based on which section title the questionid contains
		let sectionTitle = '';
		for (const repeatableSectionTitle of repeatableSectionTitles) {
			if (question.includes(repeatableSectionTitle)) {
				sectionTitle = repeatableSectionTitle;
			}
		}

		// get suffix and set as appendedIdentifier
		let appendedIdentifier = question.substring(0, question.indexOf('_') === -1)
			? sectionTitle
			: question.substring(question.indexOf('_'), question.length);

		// get the question id in the correct format for mapping to template ie. remove suffix where it exists
		let formattedQuestionId = question.substring(0, question.indexOf('_') === -1)
			? [question]
			: [question.substring(0, question.indexOf('_'))];

		// create a map object - this will then be populated with all 4 repeatable sections in correct format
		// add fields into individual objects (individualObject) then add to the section map (sectionObjectMap)
		if (question.includes(sectionTitle)) {
			let sectionObjectMap;
			let individualObject;
			if (formattedRepeatableSectionQuestionAnswers.has(sectionTitle)) {
				sectionObjectMap = formattedRepeatableSectionQuestionAnswers.get(sectionTitle);
				if (sectionObjectMap.has(appendedIdentifier)) {
					individualObject = sectionObjectMap.get(appendedIdentifier);
					individualObject = { ...individualObject, [formattedQuestionId]: repeatableSectionsAnswers[question] };
				} else {
					individualObject = { [formattedQuestionId]: repeatableSectionsAnswers[question] };
				}
			} else {
				sectionObjectMap = new Map();
				individualObject = { [formattedQuestionId]: repeatableSectionsAnswers[question] };
			}
			sectionObjectMap.set(appendedIdentifier, individualObject);
			formattedRepeatableSectionQuestionAnswers.set(sectionTitle, sectionObjectMap);
		}
	}

	let formattedRepeatableSections = {};
	formattedRepeatableSectionQuestionAnswers.forEach((sectionMap, sectionName) => {
		let sectionArray = [];
		for (const individualObject of sectionMap.values()) {
			sectionArray.push(individualObject);
		}
		formattedRepeatableSections = { ...formattedRepeatableSections, [sectionName]: sectionArray };
	});

	// spread in the updated/restructured repeatable sections and the original remaining questionAnswers
	formatedQuestionAnswers = { ...formattedRepeatableSections, ...flatQuestionAnswers };
	return formatedQuestionAnswers;
};

const _generatePopulatedTemplate = async formattedObject => {
  function sleep(ms) {
    return new Promise(resolve => {
      setTimeout(resolve, ms);
    });
  }

  let content;
  for (let counter = 0; counter < 5; counter++) {
    if(fs.existsSync(`${__dirname}/template.docx`)){
      await sleep(1000);

      // Load the docx file as binary content
      content = fs.readFileSync(path.resolve(__dirname, 'template.docx'), 'binary');
      const zip = new PizZip(content);

      const doc = new Docxtemplater(zip, {
        paragraphLoop: true,
        linebreaks: true,
        //replace undefined aka unmapped values with empty string
        nullGetter() {
          return '';
        },
      });

      // render the document with values mapped from the formatted object and save as 'populatedtemplate.docx'
      doc.render(formattedObject);
      const buf = doc.getZip().generate({ type: 'nodebuffer' });
      fs.writeFileSync(path.resolve(__dirname, 'populatedtemplate.docx'), buf);

      break;
    } else {
        await sleep(1000);
    }
  }
};

export default {
	getTemplate: _getTemplate,
    generateRestructuredQuestionAnswers: _generateRestructuredQuestionAnswers,
	generatePopulatedTemplate: _generatePopulatedTemplate
};
