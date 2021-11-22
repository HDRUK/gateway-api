import express from 'express';
import passport from 'passport';

import { DataRequestSchemaModel } from './datarequest.schemas.model';
import { authUtils } from '../../../utils';
import { ROLES } from '../../user/user.roles';

const router = express.Router();

// @router   POST api/v1/data-access-request/schema
// @desc     Add a data request schema
// @access   Private
router.post('/', passport.authenticate('jwt'), authUtils.checkIsInRole(ROLES.Admin, ROLES.Creator), async (req, res) => {
	const { version, status, dataSetId, jsonSchema, publisher } = req.body;
	const dataRequestSchema = new DataRequestSchemaModel();
	dataRequestSchema.id = parseInt(Math.random().toString().replace('0.', ''));
	dataRequestSchema.status = status;
	dataRequestSchema.version = version;
	dataRequestSchema.dataSetId = dataSetId;
	dataRequestSchema.publisher = publisher;
	dataRequestSchema.jsonSchema = jsonSchema;

	await dataRequestSchema.save(async err => {
		if (err) return res.json({ success: false, error: err });

		return res.json({ success: true, id: dataRequestSchema.id });
	});
	await archiveOtherVersions(dataRequestSchema.id, dataSetId, status);
});

// @router   GET /api/v1/data-access-request/schema
// @desc     Get a data request schema
// @access   Private
router.get('/', passport.authenticate('jwt'), authUtils.checkIsInRole(ROLES.Admin, ROLES.Creator), async (req, res) => {
	const { dataSetId } = req.body;
	let dataRequestSchema = await DataRequestSchemaModel.findOne({ $and: [{ dataSetId: dataSetId }, { status: 'active' }] });
	return res.json({ jsonSchema: dataRequestSchema.jsonSchema });
});

module.exports = router;

async function archiveOtherVersions(id, dataSetId, status) {
	try {
		if (status === 'active') {
			await DataRequestSchemaModel.updateMany(
				{ $and: [{ dataSetId: dataSetId }, { id: { $ne: id } }] },
				{ $set: { status: 'archive' } },
				async err => {
					if (err) return new Error({ success: false, error: err });

					return { success: true };
				}
			);
		}
	} catch (err) {
		console.error(err.message);
	}
}
