import express from 'express';

import OrcidStrategy from '../services/strategies/orcid';
import { captureReferer, catchLoginErrorAndRedirect } from '../middlewares/index';
import { loginAndSignToken } from '../util';

const router = express.Router();

const orcidStrategy = new OrcidStrategy();

router.get('/orcid', captureReferer, orcidStrategy.orcid);
router.get('/orcid/callback', orcidStrategy.orcidCallback, catchLoginErrorAndRedirect, loginAndSignToken);

module.exports = router;
