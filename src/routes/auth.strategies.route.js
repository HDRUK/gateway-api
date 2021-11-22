import express from 'express';

import { OrcidStrategy, NHSMailStrategy, AzureStrategy, GoogleStrategy, LinkedinStrategy, OIDCStrategy } from '../services/strategies';
import { captureReferer, catchLoginErrorAndRedirect } from '../middlewares/index';
import { authUtils } from '../utils';

const router = express.Router();

const orcidStrategy = new OrcidStrategy();
const nhsmailStrategy = new NHSMailStrategy();
const azureStrategy = new AzureStrategy();
const googleStrategy = new GoogleStrategy();
const linkedinStrategy = new LinkedinStrategy();
const oidcStrategy = new OIDCStrategy();

router.get('/orcid', captureReferer, orcidStrategy.initialise);
router.get('/orcid/callback', orcidStrategy.callback, catchLoginErrorAndRedirect, authUtils.loginAndSignToken);

router.get('/azure', captureReferer, azureStrategy.initialise);
router.get('/azure/callback', azureStrategy.callback, catchLoginErrorAndRedirect, authUtils.loginAndSignToken);

router.get('/google', captureReferer, googleStrategy.initialise);
router.get('/google/callback', googleStrategy.callback, catchLoginErrorAndRedirect, authUtils.loginAndSignToken);

router.get('/linkedin', captureReferer, linkedinStrategy.initialise);
router.get('/linkedin/callback', linkedinStrategy.callback, catchLoginErrorAndRedirect, authUtils.loginAndSignToken);

router.get('/oidc', captureReferer, oidcStrategy.initialise);
router.get('/oidc/callback', oidcStrategy.callback, catchLoginErrorAndRedirect, authUtils.loginAndSignToken);

// IN PROGRESS
//router.get('/nhsmail', captureReferer, nhsmailStrategy.initialise);
//router.get('/nhsmail/callback', nhsmailStrategy.callback, catchLoginErrorAndRedirect, authUtils.loginAndSignToken);

module.exports = router;
