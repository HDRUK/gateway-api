'use strict';

import express from 'express'; 
import Provider from 'oidc-provider';
import swaggerUi from 'swagger-ui-express';
import YAML from 'yamljs';
const swaggerDocument = YAML.load('./swagger.yaml');
import cors from 'cors';
import bodyParser from 'body-parser';
import logger from 'morgan';
import passport from 'passport';
import cookieParser from 'cookie-parser';
import { connectToDatabase } from './db';
import { initialiseAuthentication } from '../resources/auth';

require('dotenv').config();

const Account = require('./account');
const configuration = require('./configuration');


const API_PORT = process.env.PORT || 3001;
const session = require('express-session');
var app = express();

configuration.findAccount = Account.findAccount;
const oidc = new Provider(process.env.api_url || 'http://localhost:3001', configuration);
oidc.proxy = true;

var domains = [process.env.homeURL];

var rx = /^([http|https]+:\/\/[a-z]+)\.([^/]*)/;
var arr = rx.exec(process.env.homeURL);

if (Array.isArray(arr) && arr.length > 0) {
    domains.push('https://' + arr[2]);
}

app.use(
    cors({
        origin: domains,
        credentials: true,
    })
);

const router = express.Router();

connectToDatabase();

// (optional) only made for logging and
// bodyParser, parses the request body to be a readable json format
app.use(bodyParser.urlencoded({ extended: false }));
app.use(bodyParser.json());
app.use(logger('dev'));
app.use(cookieParser());
app.use(passport.initialize());
app.use(passport.session());

app.use(
    session({
        secret: process.env.JWTSecret,
        resave: false,
        saveUninitialized: true,
    })
);

function setNoCache(req, res, next) {
    res.set('Pragma', 'no-cache');
    res.set('Cache-Control', 'no-cache, no-store');
    next();
}

app.get('/api/v1/openid/endsession', setNoCache, (req, res, next) => {
    passport.authenticate('jwt', async function (err, user, info) {
        if (err || !user) {
            return res.status(200).redirect(process.env.homeURL+'/search?search=');
        }
        oidc.Session.destory;
        req.logout();
	    res.clearCookie('jwt');

        return res.status(200).redirect(process.env.homeURL+'/search?search=');
    })(req, res, next);
})


app.get('/api/v1/openid/interaction/:uid', setNoCache, (req, res, next) => {
    passport.authenticate('jwt', async function (err, user, info) {

        if (err || !user) {
            //login in user - go to login screen
            var apiURL = process.env.api_url || 'http://localhost:3001';
            return res.status(200).redirect(process.env.homeURL+'/search?search=&showLogin=true&loginReferrer='+apiURL+req.url)
        }
        else {
            try {
                const { uid, prompt, params, session } = await oidc.interactionDetails(req, res);
        
                const client = await oidc.Client.find(params.client_id);
        

                switch (prompt.name) {
                    case 'select_account': {
                        
                    }
                    case 'login': {
                        


                        const result = {
                            select_account: {}, // make sure its skipped by the interaction policy since we just logged in
                            login: {
                                account: user.id.toString()
                            },
                        };





                        return await oidc.interactionFinished(req, res, result, { mergeWithLastSubmission: false });
                    }
                    case 'consent': {
                        if (!session) {
                            return oidc.interactionFinished(req, res, { select_account: {} }, { mergeWithLastSubmission: false });
                        }
        
                        const account = await oidc.Account.findAccount(undefined, session.accountId);
                        const { email } = await account.claims('prompt', 'email', { email: null }, []);


                        const { prompt: { name, details } } = await oidc.interactionDetails(req, res);
                        //assert.equal(name, 'consent');

                        const consent = {};

                        // any scopes you do not wish to grant go in here
                        //   otherwise details.scopes.new.concat(details.scopes.accepted) will be granted
                        consent.rejectedScopes = [];

                        // any claims you do not wish to grant go in here
                        //   otherwise all claims mapped to granted scopes
                        //   and details.claims.new.concat(details.claims.accepted) will be granted
                        consent.rejectedClaims = [];

                        // replace = false means previously rejected scopes and claims remain rejected
                        // changing this to true will remove those rejections in favour of just what you rejected above
                        consent.replace = false;

                        const result = { consent };
                        return await oidc.interactionFinished(req, res, result, { mergeWithLastSubmission: true });
                    }
                    default:
                        return undefined;
                }
            } catch (err) {
                return next(err);
            }
        }
    })(req, res, next);
})





app.use('/api/v1/openid', oidc.callback);
app.use('/api', router);
app.use('/api-docs', swaggerUi.serve, swaggerUi.setup(swaggerDocument));

app.use('/api/v1/auth/sso/discourse', require('../resources/auth/sso/sso.discourse.router'));
app.use('/api/v1/auth', require('../resources/auth/auth.route'));
app.use('/api/v1/auth/register', require('../resources/user/user.register.route'));

app.use('/api/v1/users', require('../resources/user/user.route'));
app.use('/api/v1/topics', require('../resources/topic/topic.route'));
app.use('/api/v1/publishers', require('../resources/publisher/publisher.route'));
app.use('/api/v1/teams', require('../resources/team/team.route'));
app.use('/api/v1/workflows', require('../resources/workflow/workflow.route'));
app.use('/api/v1/messages', require('../resources/message/message.route'));
app.use('/api/v1/reviews', require('../resources/tool/review.route'));
app.use('/api/v1/relatedobject/', require('../resources/relatedobjects/relatedobjects.route'));
app.use('/api/v1/tools', require('../resources/tool/tool.route'));
app.use('/api/v1/accounts', require('../resources/account/account.route'));
app.use('/api/v1/search/filter', require('../resources/search/filter.route'));
app.use('/api/v1/search', require('../resources/search/search.router')); // tools projects people 

app.use('/api/v1/linkchecker', require('../resources/linkchecker/linkchecker.router')); 
 
app.use('/api/v1/stats', require('../resources/stats/stats.router')); 
app.use('/api/v1/kpis', require('../resources/stats/kpis.router')); 

app.use('/api/v1/course', require('../resources/course/course.route')); 

app.use('/api/v1/person', require('../resources/person/person.route'));

app.use('/api/v1/projects', require('../resources/project/project.route'));
app.use('/api/v1/papers', require('../resources/paper/paper.route'));
app.use('/api/v1/counter', require('../resources/tool/counter.route'));
app.use('/api/v1/coursecounter', require('../resources/course/coursecounter.route'));

app.use('/api/v1/discourse', require('../resources/discourse/discourse.route'));

app.use('/api/v1/datasets', require('../resources/dataset/dataset.route'));

app.use('/api/v1/data-access-request/schema', require('../resources/datarequest/datarequest.schemas.route'));
app.use('/api/v1/data-access-request', require('../resources/datarequest/datarequest.route'));

app.use('/api/v1/collections', require('../resources/collections/collections.route'));

app.use('/api/v1/analyticsdashboard', require('../resources/googleanalytics/googleanalytics.router'));

app.use('/api/v1/help', require('../resources/help/help.router'));

initialiseAuthentication(app);

// launch our backend into a port
app.listen(API_PORT, () => console.log(`LISTENING ON PORT ${API_PORT}`));
