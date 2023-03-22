import express from 'express';
import {
	createDiscourseTopic,
	getDiscourseTopic,
	deleteDiscoursePost,
	createDiscoursePost,
	updateDiscoursePost,
} from './discourse.service';
import { Data } from '../tool/data.model';
import { Collections } from '../collections/collections.model';
import { ROLES } from '../user/user.roles';
import passport from 'passport';
import { utils } from '../auth';
import _ from 'lodash';

const inputSanitizer = require('../utilities/inputSanitizer');
const router = express.Router();

/**
 * @route /api/v1/discourse/topic/:topicId
 * @description This route retrieves all the data for a Discourse topic in the context of the system
 * @return This routes returns an object containing 'Topic' data - see Discourse docs
 */
router.get('/topic/:topicId', async (req, res) => {
	try {
		// 1. Pull topic Id from endpoint route value
		const topicId = parseInt(req.params.topicId);
		// 2. Get the Discourse topic using the Id
		await getDiscourseTopic(topicId)
			.then(topic => {
				// 3. If no topic could be found, return 404
				if (!topic) {
					return res.status(404).json({ success: false, error: 'Topic not found.' });
				}
				// 4. Return topic data
				return res.json({ success: true, topic });
			})
			.catch(error => {
				return res.status(500).json({ success: false, error: error.message });
			});
	} catch (err) {
		process.stdout.write(`DISCOURSE - GET TOPIC: ${err.message}`);
		return res.status(500).json({ success: false, error: 'Error retrieving the topic, please try again later...' });
	}
});

/**
 * @route /api/v1/discourse/user/topic/:topicId
 * @description This route retrieves all the data for a Discourse topic in the context of a specific user
 * @return This routes returns an object containing 'Topic' data - see Discourse docs
 */
router.get('/user/topic/:topicId', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Admin, ROLES.Creator), async (req, res) => {
	try {
		// 1. Pull topic Id from endpoint route value
		const topicId = parseInt(req.params.topicId);
		// 2. Get the Discourse topic using the Id
		await getDiscourseTopic(topicId, req.user)
			.then(topic => {
				// 3. If no topic could be found, return 404
				if (!topic) {
					return res.status(404).json({ success: false, error: 'Topic not found.' });
				}
				// 4. Return topic data
				return res.json({ success: true, topic });
			})
			.catch(error => {
				return res.status(500).json({ success: false, error: error.message });
			});
	} catch (err) {
		process.stdout.write(`DISCOURSE - GET TOPIC: ${err.message}`);
		return res.status(500).json({ success: false, error: 'Error retrieving the topic, please try again later...' });
	}
});

/**
 * @route /api/v1/discourse/topic/tool/:toolId
 * @description This route creates a Discourse new topic if the tool exists and is active.
 * @return This routes returns an object { link: linkToDiscourseTopic, posts: Array of Discourse posts, (should be empty) }
 */
router.put('/tool/:toolId', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Admin, ROLES.Creator), async (req, res) => {
	try {
		// 1. Pull tool Id from endpoint route value
		const toolId = parseInt(req.params.toolId);
		// 2. Get the corresponding tool document from MongoDb
		await Data.findOne({ id: toolId })
			.then(async tool => {
				// 3. If no tool was found, return 404
				if (!tool) {
					return res.status(404).json({ success: false, error: 'Tool not found.' });
				}
				// 4. Create a new Discourse topic for the tool
				const topicId = await createDiscourseTopic(tool);
				// 5. Get the details of the new topic from Discourse
				const topic = await getDiscourseTopic(topicId, req.user);
				// 6. Return the topic data
				return res.json({ success: true, data: topic });
			})
			.catch(error => {
				return res.status(500).json({ success: false, error: error.message });
			});
	} catch (err) {
		process.stdout.write(`DISCOURSE - PUT TOPIC: ${err.message}`);
		return res.status(500).json({ success: false, error: 'Error creating the topic, please try again later...' });
	}
});

/**
 * @route /api/v1/discourse/user/posts/
 * @description This route creates a Discourse new topic if the tool exists and is active.
 * @return This routes returns an object { link: linkToDiscourseTopic, posts: Array of Discourse posts (should have at least one) }
 */
router.post('/user/posts', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Admin, ROLES.Creator), async (req, res) => {
	try {
		let { toolId, collectionId, topicId, comment } = req.body;
		comment = inputSanitizer.removeNonBreakingSpaces(comment);
		// 1. Check if the topicId has been passed, if it is 0, the topic needs to be created
		if (!topicId) {
			// 2. Check if comment is on a tool or collection
			if (toolId) {
				// 3. Get the tool details from MongoDb to create the new topic
				await Data.findOne({ id: { $eq: toolId } })
					.then(async tool => {
						// 4. If no tool was found, return 404
						if (!tool) {
							return res.status(404).json({ success: false, error: 'Tool not found.' });
						}
						// 5. Create a new Discourse topic for the tool
						topicId = await createDiscourseTopic(tool);
						// 6. Add the user's post to the new topic
						await createDiscoursePost(topicId, comment, req.user);
						// 7. Get topic for return
						const topic = await getDiscourseTopic(topicId, req.user);
						// 8. Return success with topic data
						return res.json({ success: true, topic });
					})
					.catch(error => {
						return res.status(500).json({ success: false, error: error.message });
					});
			} else if (collectionId) {
				// 3. Get the collection details from MongoDb to create the new topic
				await Collections.findOne({ id: { $eq: parseInt(collectionId) } })
					.then(async collection => {
						// 4. If no collection was found, return 404
						if (!collection) {
							return res.status(404).json({ success: false, error: 'Collection not found.' });
						}
						// 5. Create a new Discourse topic for the collection
						collection.type = 'collection';
						topicId = await createDiscourseTopic(collection);
						// 6. Add the user's post to the new topic
						await createDiscoursePost(topicId, comment, req.user);
						// 7. Get topic for return
						const topic = await getDiscourseTopic(topicId, req.user);
						// 8. Return success with topic data
						return res.json({ success: true, topic });
					})
					.catch(error => {
						return res.status(500).json({ success: false, error: error.message });
					});
			}
		} else {
			// 2. Add the user's post to the existing topic
			await createDiscoursePost(topicId, comment, req.user);
			// 3. Get the updated topic data
			const topic = await getDiscourseTopic(topicId, req.user);
			// 4. Return success
			return res.json({ success: true, topic });
		}
	} catch (err) {
		process.stdout.write(`DISCOURSE - POSTS: ${err.message}`);
		return res.status(500).json({ success: false, error: 'Error creating the topic, please try again later...' });
	}
});

/**
 * @route /api/v1/discourse/user/posts/
 * @description This route updates a Discourse post
 * @return This routes returns a success message
 */
router.put('/user/posts/:postId', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Admin, ROLES.Creator), async (req, res) => {
	try {
		// 1. Pull post Id from endpoint route value
		const postId = parseInt(req.params.postId);
		// 2. If valid post Id was not passed, return error
		if (!postId) {
			return res.status(500).json({ success: false, error: 'Post identifier was not specified' });
		}
		// 2. Pull the new content from the request body
		const { comment } = req.body;
		// 3. Perform update of post in Discourse
		const post = await updateDiscoursePost(postId, inputSanitizer.removeNonBreakingSpaces(comment), req.user);
		// 4. Get the updated topic data
		const topic = await getDiscourseTopic(post.topic_id, req.user);
		// 5. Return the topic data
		return res.json({ success: true, topic });
	} catch (err) {
		process.stdout.write(`DISCOURSE - PUT POSTS: ${err.message}`);
		return res.status(500).json({ success: false, error: 'Error editing the post, please try again later...' });
	}
});

/**
 * @route /api/v1/discourse/user/post/:postId
 * @description This route deletes a specific post and must be either owned by the requesting user or the user is an Admin of Discourse
 * @return This routes returns a message indicating success or failure in delete
 */
router.delete('/user/posts/:postId', passport.authenticate('jwt'), utils.checkIsInRole(ROLES.Admin, ROLES.Creator), async (req, res) => {
	try {
		// 1. Pull post Id from endpoint route value
		const postId = parseInt(req.params.postId);
		// 2. Call Discourse to delete the post
		deleteDiscoursePost(postId, req.user)
			.then(() => {
				// 3. Return success message
				return res.json({ success: true });
			})
			.catch(err => {
				return res.status(500).json({ success: false, error: err.message });
			});
	} catch (err) {
		process.stdout.write(`DISCOURSE - DELETE POSTS: ${err.message}`);
		return res.status(500).json({ success: false, error: 'Error deleting the topic, please try again later...' });
	}
});

module.exports = router;
