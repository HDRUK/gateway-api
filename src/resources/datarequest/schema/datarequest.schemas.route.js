import express from 'express';
import passport from 'passport';
import { isNull, isUndefined, isEmpty } from 'lodash';

import { DataRequestSchemaModel } from './datarequest.schemas.model';
import DatarequestschemaController from './datarequest.schema.controller';
import { datarequestschemaService } from './dependency';
import { utils } from '../../auth';
import { ROLES } from '../../user/user.roles';
import constants from '../../utilities/constants.util';

const datarequestschemaController = new DatarequestschemaController(datarequestschemaService);

const router = express.Router();

function isUserMemberOfTeam(user, publisherName) {
	let { teams } = user;
	return teams.filter(team => !isNull(team.publisher)).some(team => team.publisher.name === publisherName);
}

const validateUpdate = (req, res, next) => {
	const { id } = req.params;

	if (isUndefined(id)) return res.status(400).json({ success: false, message: 'You must provide a valid data request Id' });

	next();
};

const authorizeUpdate = async (req, res, next) => {
	const requestingUser = req.user;
	const { id } = req.params;

	const datarequestschema = await datarequestschemaService.getDatarequestschemaById(id);

	if (isEmpty(datarequestschema)) {
		return res.status(404).json({
			success: false,
			message: 'The requested data request schema could not be found',
		});
	}

	const authorised = isUserMemberOfTeam(requestingUser, datarequestschema.publisher);
	const isAdminUser = requestingUser.teams.map(team => team.type).includes(constants.teamTypes.ADMIN);

	if (!authorised && !isAdminUser) {
		return res.status(401).json({
			success: false,
			message: 'You are not authorised to perform this action',
		});
	}

	next();
};

// @router   POST api/v1/data-access-request/schema
// @desc     Add a data request schema
// @access   Private
router.post('/', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Admin, ROLES.Creator), async (req, res) => {
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
router.get('/', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Admin, ROLES.Creator), async (req, res) => {
	const { dataSetId } = req.body;
	let dataRequestSchema = await DataRequestSchemaModel.findOne({ $and: [{ dataSetId: dataSetId }, { status: 'active' }] });
	return res.json({ jsonSchema: dataRequestSchema.jsonSchema });
});

// @router   PATCH /api/v1/data-access-request/schema
// @desc     patch a data request schema
// @access   Private
router.patch('/:id', passport.authenticate('jwt'), validateUpdate, authorizeUpdate, (req, res) =>
	datarequestschemaController.updateDatarequestschema(req, res)
);

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
		process.stdout.write(`DATA REQUEST - archiveOtherVersions : ${err.message}\n`);
	}
}
