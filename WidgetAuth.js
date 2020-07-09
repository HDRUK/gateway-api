require('dotenv').config(); 

const { google } = require('googleapis')

const scopes = 'https://www.googleapis.com/auth/analytics.readonly'
console.log('client email: ' + process.env.CLIENT_EMAIL)
console.log('private key: ' + process.env.PRIVATE_KEY)
const jwt = new google.auth.JWT(process.env.CLIENT_EMAIL, null, process.env.PRIVATE_KEY, scopes)

const view_id = process.env.GA_VIEW_ID

//unique users in the last month
module.exports.getUsersGA = async function(startDate, endDate) {
    const response = await jwt.authorize()
    const result = await google.analytics('v3').data.ga.get({
      'auth': jwt,
      'ids': 'ga:' + view_id,
      'start-date': startDate,  
      'end-date': endDate,


      'metrics': 'ga:users'
    //the below param can be used to break this response down across each day
    //   'dimensions' : 'ga:date'
    })

    return result;
}

//unique users total
module.exports.getTotalUsersGA = async function() {
    const response = await jwt.authorize()
    const result = await google.analytics('v3').data.ga.get({
      'auth': jwt,
      'ids': 'ga:' + view_id,
      'start-date': '2020-05-01',
      'end-date': 'today',
      'metrics': 'ga:users'
    //the below param can be used to break this response down across each day
    //   'dimensions' : 'ga:date'
    })

    return result;
}

module.exports.getGA = async function() {
    const response = await jwt.authorize()
    const result = await google.analytics('v3').data.ga.get({
      'auth': jwt,
      'ids': 'ga:' + view_id,
      'start-date': '30daysAgo',
      'end-date': 'today',
      'metrics': 'ga:pageviews',
      'dimensions' : 'ga:date'
    })

    // console.dir('result is: ' + JSON.stringify(result))
    // console.log('response is: ' + JSON.stringify(response))

    return result;
}

module.exports.getGABrowsers = async function() {
  const responseBrowsers = await jwt.authorize()

  const resultBrowsers = await google.analytics('v3').data.ga.get({
    'auth': jwt,
    'ids': 'ga:' + view_id,
    // 'start-date': '30daysAgo', //For last month
    'start-date': '7daysAgo', //For last week
    'end-date': 'today',
    'dimensions': 'ga:browser',
    // 'metrics': 'ga:sessions' //Amount of sessions on each browser
    'metrics': 'ga:users' //Amount of users using each browser

  })

  // console.dir('resultBrowsers is: ' + JSON.stringify(resultBrowsers))
  // console.dir('responseBrowsers is: ' + JSON.stringify(responseBrowsers))

  return resultBrowsers;
}