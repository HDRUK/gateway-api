import express from 'express'
import axios from 'axios';
import { DataRequestModel } from '../datarequests/datarequests.model';

const router = express.Router();


//   search for additional detail on a dataset by using the MDC dataset id
  router.get('/:id', async (req, res) => {
    var metadataCatalogue = process.env.metadataURL || 'https://metadata-catalogue.org/hdruk';
    const userId = req.query.id;
    axios.get(metadataCatalogue + '/api/facets/' + req.params.id + '/profile/uk.ac.hdrukgateway/HdrUkProfilePluginService')
      .then(function (response) {
        if (userId) {
          console.log(userId)
          var p = DataRequestModel.find({ $and: [{ userId: userId }, { dataSetId: req.params.id }]});
          p.exec((datarequestErr, datarequest) => {
            if (datarequestErr) return res.json({ success: false, error: datarequestErr });
            console.log(datarequest)
            return res.json({ 'success': true, 'data': response.data, 'datarequest': datarequest });
          });
        }
        else {
          return res.json({ 'success': true, 'data': response.data, 'datarequest': [] });
        }
        console.log(response.data)
      })
      .catch(function (err) {
        // handle error
        return res.json({ success: false, error: err.message + ' (raw message from metadata catalogue)' });
      })
  
  });

module.exports = router;
