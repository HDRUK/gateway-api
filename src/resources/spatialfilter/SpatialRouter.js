import express from 'express';
import {
    checkInputMiddleware,
    checkMinLengthMiddleware,
    checkStringMiddleware,
} from '../../middlewares/index';

const router = express.Router();
const LocationController = require('./LocationController');

router.get(
    '/:filter',
    [checkInputMiddleware, checkMinLengthMiddleware, checkStringMiddleware],
    (req, res) => LocationController.getData(req, res),
);

module.exports = router;