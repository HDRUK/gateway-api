import express from 'express'
import axios from 'axios';

const router = express.Router();

  router.get('/', async (req, res) => {
      let searchString = "";
      if (req.query.search) {
        searchString = req.query.search;
      }
      await axios.get(`https://modelcatalogue.cs.ox.ac.uk/hdruk-preprod/api/dataModels/profile/values/uk.ac.hdrukgateway/HdrUkProfilePluginService?filter=publisher&filter=license&filter=geographicCoverage&filter=ageBand&filter=physicalSampleAvailability&filter=keywords&search=${searchString}`)
        .then((response) => {
          // handle success
          return res.status(200).json({ success: true, data: response.data });
        })
        .catch((err) => {
          // handle error
          return res.status(400).json({ success: false, error: err.message + ' (raw message from metadata catalogue)' });
        });
    });

    module.exports = router;


