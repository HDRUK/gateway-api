import express from 'express'
import { Data } from '../tool/data.model'

const router = express.Router();

router.get('/topic/:type', async (req, res) => {
    //req.params.id is how you get the id from the url
    var q = Data.find({ type: req.params.type });
  
    q.exec((err, data) => {
      if (err) return res.json({ success: false, error: err });
      var tempTopics = [];
        data.map((dat) => {
          dat.tags.topics.map((topic) => {
            topic.length <=0 ? tempTopics=tempTopics : tempTopics.push(topic);
          });
        });
  
      const combinedTopics = [];
      tempTopics.map(temp => {
        if (combinedTopics.indexOf(temp) === -1) {
          combinedTopics.push(temp)
        }
      });
  
      return res.json({ success: true, data: combinedTopics });
    });
  });
  
  
  router.get('/feature/:type', async (req, res) => {
    //req.params.id is how you get the id from the url
    var q = Data.find({ type: req.params.type });

    q.exec((err, data) => {
      if (err) return res.json({ success: false, error: err });
      var tempFeatures = [];
      if(data.length){
        data.map((dat) => {
          dat.tags.features.map((feature) => {
            feature.length <= 0 ? tempFeatures=tempFeatures : tempFeatures.push(feature);
          });
        });
      }

      const combinedFeatures = [];
      if(tempFeatures.length) {
        tempFeatures.map(temp => {
          if (combinedFeatures.indexOf(temp) === -1) {
            combinedFeatures.push(temp)
          }
        });
      }
      return res.json({ success: true, data: combinedFeatures });
    });
  });
  
  
  router.get('/language/:type', async (req, res) => {
    //req.params.id is how you get the id from the url
    var q = Data.find({ type: req.params.type });
  
    q.exec((err, data) => {
      if (err) return res.json({ success: false, error: err });
      var tempLanguages = [];
      data.map((dat) => {
        dat.categories.programmingLanguage.map((language) => {
          language.length <= 0 ? tempLanguages=tempLanguages : tempLanguages.push(language);
        });
      });
  
      const combinedLanguages = [];
      tempLanguages.map(temp => {
        if (combinedLanguages.indexOf(temp) === -1) {
          combinedLanguages.push(temp)
        }
      });
  
      return res.json({ success: true, data: combinedLanguages });
    });
  });
  
  
  router.get('/category/:type', async (req, res) => {
    //req.params.id is how you get the id from the url
    var q = Data.find({ type: req.params.type });
  
    q.exec((err, data) => {
      if (err) return res.json({ success: false, error: err });
      var tempCategories = [];
      data.map((dat) => {
        dat.categories.category.length <= 0 ? tempCategories=tempCategories : tempCategories.push(dat.categories.category);
      });
  
      const combinedCategories = [];
      tempCategories.map(temp => {
        if (combinedCategories.indexOf(temp) === -1) {
          combinedCategories.push(temp)
        }
      });
  
  
      return res.json({ success: true, data: combinedCategories });
    });
  });
  
  
  router.get('/license/:type', async (req, res) => {
    // try {
    //   const data = await Data.find({type: req.params.type});
    //   if (!data) {
    //     return res.status(400).json({ success: false, error: 'Could not get license types' });
    //   }
    //   let licences = [];
    //   // puts all licences in array
    //   let fullLicenseArr = [...data].map((d) => {
    //       if(d.license != null)
    //         return d.license;
    //     });
    //     // removes duplicates from array
    //     licences =  [...fullLicenseArr].filter((item, idx) => {
    //       return fullLicenseArr.indexOf(item) === idx && item != null;
    //     });      
    //     return res.json({ success: true, data: licences });
    // } 
    // catch(error) {
    //   console.error(error);
    //   res.status(500).send({ success: false, error });
    // }

    //req.params.id is how you get the id from the url
    var q = Data.find({ type: req.params.type });
  
    q.exec((err, data) => {
      if (err) return res.json({ success: false, error: err });
      var tempLicenses = [];
      data.map((dat) => {
        if(dat.license)
          dat.license.length <= 0 ? tempLicenses=tempLicenses : tempLicenses.push(dat.license);
      });

      const combinedLicenses = [];
      tempLicenses.map(temp => {
        if (combinedLicenses.indexOf(temp) === -1) {
          combinedLicenses.push(temp)
        }
      });
      return res.json({ success: true, data: combinedLicenses });
    });
  });
  
module.exports = router;