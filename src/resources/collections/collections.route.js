import express from 'express';
import passport from 'passport';
import _ from 'lodash';

import { authUtils } from '../../utils';
import CollectionsController from './collections.controller';
import { collectionsService } from './dependency';

const collectionsController = new CollectionsController(collectionsService);
const router = express.Router();

// @router   GET /api/v1/collections/getList
// @desc     Returns List of Collections
// @access   Private
router.get('/getList', passport.authenticate('jwt'), (req, res) => collectionsController.getList(req, res));

// @router   GET /api/v1/collections/{collectionID}
// @desc     Returns collection based on id
// @access   Public
router.get('/:collectionID', (req, res) => collectionsController.getCollection(req, res));

// @router   GET /api/v1/collections/relatedobjects/{collectionID}
// @desc     Returns related resources for collection based on id
// @access   Public
router.get('/relatedobjects/:collectionID', (req, res) => collectionsController.getCollectionRelatedResources(req, res));

// @router   GET /api/v1/collections/entityid/{entityID}
// @desc     Returns collections that contant the entity id
// @access   Public
router.get('/entityid/:entityID', (req, res) => collectionsController.getCollectionByEntity(req, res));

// @router   PUT /api/v1/collections/edit/{id}
// @desc     Edit Collection
// @access   Private
router.put('/edit/:id', passport.authenticate('jwt'), authUtils.checkAllowedToAccess('collection'), (req, res) =>
	collectionsController.editCollection(req, res)
);

// @router   POST /api/v1/collections/add
// @desc     Add Collection
// @access   Private
router.post('/add', passport.authenticate('jwt'), (req, res) => collectionsController.addCollection(req, res));

// @router   PUT /api/v1/collections/status/{id}
// @desc     Edit Collection
// @access   Private
router.put('/status/:id', passport.authenticate('jwt'), authUtils.checkAllowedToAccess('collection'), (req, res) =>
	collectionsController.changeStatus(req, res)
);

// @router   DELETE /api/v1/collections/delete/{id}
// @desc     Delete Collection
// @access   Private
router.delete('/delete/:id', passport.authenticate('jwt'), authUtils.checkAllowedToAccess('collection'), (req, res) =>
	collectionsController.deleteCollection(req, res)
);

// eslint-disable-next-line no-undef
module.exports = router;
