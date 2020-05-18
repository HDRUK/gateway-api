import express from 'express'
import { Data } from '../tool/data.model'
import { MessagesModel } from '../message/message.model'
import { utils } from "../auth";
import passport from "passport";
import { ROLES } from '../user/user.roles'

const router = express.Router()

router.get('/', async (req, res) => {
    res.status(200).json({ hello: 'Hello, from the back-end world!' })    
});

// @router   POST /api/mytools/add
// @desc     Add tools user
// @access   Private
router.post('/add', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Admin, ROLES.Creator),
    async (req, res) => {
      let data = new Data();
  
    const { type, name, link, description, categories, license, authors, tags, toolids } = req.body;
    data.id = parseInt(Math.random().toString().replace('0.', ''));
    data.type = type;
    data.name = name;
    data.link = link;
    data.description = description;
    data.categories.category = categories.category;
    data.categories.programmingLanguage = categories.programmingLanguage;
    data.categories.programmingLanguageVersion = categories.programmingLanguageVersion;
    data.license = license;
    data.authors = authors;
    data.tags.features = tags.features;
    data.tags.topics = tags.topics;
    data.activeflag = 'review';
    data.toolids = toolids;
    // data.updatedon = new Date();
    data.updatedon = Date.now();
  
    data.save((err) => {
      let message = new MessagesModel();
      message.messageID = parseInt(Math.random().toString().replace('0.', ''));
      message.messageTo = 0;
      message.messageObjectID = data.id;
      message.messageType = 'add';
      message.messageSent = Date.now();
      message.save((err) => {
        if (err) return res.json({ success: false, error: err });
        return res.json({ success: true, id: data.id });
      });
    });
  });

  /**
 * {put} /mytools/edit Edit tool
 * 
 * Authenticate user to see if page should be displayed.
 * Authenticate user and then pull the data for the tool from the DB.
 * When they submit, authenticate the user, validate the data and update the tool data on the DB.
 * (If we are going down the versions route then we will add a new version of the data and increase the version i.e. v1, v2)
 */
router.put(
    '/edit',
    passport.authenticate('jwt'),
    utils.checkIsInRole(ROLES.Admin, ROLES.Creator),
    async (req, res) => {
    const { id, type, name, link, description, categories, license, authors, toolids, tags } = req.body;
    Data.findOneAndUpdate({ id: id },
      {
        type: type,
        name: name,
        link: link,
        description: description,
        categories: {
          category: categories.category,
          programmingLanguage: categories.programmingLanguage,
          programmingLanguageVersion: categories.programmingLanguageVersion
        },
        license: license,
        authors: authors,
        tags: {
          features: tags.features,
          topics: tags.topics
        },
        toolids: toolids
      }, (err) => {
        if (err) return res.json({ success: false, error: err });
        return res.json({ success: true });
      });
  });

  /**
 * {delete} /mytools/delete Delete tool
 * 
 * Authenticate user to see if page should be displayed.
 * When they detele, authenticate user and then delete the tool data and review data from the DB
 */
router.delete('/delete', async (req, res) => {
    const { id } = req.body;
    Data.findOneAndDelete({ id: id }, (err) => {
      if (err) return res.send(err);
      return res.json({ success: true });
    });
  });

  
module.exports = router