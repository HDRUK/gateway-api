import { Course } from './course.model';
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
    return await Course.findOne({ id }).exec()
}

const addCourse = async (req, res) => {
  return new Promise(async(resolve, reject) => {
        let course = new Course();
        course.id = parseInt(Math.random().toString().replace('0.', ''));
        course.type = 'course';
        course.creator = req.user.id;
        course.activeflag = 'review';
        course.updatedon = Date.now();
        course.relatedObjects = req.body.relatedObjects;
        
        course.title = inputSanitizer.removeNonBreakingSpaces(req.body.title);
        course.link = inputSanitizer.removeNonBreakingSpaces(req.body.link);
        course.provider = inputSanitizer.removeNonBreakingSpaces(req.body.provider);
        course.description = inputSanitizer.removeNonBreakingSpaces(req.body.description);
        course.courseDelivery = inputSanitizer.removeNonBreakingSpaces(req.body.courseDelivery);
        course.location = inputSanitizer.removeNonBreakingSpaces(req.body.location);
        course.keywords = inputSanitizer.removeNonBreakingSpaces(req.body.keywords);
        course.domains = inputSanitizer.removeNonBreakingSpaces(req.body.domains);
       
        if (req.body.courseOptions) {
            req.body.courseOptions.forEach((x) => {
                if (x.flexibleDates) x.startDate = null;
                x.studyMode = inputSanitizer.removeNonBreakingSpaces(x.studyMode);
                x.studyDurationMeasure = inputSanitizer.removeNonBreakingSpaces(x.studyDurationMeasure);
                if (x.fees) {
                    x.fees.forEach((y) => {
                        y.feeDescription = inputSanitizer.removeNonBreakingSpaces(y.feeDescription);
                        y.feePer = inputSanitizer.removeNonBreakingSpaces(y.feePer);
                    });
                }
            });
        }
        course.courseOptions = req.body.courseOptions;

        if (req.body.entries) {
            req.body.entries.forEach((x) => {
                x.level = inputSanitizer.removeNonBreakingSpaces(x.level);
                x.subject = inputSanitizer.removeNonBreakingSpaces(x.subject);
            });
        }
        course.entries = req.body.entries;

        course.restrictions = inputSanitizer.removeNonBreakingSpaces(req.body.restrictions);
        course.award = inputSanitizer.removeNonBreakingSpaces(req.body.award);
        course.competencyFramework = inputSanitizer.removeNonBreakingSpaces(req.body.competencyFramework);
        course.nationalPriority = inputSanitizer.removeNonBreakingSpaces(req.body.nationalPriority);


      







      let newCourse = await course.save();
      if(!newCourse) 
        reject(new Error(`Can't persist data object to DB.`));

        await createMessage(course.creator, course.id, course.title, course.type, 'add');  
        await createMessage(0, course.id, course.title, course.type, 'add');
        // Send email notification of status update to admins and authors who have opted in
        await sendEmailNotifications(course, 'add');  
      resolve(newCourse);
    })
};







const editCourse = async (req, res) => {
  return new Promise(async(resolve, reject) => {
    let id = req.params.id;

    if(req.body.entries){
        req.body.entries.forEach((e) => {   
          e.level = inputSanitizer.removeNonBreakingSpaces(e.level);
          e.subject = (inputSanitizer.removeNonBreakingSpaces(e.subject));
      });
    }
    
    if (req.body.courseOptions) {
        req.body.courseOptions.forEach((x) => {
            if (x.flexibleDates) x.startDate = null;
            x.studyMode = inputSanitizer.removeNonBreakingSpaces(x.studyMode);
            x.studyDurationMeasure = inputSanitizer.removeNonBreakingSpaces(x.studyDurationMeasure);
            if (x.fees) {
                x.fees.forEach((y) => {
                    y.feeDescription = inputSanitizer.removeNonBreakingSpaces(y.feeDescription);
                    y.feePer = inputSanitizer.removeNonBreakingSpaces(y.feePer);
                });
            }
        });
    }
    
    let relatedObjects = req.body.relatedObjects;
    let courseOptions = req.body.courseOptions;
    let entries = req.body.entries;

   Course.findOneAndUpdate({ id: id },
      {
        title: inputSanitizer.removeNonBreakingSpaces(req.body.title),
        link: urlValidator.validateURL(inputSanitizer.removeNonBreakingSpaces(req.body.link)),
        provider: inputSanitizer.removeNonBreakingSpaces(req.body.provider),
        description: inputSanitizer.removeNonBreakingSpaces(req.body.description),
        courseDelivery: inputSanitizer.removeNonBreakingSpaces(req.body.courseDelivery),
        location: inputSanitizer.removeNonBreakingSpaces(req.body.location),
        keywords: inputSanitizer.removeNonBreakingSpaces(req.body.keywords),
        domains: inputSanitizer.removeNonBreakingSpaces(req.body.domains),
        relatedObjects: relatedObjects,
        courseOptions: courseOptions,
        entries:entries,
        restrictions: inputSanitizer.removeNonBreakingSpaces(req.body.restrictions),
        award: inputSanitizer.removeNonBreakingSpaces(req.body.award),
        competencyFramework: inputSanitizer.removeNonBreakingSpaces(req.body.competencyFramework),
        nationalPriority: inputSanitizer.removeNonBreakingSpaces(req.body.nationalPriority),
      }, (err) => {
        if (err) {
          reject(new Error(`Failed to update.`));
        }
      }).then(async (course) => {
        if(course == null){
          reject(new Error(`No record found with id of ${id}.`));
        } 
        
        await createMessage(course.creator, id, course.title, course.type, 'edit');  
        await createMessage(0, id, course.title, course.type, 'edit');
        // Send email notification of status update to admins and authors who have opted in
        await sendEmailNotifications(course, 'edit');  
        
        resolve(course);
      });
    })
  };

  const deleteCourse = async(req, res) => {
    return new Promise(async(resolve, reject) => {
      const { id } = req.params.id;
      Course.findOneAndDelete({ id: req.params.id }, (err) => {
        if (err) reject(err);

        
      }).then((course) => {
        if(course == null){
          reject(`No Content`);
        }
        else{
          resolve(id);
        }
      }
    )
  })};

  const getCourseAdmin = async (req, res) => {
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
      if (req.query.q) {
        searchString = req.query.q || "";;
      }

      let searchQuery = { $and: [{ type: 'course' }] };
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

  const getCourse = async (req, res) => {
    return new Promise(async (resolve, reject) => {
      //let startIndex = 0;
      //let limit = 1000;
      let idString = req.user.id;
  
      /* if (req.query.startIndex) {
        startIndex = req.query.startIndex;
      }
      if (req.query.limit) {
        limit = req.query.limit;
      } */
      if (req.query.id) {
        idString = req.query.id;
      }
  
      let query = Course.aggregate([
        { $match: { $and: [{ type: 'course' }, { creator: parseInt(idString) }] } },
        { $lookup: { from: "tools", localField: "creator", foreignField: "id", as: "persons" } },
        { $sort: { updatedAt : -1}}
      ]);//.skip(parseInt(startIndex)).limit(parseInt(maxResults));
      query.exec((err, data) => {
        if (err) reject({ success: false, error: err });

        data.map(dat => {
          dat.persons = helper.hidePrivateProfileDetails(dat.persons);
        });
        resolve(data);
      });
    });
  }

  const setStatus = async (req, res) => { 
    return new Promise(async (resolve, reject) => {
      try {
        const { activeflag, rejectionReason } = req.body;
        const id = req.params.id;
      
        let course = await Course.findOneAndUpdate({ id: id }, { $set: { activeflag: activeflag } });
        if (!course) {
          reject(new Error('Course not found'));
        }
  
        
        await createMessage(course.creator, id, course.title, course.type, activeflag, rejectionReason);
        await createMessage(0, id, course.title, course.type, activeflag, rejectionReason);
  
        if (!course.discourseTopicId && course.activeflag === 'active') {
          await createDiscourseTopic(course);
        }
        
        // Send email notification of status update to admins and authors who have opted in
        await sendEmailNotifications(course, activeflag, rejectionReason);
  
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
    else if (activeflag === 'add') {
        message.messageType = 'add';
        message.messageDescription = `Your ${toolType} ${toolName} has been submitted for approval`
      }
    else if (activeflag === 'edit') {
        message.messageType = 'edit';
        message.messageDescription = `Your ${toolType} ${toolName} has been updated`
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
    let adminCanUnsubscribe = true;
    // 1. Generate tool URL for linking user from email
    const toolLink = process.env.homeURL + '/' + tool.type + '/' + tool.id

    // 2. Build email body
    if (activeflag === 'active') {
      subject = `Your ${tool.type} ${tool.title} has been approved and is now live`
      html = `Your ${tool.type} ${tool.title} has been approved and is now live <br /><br />  ${toolLink}`
    } else if (activeflag === 'archive') {
      subject = `Your ${tool.type} ${tool.title} has been archived`
      html = `Your ${tool.type} ${tool.title} has been archived <br /><br /> ${toolLink}`
    } else if (activeflag === 'rejected') {
      subject = `Your ${tool.type} ${tool.title} has been rejected`
      html = `Your ${tool.type} ${tool.title} has been rejected <br /><br />  Rejection reason: ${rejectionReason} <br /><br /> ${toolLink}`
    }
    else if (activeflag === 'add') {
        subject = `Your ${tool.type} ${tool.title} has been submitted for approval`
        html = `Your ${tool.type} ${tool.title} has been submitted for approval<br /><br /> ${toolLink}`
        adminCanUnsubscribe = false;
      }
      else if (activeflag === 'edit') {
        subject = `Your ${tool.type} ${tool.title} has been updated`
        html = `Your ${tool.type} ${tool.title} has been updated<br /><br /> ${toolLink}`
      }

    if(adminCanUnsubscribe){
      // 3. Find the creator of the course and admins if they have opted in to email updates
      var q = UserModel.aggregate([
        // Find the creator of the course and Admins
        { $match: { $or: [{ role: 'Admin' }, { id: tool.creator }] } },
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
    else{
      // 3. Find the creator of the course if they have opted in to email updates
      var q = UserModel.aggregate([
        // Find all authors of this tool
        { $match: { id: tool.creator } },
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

      // 5. Find all admins regardless of email opt-in preference
      q = UserModel.aggregate([
        // Find all admins 
        { $match: { role: 'Admin' } },
        // Reduce response payload size to required fields
        { $project: {_id: 1, firstname: 1, lastname: 1, email: 1, role: 1 } }
      ]);
  
      // 6. Use the returned array of email recipients to generate and send emails with SendGrid
      q.exec((err, emailRecipients) => {
        if (err) {
          return new Error({ success: false, error: err });
        }
        emailGenerator.sendEmail(
          emailRecipients,
          `${hdrukEmail}`,
          subject,
          html,
          adminCanUnsubscribe
        );
      });
    }
  }

async function sendEmailNotificationToAuthors(tool, toolOwner) {
    // 1. Generate tool URL for linking user from email
    const toolLink = process.env.homeURL + '/course/' + tool.id
    
    // 2. Find all authors of the tool who have opted in to email updates
    var q = UserModel.aggregate([
      // Find all authors of this tool
      { $match: { id: tool.creator } },
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
  
    //normal user
    var toolCopy = JSON.parse(JSON.stringify(tool));
    var listToEmail = [toolCopy.creator];
    
    asyncModule.eachSeries(listToEmail, async (author) => {
        const user = await UserModel.findById(author)
      let message = new MessagesModel();
      message.messageType = 'author';
      message.messageSent = Date.now();
      message.messageDescription = `${toolOwner.name} added you as an author of the ${toolCopy.type} ${toolCopy.title}`
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
    q = Course.aggregate([
        { $match: newSearchQuery },
        { $lookup: { from: "tools", localField: "creator", foreignField: "id", as: "persons" } },
        { $lookup: { from: "tools", localField: "id", foreignField: "authors", as: "objects" } },
        { $lookup: { from: "reviews", localField: "id", foreignField: "toolID", as: "reviews" } }
    ]).sort({ updatedAt : -1}).skip(parseInt(startIndex)).limit(parseInt(limit));
  }
  else{
    q = Course.aggregate([
      { $match: newSearchQuery },
      { $lookup: { from: "tools", localField: "creator", foreignField: "id", as: "persons" } },
      { $lookup: { from: "tools", localField: "id", foreignField: "authors", as: "objects" } },
      { $lookup: { from: "reviews", localField: "id", foreignField: "toolID", as: "reviews" } }
    ]).sort({ score: { $meta: "textScore" } }).skip(parseInt(startIndex)).limit(parseInt(limit));
  }
  return new Promise((resolve, reject) => {
      q.exec((err, data) => {
          if (typeof data === "undefined") {
            resolve([]);
          }
          else {
            data.map(dat => {
              dat.persons = helper.hidePrivateProfileDetails(dat.persons);
            });
            resolve(data);
          }
      })
  })
};

export { addCourse, editCourse, deleteCourse, setStatus, getCourse, getCourseAdmin }