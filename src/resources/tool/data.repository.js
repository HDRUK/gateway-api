import { Data } from './data.model';
import { MessagesModel } from '../message/message.model'
import { UserModel } from '../user/user.model'
import { createDiscourseTopic } from '../discourse/discourse.service'
import emailGenerator from '../utilities/emailGenerator.util';
import helper from '../utilities/helper.util';
const asyncModule = require('async');
const hdrukEmail = `enquiry@healthdatagateway.org`;
const urlValidator = require('../utilities/urlValidator');
const inputSanitizer = require('../utilities/inputSanitizer');

export async function getObjectById(id) {
    return await Data.findOne({ id }).exec()
}

const addTool = async (req, res) => {
  return new Promise(async(resolve, reject) => {
      let data = new Data(); 
      const toolCreator = req.body.toolCreator; 
      const { type, name, link, description, resultsInsights, categories, license, authors, tags, journal, journalYear, relatedObjects, programmingLanguage, isPreprint } = req.body;
      data.id = parseInt(Math.random().toString().replace('0.', ''));
      data.type = inputSanitizer.removeNonBreakingSpaces(type);
      data.name = inputSanitizer.removeNonBreakingSpaces(name);
      data.link = urlValidator.validateURL(inputSanitizer.removeNonBreakingSpaces(link));  
      data.journal = inputSanitizer.removeNonBreakingSpaces(journal);
      data.journalYear = inputSanitizer.removeNonBreakingSpaces(journalYear); 
      data.description = inputSanitizer.removeNonBreakingSpaces(description);
      data.resultsInsights = inputSanitizer.removeNonBreakingSpaces(resultsInsights);
      console.log(req.body)
      if (categories && typeof categories !== undefined) data.categories.category = inputSanitizer.removeNonBreakingSpaces(categories.category);
      data.license = inputSanitizer.removeNonBreakingSpaces(license);
      data.authors = authors;
      data.tags.features = inputSanitizer.removeNonBreakingSpaces(tags.features),
      data.tags.topics = inputSanitizer.removeNonBreakingSpaces(tags.topics);
      data.activeflag = 'review';
      data.updatedon = Date.now();
      data.relatedObjects = relatedObjects;

      if(programmingLanguage){
        programmingLanguage.forEach((p) => 
        {   
            p.programmingLanguage = inputSanitizer.removeNonBreakingSpaces(p.programmingLanguage);
            p.version = (inputSanitizer.removeNonBreakingSpaces(p.version));
        });
      }
      data.programmingLanguage = programmingLanguage;

      data.isPreprint = isPreprint;
      data.uploader = req.user.id;
      let newDataObj = await data.save();
      if(!newDataObj)
        reject(new Error(`Can't persist data object to DB.`));

      let message = new MessagesModel();
      message.messageID = parseInt(Math.random().toString().replace('0.', ''));
      message.messageTo = 0;
      message.messageObjectID = data.id;
      message.messageType = 'add';
      message.messageDescription = `Approval needed: new ${data.type} added ${name}`
      message.messageSent = Date.now();
      message.isRead = false;
      let newMessageObj = await message.save();
      if(!newMessageObj)
        reject(new Error(`Can't persist message to DB.`));

      // 1. Generate URL for linking tool from email
      const toolLink = process.env.homeURL + '/' + data.type + '/' + data.id 

      // 2. Query Db for all admins who have opted in to email updates
      var q = UserModel.aggregate([
        // Find all users who are admins
        { $match: { role: 'Admin' } },
        // Reduce response payload size to required fields
        { $project: { _id: 1, firstname: 1, lastname: 1, email: 1, role: 1 } }
      ]);

      // 3. Use the returned array of email recipients to generate and send emails with SendGrid
      q.exec((err, emailRecipients) => {
        if (err) {
          return new Error({ success: false, error: err });
        }
        emailGenerator.sendEmail(
          emailRecipients,
          `${hdrukEmail}`,
          `A new ${data.type} has been added and is ready for review`,
          `Approval needed: new ${data.type} ${data.name} <br /><br />  ${toolLink}`,
          false
        );
      });

      if (data.type === 'tool') {
          await sendEmailNotificationToAuthors(data, toolCreator);
      }
      await storeNotificationsForAuthors(data, toolCreator);

      resolve(newDataObj);
    })
};


const editTool = async (req, res) => {
  return new Promise(async(resolve, reject) => {

    const toolCreator = req.body.toolCreator;
    let { type, name, link, description, resultsInsights, categories, license, authors, tags, journal, journalYear, relatedObjects, isPreprint } = req.body;
    let id = req.params.id;
    let programmingLanguage = req.body.programmingLanguage;

    if (!categories || typeof categories === undefined) categories = {'category':'', 'programmingLanguage':[], 'programmingLanguageVersion':''}

    if(programmingLanguage){
      programmingLanguage.forEach((p) => 
      {   
          p.programmingLanguage = inputSanitizer.removeNonBreakingSpaces(p.programmingLanguage);
          p.version = (inputSanitizer.removeNonBreakingSpaces(p.version));
      });
    }
    
    let data = {
      id: id,
      name: name,
      authors: authors,
    };

    Data.findOneAndUpdate({ id: id },
      {
        type: inputSanitizer.removeNonBreakingSpaces(type),
        name: inputSanitizer.removeNonBreakingSpaces(name),
        link: urlValidator.validateURL(inputSanitizer.removeNonBreakingSpaces(link)),
        description: inputSanitizer.removeNonBreakingSpaces(description),
        resultsInsights: inputSanitizer.removeNonBreakingSpaces(resultsInsights),
        journal: inputSanitizer.removeNonBreakingSpaces(journal),
        journalYear: inputSanitizer.removeNonBreakingSpaces(journalYear),
        categories: {
          category: inputSanitizer.removeNonBreakingSpaces(categories.category),
          programmingLanguage: categories.programmingLanguage,
          programmingLanguageVersion: categories.programmingLanguageVersion
        },
        license: inputSanitizer.removeNonBreakingSpaces(license),
        authors: authors,
        programmingLanguage: programmingLanguage,
        tags: {
          features: inputSanitizer.removeNonBreakingSpaces(tags.features),
          topics: inputSanitizer.removeNonBreakingSpaces(tags.topics)
        },
        relatedObjects: relatedObjects,
        isPreprint: isPreprint
      }, (err) => {
        if (err) {
          reject(new Error(`Failed to update.`));
        }
      }).then((tool) => {
        if(tool == null){
          reject(new Error(`No record found with id of ${id}.`));
        } 
        else if (type === 'tool') {
          // Send email notification of update to all authors who have opted in to updates
          sendEmailNotificationToAuthors(data, toolCreator);
          storeNotificationsForAuthors(data, toolCreator);
        }
          resolve(tool);
      });
    })
  };

  const deleteTool = async(req, res) => {
    return new Promise(async(resolve, reject) => {
      const { id } = req.params.id;
      Data.findOneAndDelete({ id: req.params.id }, (err) => {
        if (err) reject(err);

        
      }).then((tool) => {
        if(tool == null){
          reject(`No Content`);
        }
        else{
          resolve(id);
        }
      }
    )
  })};

  const getToolsAdmin = async (req, res) => {
    return new Promise(async (resolve, reject) => {

      let startIndex = 0;
      let limit = 1000;
      let typeString = "";
      let searchString = "";
      
      if (req.query.offset) {
        startIndex = req.query.offset;
      }
      if (req.query.limit) {
        limit = req.query.limit;
      }
      if (req.params.type) {
        typeString = req.params.type;
      }
      if (req.query.q) {
        searchString = req.query.q || "";;
      }

      let searchQuery = { $and: [{ type: typeString }] };
      let searchAll = false;

      if (searchString.length > 0) {
          searchQuery["$and"].push({ $text: { $search: searchString } });
        }
      else {
          searchAll = true;
      }
      await Promise.all([
          getObjectResult(typeString, searchAll, searchQuery, startIndex, limit),
      ]).then((values) => {
        resolve(values[0]);
    });
    });
  }

  const getTools = async (req, res) => {
    return new Promise(async (resolve, reject) => {
      let startIndex = 0;
      let limit = 1000;
      let typeString = "";
      let idString = req.user.id;
  
      if (req.query.startIndex) {
        startIndex = req.query.startIndex;
      }
      if (req.query.limit) {
        limit = req.query.limit;
      }
      if (req.params.type) {
        typeString = req.params.type;
      }
      if (req.query.id) {
        idString = req.query.id;
      }
  
      let query = Data.aggregate([
        { $match: { $and: [{ type: typeString }, { authors: parseInt(idString) }] } },
        { $lookup: { from: "tools", localField: "authors", foreignField: "id", as: "persons" } },
        { $sort: { updatedAt : -1}}
      ])//.skip(parseInt(startIndex)).limit(parseInt(maxResults));
      query.exec((err, data) => {
        data.map(dat => {
          dat.persons = helper.hidePrivateProfileDetails(dat.persons);
        });
        if (err) reject({ success: false, error: err });
        resolve(data);
      });
    });
  }

  const setStatus = async (req, res) => {
    return new Promise(async (resolve, reject) => {
      try {
        const { activeflag, rejectionReason } = req.body;
        const id = req.params.id;
      
        let tool = await Data.findOneAndUpdate({ id: id }, { $set: { activeflag: activeflag } });
        if (!tool) {
          reject(new Error('Tool not found'));
        }
  
        if (tool.authors) {
          tool.authors.forEach(async (authorId) => {
            await createMessage(authorId, id, tool.name, tool.type, activeflag, rejectionReason);
          });
        }
        await createMessage(0, id, tool.name, tool.type, activeflag, rejectionReason);
  
        if (!tool.discourseTopicId && tool.activeflag === 'active') {
          await createDiscourseTopic(tool);
        }
        
        // Send email notification of status update to admins and authors who have opted in
        await sendEmailNotifications(tool, activeflag, rejectionReason);
  
        resolve(id);
        
      } catch (err) {
        console.log(err);
        reject(new Error(err));
      }
    });
  };

  async function createMessage(authorId, toolId, toolName, toolType, activeflag, rejectionReason) {
    let message = new MessagesModel();
    const toolLink = process.env.homeURL + '/' + toolType + '/' + toolId;
  
    if (activeflag === 'active') {
      message.messageType = 'approved';
      message.messageDescription = `Your ${toolType} ${toolName} has been approved and is now live ${toolLink}`
    } else if (activeflag === 'archive') {
      message.messageType = 'archive';
      message.messageDescription = `Your ${toolType} ${toolName} has been archived ${toolLink}`
    } else if (activeflag === 'rejected') {
      message.messageType = 'rejected';
      message.messageDescription = `Your ${toolType} ${toolName} has been rejected ${toolLink}`
      message.messageDescription = (rejectionReason) ? message.messageDescription.concat(` Rejection reason: ${rejectionReason}`) : message.messageDescription
    }
    message.messageID = parseInt(Math.random().toString().replace('0.', ''));
    message.messageTo = authorId;
    message.messageObjectID = toolId;
    message.messageSent = Date.now();
    message.isRead = false;
    await message.save();
  }
  
  async function sendEmailNotifications(tool, activeflag, rejectionReason) {
    let subject;
    let html;
    // 1. Generate tool URL for linking user from email
    const toolLink = process.env.homeURL + '/' + tool.type + '/' + tool.id

    // 2. Build email body
    if (activeflag === 'active') {
      subject = `Your ${tool.type} ${tool.name} has been approved and is now live`
      html = `Your ${tool.type} ${tool.name} has been approved and is now live <br /><br />  ${toolLink}`
    } else if (activeflag === 'archive') {
      subject = `Your ${tool.type} ${tool.name} has been archived`
      html = `Your ${tool.type} ${tool.name} has been archived <br /><br /> ${toolLink}`
    } else if (activeflag === 'rejected') {
      subject = `Your ${tool.type} ${tool.name} has been rejected`
      html = `Your ${tool.type} ${tool.name} has been rejected <br /><br />  Rejection reason: ${rejectionReason} <br /><br /> ${toolLink}`
    }
    
    // 3. Find all authors of the tool who have opted in to email updates
    var q = UserModel.aggregate([
      // Find all authors of this tool
      { $match: { $or: [{ role: 'Admin' }, { id: { $in: tool.authors } }] } },
      // Perform lookup to check opt in/out flag in tools schema
      { $lookup: { from: 'tools', localField: 'id', foreignField: 'id', as: 'tool' } },
      // Filter out any user who has opted out of email notifications
      { $match: { 'tool.emailNotifications': true } },
      // Reduce response payload size to required fields
      { $project: {_id: 1, firstname: 1, lastname: 1, email: 1, role: 1, 'tool.emailNotifications': 1 } }
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

async function sendEmailNotificationToAuthors(tool, toolOwner) {
    // 1. Generate tool URL for linking user from email
    const toolLink = process.env.homeURL + '/tool/' + tool.id
    
    // 2. Find all authors of the tool who have opted in to email updates
    var q = UserModel.aggregate([
      // Find all authors of this tool
      { $match: { id: { $in: tool.authors } } },
      // Perform lookup to check opt in/out flag in tools schema
      { $lookup: { from: 'tools', localField: 'id', foreignField: 'id', as: 'tool' } },
      // Filter out any user who has opted out of email notifications
      { $match: { 'tool.emailNotifications': true } },
      // Reduce response payload size to required fields
      { $project: {_id: 1, firstname: 1, lastname: 1, email: 1, role: 1, 'tool.emailNotifications': 1 } }
    ]);

    // 3. Use the returned array of email recipients to generate and send emails with SendGrid
    q.exec((err, emailRecipients) => {
      if (err) {
        return new Error({ success: false, error: err });
      }
      emailGenerator.sendEmail(
        emailRecipients,
        `${hdrukEmail}`,
        `${toolOwner.name} added you as an author of the tool ${tool.name}`,
        `${toolOwner.name} added you as an author of the tool ${tool.name} <br /><br />  ${toolLink}`
      );
    });
  };

async function storeNotificationsForAuthors(tool, toolOwner) {
    //store messages to alert a user has been added as an author
    const toolLink = process.env.homeURL + '/tool/' + tool.id
  
    //normal user
    var toolCopy = JSON.parse(JSON.stringify(tool));
    
    toolCopy.authors.push(0);
    asyncModule.eachSeries(toolCopy.authors, async (author) => {
  
      let message = new MessagesModel();
      message.messageType = 'author';
      message.messageSent = Date.now();
      message.messageDescription = `${toolOwner.name} added you as an author of the ${toolCopy.type} ${toolCopy.name}`
      message.isRead = false;
      message.messageObjectID = toolCopy.id;
      message.messageID = parseInt(Math.random().toString().replace('0.', ''));
      message.messageTo = author;
  
      await message.save(async (err) => {
        if (err) {
          return new Error({ success: false, error: err });
        }
        return { success: true, id: message.messageID };
      });
    }); 
};

function getObjectResult(type, searchAll, searchQuery, startIndex, limit) {
  let newSearchQuery = JSON.parse(JSON.stringify(searchQuery));
  let q = '';

  if (searchAll) {
    q = Data.aggregate([
        { $match: newSearchQuery },
        { $lookup: { from: "tools", localField: "authors", foreignField: "id", as: "persons" } },
        { $lookup: { from: "tools", localField: "id", foreignField: "authors", as: "objects" } },
        { $lookup: { from: "reviews", localField: "id", foreignField: "toolID", as: "reviews" } }
    ]).sort({ updatedAt : -1}).skip(parseInt(startIndex)).limit(parseInt(limit));
  }
  else{
    q = Data.aggregate([
      { $match: newSearchQuery },
      { $lookup: { from: "tools", localField: "authors", foreignField: "id", as: "persons" } },
      { $lookup: { from: "tools", localField: "id", foreignField: "authors", as: "objects" } },
      { $lookup: { from: "reviews", localField: "id", foreignField: "toolID", as: "reviews" } }
    ]).sort({ score: { $meta: "textScore" } }).skip(parseInt(startIndex)).limit(parseInt(limit));
  }
  return new Promise((resolve, reject) => {
      q.exec((err, data) => {
          data.map(dat => {
            dat.persons = helper.hidePrivateProfileDetails(dat.persons);
          });
          if (typeof data === "undefined") resolve([]);
          else resolve(data);
      })
  })
};

export { addTool, editTool, deleteTool, setStatus, getTools, getToolsAdmin }