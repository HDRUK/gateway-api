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
      console.log("promiseResult: " + JSON.stringify(result)); 
      JSON.stringify(result);
      
      return res.json({'success': true, 'data' : result.data});
    })
});

//returns the total number of unique users - has date params set from 1st May 2020 to today
router.get('/totalusers', async (req, res) => {
 
    var getTotalUsersGAPromise = WidgetAuth.getTotalUsersGA();

    getTotalUsersGAPromise
    .then(function (result){ 
      console.log("promiseResult: " + JSON.stringify(result)); 
      JSON.stringify(result);
      
      return res.json({'success': true, 'data' : result.data});
    })
});

//
router.get('/viewspermonth', async (req, res) => {
 
    var getGAPromise = WidgetAuth.getGA();

    getGAPromise
    .then(function (result){ 
      console.log("promiseResult: " + JSON.stringify(result)); 
      JSON.stringify(result);
      
      return res.json({'success': true, 'data' : result.data});
    })
});

module.exports = router
