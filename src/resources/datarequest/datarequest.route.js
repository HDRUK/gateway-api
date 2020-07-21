import express from 'express';
import passport from 'passport';
import axios from 'axios';
import { DataRequestModel } from './datarequest.model';
import { DataRequestSchemaModel } from './datarequest.schemas.model';
import emailGenerator from '../utilities/emailGenerator.util';
const sgMail = require('@sendgrid/mail');
const notificationBuilder = require('../utilities/notificationBuilder');

const router = express.Router();

// @route   GET api/v1/data-access-request/dataset/:datasetId
// @desc    GET Access request for user
// @access  Private
router.get('/dataset/:dataSetId', passport.authenticate('jwt'), async (req, res) => {
  let accessRecord;
  let data = {};
   try {
      // 1. Get dataSetId from params
      let {params: {dataSetId}} = req;
      // 2. Get the userId
      let {id: userId} = req.user;
      // 3. Find the matching record 
      accessRecord = await DataRequestModel.findOne({dataSetId, userId});
      // 4. if no record create it and pass back
      if (!accessRecord) { 
         // 1. GET the template from the custodian
         const accessRequestTemplate = await DataRequestSchemaModel.findOne({ $or: [{dataSetId}, {dataSetId: 'default'}] , status: 'active' }).sort({createdAt: -1});
         
         if(!accessRequestTemplate) {
            return res
            .status(400)
            .json({status: 'error', message: 'No Data Access request schema.' });
         }
         // 2. Build up the accessModel for the user
         let {jsonSchema, version} = accessRequestTemplate;
         // 4. create new DataRequestModel
         let record = new DataRequestModel({
            version,
            userId,
            dataSetId,
            jsonSchema,
            questionAnswers: "{}",
            applicationStatus: "inProgress"
         });
         // 3. save record
         await record.save();
         // 4. return record
         data = {...record._doc};
       } else {
         data = {...accessRecord._doc};
       }
       console.log(data);
      return res.status(200).json({status: 'success', data: {...data, jsonSchema: JSON.parse(data.jsonSchema), questionAnswers: JSON.parse(data.questionAnswers)}});
   }
   catch (err) {
      console.log(err.message);
      res.status(500).json({status: 'error', message: err});
   };
});

// @route   PATCH api/v1/data-access-request/:id
// @desc    Update request record answers
// @access  Private
router.patch('/:id', passport.authenticate('jwt'), async (req, res) => {
  try {
    // 1. id is the _id object in mongoo.db not the generated id or dataset Id
    const {
      params: { id },
    } = req;
    // 2. find data request by _id and update via body
    let accessRequestRecord = await DataRequestModel.findByIdAndUpdate(id, req.body, { new: true });
    // 3. check access record
    if (!accessRequestRecord) {
      return res.status(400).json({ status: 'error', message: 'Data Access Request not found.' });
    }

    // 4. return new data object
    return res.status(200).json({
      status: 'success',
      data: { ...accessRequestRecord._doc, questionAnswers: JSON.parse(accessRequestRecord.questionAnswers) },
    });
  } catch (err) {
    console.log(err.message);
    res.status(500).json({ status: 'error', message: err });
  }
});

// @route   POST api/v1/data-access-request/:id
// @desc    Update request record
// @access  Private
router.post('/:id', passport.authenticate('jwt'), async (req, res) => {
  let metadataCatalogue = process.env.metadataURL || 'https://metadata-catalogue.org/hdruk';
  // 1. id is the _id object in mongoo.db not the generated id or dataset Id
  let { params: { id }} = req;
  try {
    const application = await DataRequestModel.findOne({ _id: id });
    if (application) {
      // destructure
      let {questionAnswers, jsonSchema, dataSetId} = application;
      // parse schema
      let {pages, questionPanels, questionSets: questions} = JSON.parse(jsonSchema);
      // parse questionAnswers
      let answers = JSON.parse(questionAnswers);
      // GET dataset from metadatacatalogue we need the contactPoint, author and 
      const response = await axios.get(`${metadataCatalogue}/api/facets/${dataSetId}/profile/uk.ac.hdrukgateway/HdrUkProfilePluginService`);
      if(!response) {
        return res.status(400).json({ status: 'error', message: 'No dataset from meta data catalogue.' });
      }
      let { firstname, lastname, email } = req.user
      // DataSet details - if no descsription use abstract
      let { data: { contactPoint, publisher, description, abstract, title } } = response;
      // declare recipientTypes, static until otherwise
      const emailRecipientTypes = ['requester', 'dataCustodian'];
      // set options
      let options = {userType: '', userEmail: email, userName: `${firstname} ${lastname}`, custodianEmail: contactPoint , dataSetTitle: title, publisher, description, abstract };
      console.log(options);
      // set sendGrid key
      sgMail.setApiKey(process.env.SENDGRID_API_KEY); 

      for (let emailRecipientType of emailRecipientTypes) {
        let emailTemplate = {};

        options = {...options, userType: emailRecipientType};
        // build email template
        emailTemplate = await emailGenerator.generateEmail(questions, pages, questionPanels, answers, options); 
        // send email
        await sgMail.send(emailTemplate);
      }
     
      application.applicationStatus = 'submitted';
      // save the application to db
      await application.save();

      await notificationBuilder.triggerNotificationMessage(application.userId, `You have successfully submitted a Data Access Request for ${title}`,'data access request', application.dataSetId);

      return res.status(200).json({ status: 'success', data: application });
    }
  } catch (err) {
    console.log(err.message);
    res.status(500).json({ status: 'error', message: err });
  }
});

module.exports = router;