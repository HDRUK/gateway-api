'use strict';

import express from 'express';
import Provider from 'oidc-provider';
import swaggerUi from 'swagger-ui-express';
import cors from 'cors';
import logger from 'morgan';
import passport from 'passport';
import cookieParser from 'cookie-parser';
import bodyParser from 'body-parser';
import { connectToDatabase } from './db';
import { initialiseAuthentication } from '../resources/auth';
import * as Sentry from '@sentry/node';
import * as Tracing from '@sentry/tracing';

require('dotenv').config();

var app = express();

const readEnv = process.env.NODE_ENV || 'prod';
if (readEnv === 'test' || readEnv === 'prod') {
	Sentry.init({
		dsn: process.env.SENTRY_DNS,
		environment: process.env.NODE_ENV,
		integrations: [
			// enable HTTP calls tracing
			new Sentry.Integrations.Http({ tracing: true }),
			// enable Express.js middleware tracing
			new Tracing.Integrations.Express({
				// trace all requests to the default router
				app,
			}),
		],
		tracesSampleRate: 1.0,
	});
	// RequestHandler creates a separate execution context using domains, so that every
	// transaction/span/breadcrumb is attached to its own Hub instance
	app.use(Sentry.Handlers.requestHandler());
	// TracingHandler creates a trace for every incoming request
	app.use(Sentry.Handlers.tracingHandler());
	app.use(Sentry.Handlers.errorHandler());
}

const Account = require('./account');
const configuration = require('./configuration');

const API_PORT = process.env.PORT || 3001;
const session = require('express-session');
app.disable('x-powered-by');

configuration.findAccount = Account.findAccount;
const oidc = new Provider(process.env.APP_URL, configuration);
oidc.proxy = true;

var domains = [/\.healthdatagateway\.org$/, process.env.GATEWAY_WEB_URL];

var rx = /^((http|https)+:\/\/[a-z]+)\.([^/]*)/;
var arr = rx.exec(process.env.GATEWAY_WEB_URL);

if (Array.isArray(arr) && arr.length > 0) {
	domains.push('https://' + arr[2]);
}

app.use(
	cors({
		origin: domains,
		credentials: true,
	})
);

// apply rate limiter of 100 requests per minute
const RateLimit = require('express-rate-limit');
let limiter = new RateLimit({ windowMs: 60000, max: 500 });
app.use(limiter);

const router = express.Router();

connectToDatabase();

// (optional) only made for logging and
// bodyParser, parses the request body to be a readable json format

app.use(bodyParser.json({ limit: '10mb', extended: true }));
app.use(bodyParser.urlencoded({ limit: '10mb', extended: false }));

app.use(logger('dev'));
app.use(cookieParser());
app.use(passport.initialize());
app.use(passport.session());

app.use(
	session({
		secret: process.env.JWTSecret,
		resave: false,
		saveUninitialized: true,
		name: 'sessionId',
		/* cookie: {
            secure: process.env.NODE_ENV !== 'local',
            httpOnly: true
        } */
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
			return res.status(200).redirect(process.env.GATEWAY_WEB_URL + '/search?search=');
		}
		req.logout();
		res.clearCookie('jwt');

		return res.status(200).redirect(process.env.GATEWAY_WEB_URL + '/search?search=');
	})(req, res, next);
});

app.get('/api/v1/openid/interaction/:uid', setNoCache, (req, res, next) => {
	passport.authenticate('jwt', async function (err, user, info) {
		if (err || !user) {
			//login in user - go to login screen
			return res.status(200).redirect(process.env.GATEWAY_WEB_URL + '/search?search=&showLogin=true&loginReferrer=' + process.env.APP_URL + req.url);
		} else {
			try {
				const { prompt, session } = await oidc.interactionDetails(req, res);

				switch (prompt.name) {
					case 'select_account': {
					}
					case 'login': {
						const result = {
							select_account: {}, // make sure its skipped by the interaction policy since we just logged in
							login: {
								account: user.id.toString(),
							},
						};

						return await oidc.interactionFinished(req, res, result, { mergeWithLastSubmission: false });
					}
					case 'consent': {
						if (!session) {
							return oidc.interactionFinished(req, res, { select_account: {} }, { mergeWithLastSubmission: false });
						}

						await oidc.interactionDetails(req, res);
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
});

app.use('/api/v1/openid', oidc.callback);
app.use('/api', router);
app.use('/api-docs', swaggerUi.serve, swaggerUi.setup(require('../../docs/index.docs')));

app.use('/oauth', require('../resources/auth/oauth.route'));
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

app.use('/api/v1/accounts', require('../resources/account/account.route'));
app.use('/api/v1/search/filter', require('../resources/search/filter.route'));
app.use('/api/v1/search', require('../resources/search/search.router')); // tools projects people

app.use('/api/v1/linkchecker', require('../resources/linkchecker/linkchecker.router'));

app.use('/api/v1/stats', require('../resources/stats/v1/stats.route'));
app.use('/api/v2/stats', require('../resources/stats/v2/stats.route'));
app.use('/api/v1/kpis', require('../resources/stats/v1/kpis.route'));

app.use('/api/v1/course', require('../resources/course/v1/course.route'));
app.use('/api/v2/courses', require('../resources/course/v2/course.route'));

app.use('/api/v1/person', require('../resources/person/person.route'));

app.use('/api/v1/tools', require('../resources/tool/v1/tool.route'));
app.use('/api/v2/tools', require('../resources/tool/v2/tool.route'));

app.use('/api/v1/projects', require('../resources/project/v1/project.route'));
app.use('/api/v2/projects', require('../resources/project/v2/project.route'));

app.use('/api/v1/papers', require('../resources/paper/v1/paper.route'));
app.use('/api/v2/papers', require('../resources/paper/v2/paper.route'));

app.use('/api/v1/cohorts', require('../resources/cohort/cohort.route'));
app.use('/api/v1/save-cohort', require('../resources/cohort/cohort.route'));

app.use('/api/v1/counter', require('../resources/tool/counter.route'));
app.use('/api/v1/coursecounter', require('../resources/course/coursecounter.route'));
app.use('/api/v1/collectioncounter', require('../resources/collections/collectioncounter.route'));

app.use('/api/v1/discourse', require('../resources/discourse/discourse.route'));

app.use('/api/v1/dataset-onboarding', require('../routes/datasetonboarding.route'));
app.use('/api/v1/datasets', require('../resources/dataset/v1/dataset.route'));
app.use('/api/v2/datasets', require('../resources/dataset/v2/dataset.route'));

app.use('/api/v1/data-access-request/schema', require('../resources/datarequest/schema/datarequest.schemas.route'));
app.use('/api/v1/data-access-request', require('../resources/datarequest/datarequest.route'));

app.use('/api/v1/collections', require('../resources/collections/collections.route'));

app.use('/api/v1/analyticsdashboard', require('../services/googleAnalytics/googleAnalytics.route'));

app.use('/api/v1/help', require('../resources/help/help.router'));

app.use('/api/v2/filters', require('../resources/filters/filters.route'));
app.use('/api/v2/activitylog', require('../resources/activitylog/activitylog.route'));

app.use('/api/v1/hubspot', require('../services/hubspot/hubspot.route'));

app.use('/api/v1/cohortprofiling', require('../resources/cohortprofiling/cohortprofiling.route'));

app.use('/api/v1/global', require('../resources/global/global.route'));

app.use('/api/v1/search-preferences', require('../resources/searchpreferences/searchpreferences.route'));

app.use('/api/v2/data-use-registers', require('../resources/dataUseRegister/dataUseRegister.route'));
app.use('/api/v1/locations', require('../resources/spatialfilter/SpatialRouter'));

initialiseAuthentication(app);

// launch our backend into a port
app.listen(API_PORT, () => console.log(`LISTENING ON PORT ${API_PORT}`));
