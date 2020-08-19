import mongoose, { Mongoose } from 'mongoose';
import { TopicModel } from './topic.model';
import { Data as ToolModel } from '../tool/data.model';
import _ from 'lodash';

module.exports = {
    buildRecipients: async (tool, createdBy) => {
        console.log(tool.team);
        const { team: { members }} = tool || [];
        let recipients = members.map(m => m.memberid);
        return recipients || [];
        // return [
        //     "5eb29430861979081c1f6acd", 
        //     "5f03530178e28143d7af2eb1"
        // ];
    },

    buildTopic: async (context) => {
        try {
            let subTitle = '';
            const { createdBy, relatedObjectId} = context;
            // 1. Topic cannot be created without related object i.e. data/project/tool/paper
            if(!relatedObjectId) {
                console.error('No related object Id passed to build topic');
                return undefined;
            }
            // 2. Find the related object in MongoDb and include team data
            const tool = await ToolModel.findById(relatedObjectId).populate('team');
            // 3. Return undefined if no object exists
            if(!tool) {
                console.error(`Failed to find related tool with objectId: ${relatedObjectId}`);
                return undefined;
            }
            // 4. Deconstruct tool props
            let { name: title, type, datasetfields, datasetid = '' } = tool;
            // 5. Switch based on related object type
            switch(type) {
                // If dataset, we require the publisher
                case 'dataset':
                    ({publisher: subTitle} = datasetfields);
                    break;
                default:
                    console.log('default');
            }
            // 6. Get recipients for topic/message
            const recipients = await module.exports.buildRecipients(tool, createdBy);
            if(_.isEmpty(recipients)) {
                console.error('A topic cannot be created without recipients');
                return undefined;
            }
            // 7. Create new topic against related object with recipients
            const topic = await TopicModel.create({
                title,
                subTitle,
                relatedObjectId,
                createdBy,
                createdDate: Date.now(),
                recipients,
                dataSetId: datasetid
            });
            // 8. Return created object
            return topic;
        } catch (err) {
            console.error(err.message);
            return undefined;
        }
    },

    findTopic: async (topicId, userId) => {
        try {
            const topic = await TopicModel.findOne({ 
                    _id: new mongoose.Types.ObjectId(topicId), 
                    recipients: { $elemMatch : { $eq: userId }}
            });
            if (!topic) 
                return undefined

            // Append property to indicate the number of unread messages
            topic.topicMessages.forEach(message => {
                if(!message.readBy.includes(userId)) {
                    topic.unreadMessages ++;
                }
            })

            return topic;
        }
        catch (err) {
            console.error(err.message);
            return undefined;
        }
    },

    // POST /api/v1/topics
    createTopic: async (req, res) => {
        try {
            const { _id: createdBy } = req.user;
            const { relatedObjectId } = req.body;
            const topic = await buildTopic({createdBy, relatedObjectId});

            if(!topic)
                return res.status(500).json({ success: false, message: 'Could not save topic to database.' });

                return res.status(201).json({ success: true, topic }); 

        } catch (err) {
            console.error(err.message);
            return res.status(500).json(err);
        }
    },
    // DELETE api/v1/topics/:id
    deleteTopic: async(req, res) => { 
        try {
            const { id } = req.params;

            if(!id) 
                return res.status(404).json({ success: false, message: 'Topic Id not found.' });
            
            const topic = await TopicModel.findByIdAndUpdate( id, { isDeleted: true, status: 'closed', expiryDate: Date.now() }, {new: true});

            console.log(topic);

            return res.status(204).json({success: true});

        } catch (err) {
            console.error(err.message);
            return res.status(500).json(err);
        }
    },
    // GET api/v1/topics
    getTopics: async(req, res) => {
        // check if user / publisher
        try {
            let {_id: userId} = req.user;

            const topics = await TopicModel.find({ 
                recipients: { $elemMatch : { $eq: userId }},
                status: 'active'
            });

            // Append property to indicate the number of unread messages
            topics.forEach(topic => {
                topic.unreadMessages = 0;
                topic.topicMessages.forEach(message => {
                    if(!message.readBy.includes(userId)) {
                        topic.unreadMessages ++;
                    }
                    // Calculate last unread message date at topic level
                    topic.lastUnreadMessage = topic.topicMessages.reduce((a, b) => {
                        console.log (Date(a.createdDate) > new Date(b.createdDate) ? a : b);
                        return (new Date(a.createdDate) > new Date(b.createdDate) ? a : b).createdDate;
                    });
                })
            });

            // Sort topics by most unread first followed by created date
            topics.sort((a, b) => a.unreadMessages - b.unreadMessages || b.lastUnreadMessage - a.lastUnreadMessage);
            
            return res.status(200).json({ success: true, topics });

        } catch(err) {
            console.error(err.message);
            return res.status(500).json(err);
        }
    },
    // GET api/v1/topics/:id
    getTopicById: async(req, res) => {
        try {
            // 1. Get the topic from the database
            const topic = await module.exports.findTopic(req.params.id, req.user._id);
            // 2. Keep a copy of the unmodified topic for returning in this response
            const dispatchTopic = topic.toJSON();
            if(!topic)
                return res.status(404).json({ success: false, message: 'Could not find topic specified.' });
            // 3. If there any unread messages, mark them as read 
            if(topic.unreadMessages > 0) {
                topic.topicMessages.forEach(async (message) => {
                    message.readBy.push(req.user._id)
                    await message.save();
                });
                topic.unreadMessages = 0;
                // 4. Save topic to Mongo
                await topic.save();
            }
            // 5. Return original topic so unread messages are displayed correctly
            return res.status(200).json({ success: true, topic: dispatchTopic });

        } catch (err) {
            console.error(err.message);
            return res.status(500).json(err);
        }
    }
}
