import express from 'express'
import { ROLES } from '../user/user.roles'
import { Reviews } from './review.model';
import { Data } from '../tool/data.model'
import passport from "passport";
import { utils } from "../auth";
import { findPostsByTopicId } from "../discourse/discourse.service";
import { UserModel } from '../user/user.model'
import { MessagesModel } from '../message/message.model'
import {addTool, editTool, deleteTool, setStatus, getTools, getToolsAdmin} from '../tool/data.repository';
const sgMail = require('@sendgrid/mail');
const hdrukEmail = `enquiry@healthdatagateway.org`;
const router = express.Router()

// @router   POST /api/v1/add
// @desc     Add tools user
// @access   Private
router.post('/', 
  passport.authenticate('jwt'),
  utils.checkIsInRole(ROLES.Admin, ROLES.Creator),
    async (req, res) => {
      await addTool(req)
      .then(response => {
        return res.json({ success: true, response});
      })
      .catch(err => {
        return res.json({ success: false, err});
      })
    }
);

// @router   PUT /api/v1/{id}
// @desc     Edit tools user
// @access   Private
// router.put('/{id}', 
router.put('/:id', 
  passport.authenticate('jwt'),
  utils.checkIsInRole(ROLES.Admin, ROLES.Creator),
    async (req, res) => {
      await editTool(req)
      .then(response => {
        return res.json({ success: true, response});
      })
      .catch(err => {
        return res.json({ success: false, error: err.message});
      })
    }
);

// @router   GET /api/v1/get/admin
// @desc     Returns List of Tool objects
// @access   Private
router.get('/',
  passport.authenticate('jwt'),
  utils.checkIsInRole(ROLES.Admin, ROLES.Creator),
    async (req, res) => {
      req.params.type = "tool";
      let role = req.user.role;

      if(role === ROLES.Admin){
        await getToolsAdmin(req)
        .then(data => {
          return res.json({success: true, data});
        })
        .catch(err => {
          return res.json({success: false, err});
        });
      }
      else if(role === ROLES.Creator){
        await getTools(req)
        .then(data => {
          return res.json({success: true, data});
        })
        .catch(err => {
          return res.json({success: false, err});
        });
      }
    }
);

// @router   PATCH /api/v1/status
// @desc     Set tool status
// @access   Private
router.patch('/:id',
  passport.authenticate('jwt'),
  utils.checkIsInRole(ROLES.Admin),
    async (req, res) => {
      await setStatus(req)
        .then(response => {
          return res.json({success: true, response});
        })
        .catch(err => {
          return res.json({success: false, error: err.message});
        });
    }
);

/**
 * {get} /tool/:id Tool
 * 
 * Return the details on the tool based on the tool ID.
 */
router.get('/:id', async (req, res) => { 
    var query = Data.aggregate([
        { $match: { $and: [{ id: parseInt(req.params.id) }] } },
        { $lookup: { from: "tools", localField: "authors", foreignField: "id", as: "persons" } }
    ]);
    query.exec((err, data) => {
      if(data.length > 0){
          var p = Data.aggregate([
              { $match: { $and: [{ "relatedObjects": { $elemMatch: { "objectId": req.params.id } } }] } },
            ]);
            p.exec((err, relatedData) => {
                relatedData.forEach((dat) => {
                    dat.relatedObjects.forEach((x) => {
                        if (x.objectId === req.params.id && dat.id !== req.params.id) {
                            if (typeof data[0].relatedObjects === "undefined") data[0].relatedObjects=[];
                            data[0].relatedObjects.push({ objectId: dat.id, reason: x.reason, objectType: dat.type })
                        }
                    })
                });

                var r = Reviews.aggregate([
                    { $match: { $and: [{ toolID: parseInt(req.params.id) }, { activeflag: 'active' }] } },
                    { $sort: { date: -1 } },
                    { $lookup: { from: "tools", localField: "reviewerID", foreignField: "id", as: "person" } },
                    { $lookup: { from: "tools", localField: "replierID", foreignField: "id", as: "owner" } }
                ]);
                r.exec(async (err, reviewData) => {
                    if (err) return res.json({ success: false, error: err });

                    let discourseTopic = {};
                    if (data[0].discourseTopicId) {
                        discourseTopic = await findPostsByTopicId(data[0].discourseTopicId);
                    }

                    return res.json({ success: true, data: data, reviewData: reviewData, discourseTopic: discourseTopic });
                });
            });
          }
        else{
          return res.json({success: false, error: `Tool not found for tool id ${req.params.id}`})
        }
      });
});

/**
* {post} /tool/review/add Add review
* 
* Authenticate user to see if add review should be displayed.
* When they submit, authenticate the user, validate the data and add review data to the DB.
* We will also check the review (Free word entry) for exclusion data (node module?)
*/
router.post(
  '/review/add',
  passport.authenticate('jwt'),
  utils.checkIsInRole(ROLES.Admin, ROLES.Creator),
  async (req, res) => {
    let reviews = new Reviews(); 
    const { toolID, reviewerID, rating, projectName, review } = req.body;

    reviews.reviewID = parseInt(Math.random().toString().replace('0.', ''));
    reviews.toolID = toolID;
    reviews.reviewerID = reviewerID;
    reviews.rating = rating;
    reviews.projectName = projectName;
    reviews.review = review;
    reviews.activeflag = 'review';
    reviews.date = Date.now(); 

    reviews.save(async (err) => {
      if (err) {
        return res.json({ success: false, error: err })
      } else {
        return res.json({ success: true, id: reviews.reviewID });
      };
    });
  });

/**
 * {post} /tool/reply/add Add reply
 * 
 * Authenticate user to see if add reply should be displayed.
 * When they submit, authenticate the user, validate the data and add reply data to the DB.
 * We will also check the review (Free word entry) for exclusion data (node module?)
 */
router.post(
  '/reply',
  passport.authenticate('jwt'),
  utils.checkIsInRole(ROLES.Admin, ROLES.Creator),
  async (req, res) => {
    const { reviewID, replierID, reply } = req.body;
    Reviews.findOneAndUpdate({ reviewID: reviewID },
      {
        replierID: replierID,
        reply: reply,
        replydate: Date.now()
      }, (err) => {
        if (err) return res.json({ success: false, error: err });
        return res.json({ success: true });
      });
  });

/**
 * {post} /tool/review/approve Approve review
 * 
 * Authenticate user to see if user can approve.
 */
router.post(
  '/review/approve',
  passport.authenticate('jwt'),
  utils.checkIsInRole(ROLES.Admin),
  async (req, res) => { 
    const { id, activeflag } = req.body;
    // Get the emailNotification status for the current user
    let {emailNotifications = false} = await getObjectById(req.user.id)

    Reviews.findOneAndUpdate({ reviewID: id },
      {
        activeflag: activeflag
      }, (err) => {
        if (err) return res.json({ success: false, error: err });

        return res.json({ success: true });
      }).then(async (res) => {
        const review = await Reviews.findOne({ reviewID: id });

        await storeNotificationMessages(review);

        if(emailNotifications)
          await sendEmailNotifications(review);
      });
  });

/**
 * {delete} /tool/review/reject Reject review
 * 
 * Authenticate user to see if user can reject.
 */
router.delete(
  '/review/reject',
  passport.authenticate('jwt'),
  utils.checkIsInRole(ROLES.Admin),
  async (req, res) => {
    const { id } = req.body;
    Reviews.findOneAndDelete({ reviewID: id }, (err) => {
      if (err) return res.send(err);
      return res.json({ success: true });
    });
  });

/**
 * {delete} /tool/review/delete Delete review
 * 
 * When they delete, authenticate the user and remove the review data from the DB.
 */
router.delete(
  '/review/delete',
  passport.authenticate('jwt'),
  utils.checkIsInRole(ROLES.Admin, ROLES.Creator),
  async (req, res) => {
    const { id } = req.body;
    Data.findOneAndDelete({ id: id }, (err) => {
      if (err) return res.send(err);
      return res.json({ success: true });
    });
  });

//Validation required if Delete is to be implemented
// router.delete('/:id',
//   passport.authenticate('jwt'),
//   utils.checkIsInRole(ROLES.Admin, ROLES.Creator),
//     async (req, res) => {
//       await deleteTool(req, res)
//         .then(response => {
//           return res.json({success: true, response});
//         })
//         .catch(err => {
//           res.status(204).send(err);
//         });
//     }
// );

module.exports = router

async function storeNotificationMessages(review) {

  const tool = await Data.findOne({ id: review.toolID });
  //Get reviewer name
  const reviewer = await UserModel.findOne({ id: review.reviewerID });
  const toolLink = process.env.homeURL + '/tool/' + review.toolID + '/' + tool.name
  //admins
  let message = new MessagesModel();
  message.messageID = parseInt(Math.random().toString().replace('0.', ''));
  message.messageTo = 0;
  message.messageObjectID = review.toolID;
  message.messageType = 'review';
  message.messageSent = Date.now();
  message.isRead = false;
  message.messageDescription = `${reviewer.firstname} ${reviewer.lastname} gave a ${review.rating}-star review to your tool ${tool.name} ${toolLink}`

  await message.save(async (err) => {
    if (err) {
      return new Error({ success: false, error: err });
    }
  })
  //authors
  const authors = tool.authors;
  authors.forEach(async (author) => {
    message.messageTo = author;
    await message.save(async (err) => {
      if (err) {
        return new Error({ success: false, error: err });
      }
    });
  });
  return { success: true, id: message.messageID };
}

async function sendEmailNotifications(review) {
  //Get email recipients 
  let emailRecipients = await UserModel.find({ role: 'Admin' });
  const tool = await Data.findOne({ id: review.toolID });


  (await UserModel.find({ id: { $in: tool.authors } }))
    .forEach(author => {
      emailRecipients.push(author)
    });

  //Get reviewer name
  const reviewer = await UserModel.findOne({ id: review.reviewerID });
  const toolLink = process.env.homeURL + '/tool/' + tool.id + '/' + tool.name
  sgMail.setApiKey(process.env.SENDGRID_API_KEY);

  //send emails
  for (let emailRecipient of emailRecipients) {
    const msg = {
      to: emailRecipient.email,
      from: `${hdrukEmail}`,
      subject: `Someone reviewed your tool`,
      html: `${reviewer.firstname} ${reviewer.lastname} gave a ${review.rating}-star review to your tool ${tool.name} <br /><br />  ${toolLink}`
    };
    await sgMail.send(msg);
  }
}