import express from 'express'
import { UserModel } from '../user/user.model';
import { DataRequestModel } from '../datarequests/datarequests.model';
const sgMail = require('@sendgrid/mail');
const router = express.Router();


// @router   POST /api/v1/dataset/access/request
// @desc     Request Access for Datasets 
// @access   Private
  router.post('/request', async (req, res) => {
    const {
      researchAim,
      linkedDataSets,
      namesOfDataSets,
      dataRequirements,
      dataSetParts,
      startDate,
      icoRegistration,
      researchBenefits,
      ethicalProcessingEvidence,
      contactNumber,
      title,
      userId,
      dataSetId
    } = req.body;

    const sendSuccess = {type: 'success', message: 'Done! Your request for data access has been sent to the data custodian.'};

    try {
      const user = await UserModel.findOne({id: userId});
      if (!user) {
        return res
          .status(400)
          .json({ message: { type:'danger', message: 'User not found' } });
      }

      const msg = {
        to: user.email,
        from: 'tony.espley@paconsulting.com',
        subject: `Enquires for ${title} dataset healthdatagateway.org`,
        text: 'and easy to do anywhere, even with Node.js',
        html: `Thank you for enquiring about access to the ${title} dataset through the Health Data Research UK Innovation Gateway. The Data Custodian for this dataset has been notified and they will contact you directly in due course.<br /><br />

        In order to facilitate the next stage of the request process, please make yourself aware of the technical data terminology used by the NHS Data Dictionary on the following link: <a href="https://www.datadictionary.nhs.uk/">https://www.datadictionary.nhs.uk/</a><br /><br />

        Please reply to this email, if you would like to provide feedback to the Data Enquiry process facilitated by the Health Data Research Innovation Gateway - <a href="mailto:support@healthdatagateway.org">support@healthdatagateway.org(opens in new tab)</a>`,
      };
      sgMail.setApiKey(process.env.SENDGRID_API_KEY);
      await sgMail.send(msg); 

      let dataAccessLog = new DataRequestModel();
      dataAccessLog.id = parseInt(Math.random().toString().replace(`0.`, ``));
      dataAccessLog.dataSetId = dataSetId;
      dataAccessLog.userId = userId;
      dataAccessLog.timeStamp = Date.now();
      dataAccessLog.save((err) => {
        if (err) return res.json({message: {type: 'danger', message: err}});

        return res.json({message: {...sendSuccess}});
      });

    }
    catch(error) {
      console.log(error);
      res.status(500).json({message: {type: 'danger', message: 'Something went wrong and your request could not be sent.'}});
    }
  });

  module.exports = router;