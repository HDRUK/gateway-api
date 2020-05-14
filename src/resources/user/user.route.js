import express from 'express'
import { ROLES } from '../user/user.roles'
import passport from "passport";
import { utils } from "../auth";
import { UserModel } from './user.model'
import { Data } from '../tool/data.model'

const router = express.Router();

// @router   GET /api/v1/users/:userID
// @desc     find user by id
// @access   Private
router.get(
    '/:userID',
    passport.authenticate('jwt'),
    utils.checkIsInRole(ROLES.Admin, ROLES.Creator),
    async (req, res) => {
    //req.params.id is how you get the id from the url
    var q = UserModel.find({ id: req.params.userID });
  
    q.exec((err, userdata) => {
      if (err) return res.json({ success: false, error: err });
      return res.json({ success: true, userdata: userdata });
    });
  });

// @router   GET /api/v1/users
// @desc     get all
// @access   Private
router.get('/', async (req, res) => {
    //req.params.id is how you get the id from the url
    var q = Data.find({ type: 'person' });

    q.exec((err, data) => {
        if (err) return res.json({ success: false, error: err });
        const users = [];
        data.map((dat) => {
        users.push({ id: dat.id, name: dat.firstname + ' ' + dat.lastname })
        });
        return res.json({ success: true, data: users });
    });
});
  

module.exports = router