import express from 'express'
import { ROLES } from '../user/user.roles'
import passport from "passport";
import { utils } from "../auth";
// import { UserModel } from '../user/user.model'
import { Collections } from '../collections/collections.model'; 
import { MessagesModel } from '../message/message.model';
import { UserModel } from '../user/user.model'
import { getObjectById } from '../tool/data.repository';
import emailGenerator from '../utilities/emailGenerator.util';
import helper from '../utilities/helper.util';
const inputSanitizer = require('../utilities/inputSanitizer');

const urlValidator = require('../utilities/urlValidator');

const hdrukEmail = `enquiry@healthdatagateway.org`;

const router = express.Router()

router.get('/:collectionID', async (req, res) => { 
  var q = Collections.aggregate([
    { $match: { $and: [{ id: parseInt(req.params.collectionID) }] } },

    { $lookup: { from: "tools", localField: "authors", foreignField: "id", as: "persons" } }  

  ]); 
  q.exec((err, data) => {
    data[0].persons = helper.hidePrivateProfileDetails(data[0].persons);
    if (err) return res.json({ success: false, error: err });
    return res.json({ success: true, data: data });
  });
});

router.get('/datasetid/:datasetID', async (req, res) => {  
  var q = Collections.aggregate([
      { $match: { $and: [{ "relatedObjects": { $elemMatch: { "objectId": req.params.datasetID } } }, {publicflag: true}, {activeflag: "active"} ] } },
      { $lookup: { from: "tools", localField: "authors", foreignField: "id", as: "persons" } },
      { $project: { _id: 1, id: 1, name: 1, description: 1, imageLink: 1, relatedObjects: 1, 'persons.firstname': 1, 'persons.lastname': 1  }}
  ]);

  q.exec((err, data) => {
    if (err) return res.json({ success: false, error: err });
    return res.json({ success: true, data: data });
  });
});

router.put('/edit', 
  passport.authenticate('jwt'),
  utils.checkIsInRole(ROLES.Admin, ROLES.Creator),
  async (req, res) => {
    const collectionCreator = req.body.collectionCreator;
    var {id, name, description, imageLink, authors, relatedObjects } = req.body;
    imageLink = urlValidator.validateURL(imageLink); 

    Collections.findOneAndUpdate({ id: id }, 
      {
        name: inputSanitizer.removeNonBreakingSpaces(name),
        description: inputSanitizer.removeNonBreakingSpaces(description),
        imageLink: imageLink,
        authors: authors, 
        relatedObjects: relatedObjects
      }, (err) => {
        if(err) {
          return res.json({ success: false, error: err });
        }
      }).then(() => {
        return res.json({ success: true });
      })   
  }); 

router.post('/add',
  passport.authenticate('jwt'),
  utils.checkIsInRole(ROLES.Admin, ROLES.Creator),
  async (req, res) => {
    let collections = new Collections();

    const collectionCreator = req.body.collectionCreator;

    const {name, description, imageLink, authors, relatedObjects } = req.body;

    collections.id = parseInt(Math.random().toString().replace('0.', ''));
    collections.name = inputSanitizer.removeNonBreakingSpaces(name);
    collections.description = inputSanitizer.removeNonBreakingSpaces(description);
    collections.imageLink = imageLink;
    collections.authors = authors;
    collections.relatedObjects = relatedObjects;
    collections.activeflag = 'active'; 

    try {
        if (collections.authors) {
          collections.authors.forEach(async (authorId) => {
            await createMessage(authorId, collections, collections.activeflag, collectionCreator);
          });
        }
        await createMessage(0, collections, collections.activeflag, collectionCreator);

        // Send email notifications to all admins and authors who have opted in
        await sendEmailNotifications(collections, collections.activeflag, collectionCreator);

      } catch (err) {
        console.log(err);
        // return res.status(500).json({ success: false, error: err });
      }

    collections.save((err) => {
        if (err) {
            return res.json({ success: false, error: err })
        } else {
          return res.json({ success: true, id: collections.id })
        }
    });

  }); 

  router.put('/status',  
  passport.authenticate('jwt'),
  utils.checkIsInRole(ROLES.Admin, ROLES.Creator), 
  async (req, res) => { 

    var {id, activeflag } = req.body;
    var isAuthorAdmin = false; 

    var q = Collections.aggregate([
      { $match: { $and: [{ id: parseInt(req.body.id) }, {authors: req.user.id}] } }
    ]);
    q.exec((err, data) => {
      if(data.length === 1) {
        isAuthorAdmin = true;
      } 
      
      if(req.user.role === 'Admin') {
        isAuthorAdmin = true;
      } 

      if(isAuthorAdmin){
          Collections.findOneAndUpdate({ id: id },   
            {
              activeflag: activeflag
            }, (err) => {
              if(err) {
                return res.json({ success: false, error: err }); 
              }
            }).then(() => {
              return res.json({ success: true });
            })  

        } else {
          return res.json({ success: false, error: 'Not authorised' }); 
        }
    });
  }); 

  router.delete('/delete/:id', 
    passport.authenticate('jwt'),
    utils.checkIsInRole(ROLES.Admin, ROLES.Creator),
    async (req, res) => {
      var isAuthorAdmin = false; 

      var q = Collections.aggregate([
        { $match: { $and: [{ id: parseInt(req.params.id) }, {authors: req.user.id}] } }
      ]);
      q.exec((err, data) => {

        if(data.length === 1) {
          isAuthorAdmin = true;
        } 
        
        if(req.user.role === 'Admin') {
        isAuthorAdmin = true;
        } 
  
        if(isAuthorAdmin){
        Collections.findOneAndRemove({id: req.params.id}, (err) => {
            if (err) return res.send(err);
            return res.json({ success: true });
          });
  
          } else {
          return res.json({ success: false, error: 'Not authorised' }); 
          }
      });
  });

  module.exports = router;

  async function createMessage(authorId, collections, activeflag, collectionCreator) { 
    let message = new MessagesModel();
    
    const collectionLink = process.env.homeURL + '/collection/' + collections.id; 
    const messageRecipients = await UserModel.find({ $or: [{ role: 'Admin' }, { id: { $in: collections.authors } }] });
    async function saveMessage() { 
      message.messageID = parseInt(Math.random().toString().replace('0.', ''));
      message.messageTo = authorId;
      message.messageObjectID = collections.id;
      message.messageSent = Date.now();
      message.isRead = false;
      await message.save();
    }

    if (authorId === 0) {
        message.messageType = 'added collection';
        message.messageDescription = `${collectionCreator.name} added a new collection: ${collections.name}.`
        saveMessage();
    }

    for (let messageRecipient of messageRecipients) {
      if (activeflag === 'active' && authorId === messageRecipient.id && authorId === collectionCreator.id){
        message.messageType = 'added collection';
        message.messageDescription = `Your new collection ${collections.name} has been added.`
        saveMessage();
      } 
      else if (activeflag === 'active' && authorId === messageRecipient.id && authorId !== collectionCreator.id) {
        message.messageType = 'added collection';
        message.messageDescription = `${collectionCreator.name} added you as a collaborator on the new collection ${collections.name}.`
        saveMessage();
      }
   }

    //UPDATE WHEN ARCHIVE/DELETE IS AVAILABLE FOR COLLECTIONS
    // else if (activeflag === 'archive') {
    //   message.messageType = 'rejected';
    //   message.messageDescription = `Your ${toolType} ${toolName} has been rejected ${collectionLink}`
    // }
  }
  
  async function sendEmailNotifications(collections, activeflag, collectionCreator) {
    let subject;
    let html;
    // 1. Generate URL for linking collection in email
    const collectionLink = process.env.homeURL + '/collection/' + collections.id;

    // 2. Build email body
    emailRecipients.map((emailRecipient) => {
      if(activeflag === 'active' && emailRecipient.role === 'Admin'){
        subject = `New collection ${collections.name} has been added and is now live`
        html = `New collection ${collections.name} has been added and is now live <br /><br />  ${collectionLink}`
      }

      collections.authors.map((author) => {
        if(activeflag === 'active' && author === emailRecipient.id && author === collectionCreator.id){
          subject = `Your collection ${collections.name} has been added and is now live`
          html = `Your collection ${collections.name} has been added and is now live <br /><br />  ${collectionLink}`
        } else if (activeflag === 'active' && author === emailRecipient.id && author !== collectionCreator.id) {
          subject = `You have been added as a collaborator on collection ${collections.name}`
          html = `${collectionCreator.name} has added you as a collaborator to the collection ${collections.name} which is now live <br /><br />  ${collectionLink}`
        } 
      })
    })

    if (activeflag === 'active') {
      subject = `Your collection ${collections.name} has been approved and is now live`
      html = `Your collection ${collections.name} has been approved and is now live <br /><br />  ${collectionLink}`
    } 
    //UPDATE WHEN ARCHIVE/DELETE IS AVAILABLE FOR COLLECTIONS
    // else if (activeflag === 'archive') {
    //   subject = `Your collection ${collections.name} has been rejected`
    //   html = `Your collection ${collections.name} has been rejected <br /><br />  ${collectionLink}`
    // }

    // 3. Query Db for all admins or authors of the collection who have opted in to email updates
    var q = UserModel.aggregate([
      // Find all users who are admins or authors of this collection
      { $match: { $or: [{ role: 'Admin' }, { id: { $in: collections.authors } }] } },
      // Perform lookup to check opt in/out flag in tools schema
      { $lookup: { from: 'tools', localField: 'id', foreignField: 'id', as: 'tool' } },
      // Filter out any user who has opted out of email notifications
      { $match: { 'tool.emailNotifications': true } },
      // Reduce response payload size to required fields
      { $project: { _id: 1, firstname: 1, lastname: 1, email: 1, role: 1, 'tool.emailNotifications': 1 } }
    ]);

    // 4. Use the returned array of email recipients to generate and send emails with SendGrid
    q.exec((err, emailRecipients) => {
      if (err) {
        return new Error({ success: false, error: err });
      }
      emailGenerator.sendEmail(
        emailRecipients,
        `${hdrukEmail}`,
        subject,
        html
      );
    });
  }
 
  