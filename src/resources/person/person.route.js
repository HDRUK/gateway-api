import express from 'express'
import { Data } from '../tool/data.model'
import { utils } from "../auth";
import passport from "passport";
import { ROLES } from '../user/user.roles'
import {addTool, editTool, deleteTool, setStatus, getTools, getToolsAdmin} from '../tool/data.repository';
import emailGenerator from '../utilities/emailGenerator.util';
import { UserModel } from '../user/user.model'
import helper from '../utilities/helper.util'; 
const urlValidator = require('../utilities/urlValidator');
const inputSanitizer = require('../utilities/inputSanitizer');

const router = express.Router()

router.post('/',
    passport.authenticate('jwt'),
    utils.checkIsInRole(ROLES.Admin, ROLES.Creator),
    async (req, res) => {
      const { firstname, lastname, bio, emailNotifications, terms, sector, organisation, showOrganisation, tags} = req.body;
      let link = urlValidator.validateURL(inputSanitizer.removeNonBreakingSpaces(req.body.link));
      let orcid = req.body.orcid !== '' ? urlValidator.validateOrcidURL(inputSanitizer.removeNonBreakingSpaces(req.body.orcid)) : '';
      let data = Data();
      console.log(req.body)
      data.id = parseInt(Math.random().toString().replace('0.', ''));
      data.firstname = inputSanitizer.removeNonBreakingSpaces(firstname),
      data.lastname = inputSanitizer.removeNonBreakingSpaces(lastname),
      data.type = "person";
      data.bio = inputSanitizer.removeNonBreakingSpaces(bio);
      data.link = link;
      data.orcid = orcid;
      data.emailNotifications = emailNotifications;
      data.terms = terms;
      data.sector = inputSanitizer.removeNonBreakingSpaces(sector);
      data.organisation = inputSanitizer.removeNonBreakingSpaces(organisation);
      data.showOrganisation = showOrganisation;
      data.tags = inputSanitizer.removeNonBreakingSpaces(tags);
      let newPersonObj = await data.save();
      if(!newPersonObj)
        return res.json({ success: false, error: "Can't persist data to DB" });
      
      return res.json({ success: true, data: newPersonObj});
  });

router.put('/',
  passport.authenticate('jwt'),
  utils.checkIsInRole(ROLES.Admin, ROLES.Creator),
  async (req, res) => {
  let { id, firstname, lastname, email, bio, showBio, showLink, showOrcid, emailNotifications, terms, sector, showSector, organisation, showOrganisation, tags, showDomain, profileComplete } = req.body;
  const type = 'person';
  let link = urlValidator.validateURL(inputSanitizer.removeNonBreakingSpaces(req.body.link));
  let orcid = req.body.orcid !== '' ? urlValidator.validateOrcidURL(inputSanitizer.removeNonBreakingSpaces(req.body.orcid)) : '';
  firstname = inputSanitizer.removeNonBreakingSpaces(firstname),
  lastname = inputSanitizer.removeNonBreakingSpaces(lastname),
  bio = inputSanitizer.removeNonBreakingSpaces(bio);
  sector = inputSanitizer.removeNonBreakingSpaces(sector);
  organisation = inputSanitizer.removeNonBreakingSpaces(organisation);
  tags.topics = inputSanitizer.removeNonBreakingSpaces(tags.topics);
  console.log(req.body)

  await Data.findOneAndUpdate({ id: id },
    {
      firstname,
      lastname,
      type,
      bio,
      showBio,
      link,
      showLink,
      orcid,
      showOrcid,
      emailNotifications,
      terms,
      sector,
      showSector,
      organisation,
      showOrganisation,
      tags,
      showDomain,
      profileComplete
    },
    {new:true});
    await UserModel.findOneAndUpdate({ id: id }, 
      { $set: { firstname: firstname, lastname: lastname, email: email } })
    .then(person => {
      return res.json({ success: true, data: person});
    })
    .catch(err =>{
      return res.json({ success: false, error: err });
    })
  });

// @router   GET /api/v1/person/unsubscribe/:userObjectId
// @desc     Unsubscribe a single user from email notifications without challenging authentication
// @access   Public
router.put('/unsubscribe/:userObjectId', async (req, res) => {
   const userId = req.params.userObjectId;
   // 1. Use _id param issued by MongoDb as unique reference to find user entry
    await UserModel.findOne({ _id: userId })
      .then(async (user) => {
        // 2. Find person entry using numeric id and update email notifications to false
        await Data.findOneAndUpdate({ id: user.id },
        {
          emailNotifications: false
        }).then(() => {
          // 3a. Return success message
          return res.json({ success: true, msg: "You've been successfully unsubscribed from all emails. You can change this setting via your account." });
        });
      })
      .catch(() =>{
        // 3b. Return generic failure message in all cases without disclosing reason or data structure
        return res.status(500).send({ success: false, msg: "A problem occurred unsubscribing from email notifications." });
      })
});

// @router   GET /api/v1/person/:id
// @desc     Get person info based on personID
router.get('/:id', async (req, res) => {

    let person = Data.aggregate([
        { $match: { $and: [{ id: parseInt(req.params.id) }] } },
        { $lookup: { from: "tools", localField: "id", foreignField: "authors", as: "tools" } },
        { $lookup: { from: "reviews", localField: "id", foreignField: "reviewerID", as: "reviews" } }
    ]);
    person.exec((err, data) => {
        if (err) return res.json({ success: false, error: err });

        data = helper.hidePrivateProfileDetails(data);
        return res.json({ success: true, data: data });
    });
});

// @router   GET /api/v1/person/profile/:id
// @desc     Get person info for their account
router.get('/profile/:id', async (req, res) => {

  let profileData = Data.aggregate([
      { $match: { $and: [{ id: parseInt(req.params.id) }] } },
      { $lookup: { from: "tools", localField: "id", foreignField: "authors", as: "tools" } },
      { $lookup: { from: "reviews", localField: "id", foreignField: "reviewerID", as: "reviews" } }
  ]);
  profileData.exec((err, data) => {
      if (err) return res.json({ success: false, error: err });
      return res.json({ success: true, data: data });
  });
});

// @router   GET /api/v1/person
// @desc     Get paper for an author
// @access   Private
router.get('/',
  async (req, res) => {
    let personArray = [];
    req.params.type = "person";
    await getToolsAdmin(req)
      .then(data => {
          data.map((personObj) => {
            personArray.push(
              {     
                "id":personObj.id,
                "type":personObj.type,
                "firstname":personObj.firstname,
                "lastname":personObj.lastname,
                "bio":personObj.bio,
                "sociallinks":personObj.sociallinks,
                "company":personObj.company,
                "link":personObj.link,
                "orcid":personObj.orcid,
                "activeflag":personObj.activeflag,
                "createdAt":personObj.createdAt,
                "updatedAt":personObj.updatedAt,
                "__v":personObj.__v,
                "emailNotifications":personObj.emailNotifications,
                "terms":personObj.terms,
                "counter":personObj.counter,
                "sector":personObj.sector,
                "organisation":personObj.organisation,
                "showOrganisation":personObj.showOrganisation
              }
            );
          })
        return res.json({success: true, data: personArray});
        })
        .catch(err => {
          return res.json({success: false, err});
      });
  }
);
  
module.exports = router