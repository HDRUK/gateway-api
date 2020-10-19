import express from 'express';
import axios from 'axios'; 

var WidgetAuth = require('../../../WidgetAuth');

const router = express.Router()

//returns the number of unique users within a set timeframe specified by the start date and end date params passed
router.get('/userspermonth', async (req, res) => {
    var startDate = req.query.startDate;
    var endDate = req.query.endDate;

    var getUsersGAPromise = WidgetAuth.getUsersGA(startDate, endDate); 

    getUsersGAPromise
    .then(function (result){ 
      JSON.stringify(result);

      return res.json({'success': true, 'data' : result.data});
    })
});

//returns the total number of unique users
router.get('/totalusers', async (req, res) => { 

    var getTotalUsersGAPromise = WidgetAuth.getTotalUsersGA();

    getTotalUsersGAPromise
    .then(function (result){ 
      JSON.stringify(result);

      return res.json({'success': true, 'data' : result.data});
    })
});

//Pageviews - with previous page path
router.get('/pageviews', async (req, res) => { 

  var getPageViewsPromise = WidgetAuth.getPageViews();

  getPageViewsPromise
  .then(function (result){ 
    JSON.stringify(result);

    return res.json({'success': true, 'data' : result.data});
  })
});

//Pageviews - total per page
router.get('/totalpageviews', async (req, res) => { 

  var getTotalPageViewsPromise = WidgetAuth.getTotalPageViews();

  getTotalPageViewsPromise
  .then(function (result){ 
    JSON.stringify(result);

    return res.json({'success': true, 'data' : result.data});
  })
});

module.exports = router