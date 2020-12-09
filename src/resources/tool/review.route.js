import express from 'express'
import { ROLES } from '../user/user.roles'
import { Reviews } from './review.model';
import passport from "passport";
import { utils } from "../auth";
import helper from '../utilities/helper.util';

const router = express.Router();

/**
 * {get} /accountsearch Search tools
 * 
 * Return list of tools, this can be with filters or/and search criteria. This will also include pagination on results.
 * The free word search criteria can be improved on with node modules that specialize with searching i.e. js-search
 */
router.get(
    '/admin/pending',
    passport.authenticate('jwt'),
    utils.checkIsInRole(ROLES.Admin),
    async (req, res) => {
  
    var r = Reviews.aggregate([
      { $lookup: { from: "tools", localField: "reviewerID", foreignField: "id", as: "person" } },
      { $lookup: { from: "tools", localField: "toolID", foreignField: "id", as: "tool" } }
    ]);
    r.exec((err, data) => {
      if (err) return res.json({ success: false, error: err });
        
      data.map(dat => {
          dat.person = helper.hidePrivateProfileDetails(dat.person);
        });
      return res.json({ success: true, data: data });
    });
  });
  
  /**
   * {get} /accountsearch Search tools
   * 
   * Return list of tools, this can be with filters or/and search criteria. This will also include pagination on results.
   * The free word search criteria can be improved on with node modules that specialize with searching i.e. js-search
   */
  router.get(
    '/pending',
    passport.authenticate('jwt'),
    utils.checkIsInRole(ROLES.Creator),
    async (req, res) => {
  
    var idString = "";
  
    if (req.query.id) {
      idString = parseInt(req.query.id);
    }
  
    var r = Reviews.aggregate([
      { $match: { $and: [{ activeflag: 'review' }, { reviewerID: idString }] } },
      { $lookup: { from: "tools", localField: "reviewerID", foreignField: "id", as: "person" } },
      { $lookup: { from: "tools", localField: "toolID", foreignField: "id", as: "tool" } }
    ]);
    r.exec((err, data) => {
      var a = Reviews.aggregate([
        { $match: { $and: [{ activeflag: 'active' }, { reviewerID: idString }] } },
        { $lookup: { from: "tools", localField: "reviewerID", foreignField: "id", as: "person" } },
        { $lookup: { from: "tools", localField: "toolID", foreignField: "id", as: "tool" } }
      ]);
      a.exec((err, allReviews) => {
        if (err) return res.json({ success: false, error: err });
        return res.json({ success: true, data: data, allReviews: allReviews });
      });
    });
  });
  
  /**
   * {get} /accountsearch Search tools
   * 
   * Return list of tools, this can be with filters or/and search criteria. This will also include pagination on results.
   * The free word search criteria can be improved on with node modules that specialize with searching i.e. js-search
   */
  router.get('/', async (req, res) => {
  
    var reviewIDString = "";
  
    if (req.query.id) {
      reviewIDString = parseInt(req.query.id);
    }
  
    var r = Reviews.aggregate([
      { $match: { $and: [{ activeflag: 'active' }, { reviewID: reviewIDString }] } },
      { $lookup: { from: "tools", localField: "reviewerID", foreignField: "id", as: "person" } },
      { $lookup: { from: "tools", localField: "toolID", foreignField: "id", as: "tool" } }
    ]);
    r.exec((err, data) => {
      if (err) return res.json({ success: false, error: err });

      data.map(dat => {
        dat.person = helper.hidePrivateProfileDetails(dat.person);
      });
      return res.json({ success: true, data: data });
    });
  });
  
  module.exports = router;