import express from 'express'
import axios from 'axios';

const router = express.Router();

  router.get('/', async (req, res) => {
      var modelCatalogue = 
      // process.env.metadataURL || 
      'https://modelcatalogue.cs.ox.ac.uk/hdruk-preprod';

      var searchString = "";
      var filterTerm = "";
  
      if (req.query.search) {
        searchString = req.query.search;
      }

      if(req.query.filter) {
        filterTerm = req.query.filter;
      }

      axios.get(modelCatalogue + '/api/dataModels/profile/values/uk.ac.hdrukgateway/HdrUkProfilePluginService?filter=publisher&filter=license&filter=geographicCoverage&filter=ageBand&filter=physicalSampleAvailability&filter=keywords&search=' + searchString)
        .then(function (response) {
          // handle success
          return res.json({ 'success': true, 'data': response.data });
        })
        .catch(function (err) {
          // handle error
          return res.json({ success: false, error: err.message + ' (raw message from metadata catalogue)' });
        })
    });

    module.exports = router;


