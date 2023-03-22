import express from 'express';
import passport from 'passport';

import { utils } from '../auth';
import { ROLES } from '../user/user.roles';

import datasetonboardingUtil from '../../utils/datasetonboarding.util';

const router = express.Router({ mergeParams: true });

router.post('/scoring', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Admin), async (req, res) => {
    const { dataset } = req.body;

    if (!dataset) {
        res.json({ success: false, error: 'Dataset object must be supplied and contain all required data', status: 400 });
    }

    const verdict = await datasetonboardingUtil.buildMetadataQuality(dataset, dataset.datasetv2, dataset.pid);
    res.json({ success: true, data: verdict, status: 200 });
});

module.exports = router;