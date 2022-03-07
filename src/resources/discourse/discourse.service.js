import { Data } from '../tool/data.model';
import { UserModel } from '../user/user.model';

import axios from 'axios';
import { HmacSHA256 } from 'crypto-js';
import base64url from 'base64url';
import _ from 'lodash';
import { Collections } from '../collections/collections.model';

/**
 * Gets a Discourse topic containing posts
 *
 * @param {number} topicId The unique identifier for the topic
 * @param {object} user The user object deserialised from the request cookie
 * @return {object} Discourse topic posts and content link
 */
export async function getDiscourseTopic(topicId, user) {
	// Guard clause for invalid identifier passed
	if (!topicId) {
		throw new Error("Topic can't be null");
	}
	// 1. Get the requesting users credentials to retrieve the topic in their context
	const config = await getCredentials(user, false);
	try {
		// 2. Issue GET request to Discourse to return the topic in response
		const response = await axios.get(`${process.env.DISCOURSE_URL}/t/${topicId}.json`, config);
		// 3. Remove the first post as it is system generated
		let postsLength = response.data.post_stream.posts.length;
		let posts = response.data.post_stream.posts.slice(1, postsLength);
		// 4. Set the avatar size in each post and place each post into read mode by default
		posts.map(post => {
			post.avatar_template = `${process.env.DISCOURSE_URL}${post.avatar_template.replace('{size}', '46')}`;
			post.mode = 'read';
		});
		// 5. Sort the post array by descending datetime created
		posts.sort(function (a, b) {
			return new Date(b.created_at) - new Date(a.created_at);
		});
		// 6. Return the topic details
		return {
			link: `${process.env.DISCOURSE_URL}/t/${response.data.slug}/${topicId}`,
			posts: posts,
		};
	} catch (err) {
		console.error(err.message);
	}
}

/**
 * Creates a Discourse topic for a tool
 *
 * @param {object} tool The tool for which the Discourse topic should be created
 * @return {int} The unique identifier for the new topic
 */
export async function createDiscourseTopic(tool) {
	// 1. Establish system access config for Discourse as this is always used to create a topic
	const config = {
		headers: {
			'Api-Key': process.env.DISCOURSE_API_KEY,
			'Api-Username': 'system',
			'user-agent': 'node.js',
			'Content-Type': 'application/json',
		},
	};
	// 2. Depending on tool type passed, generate initial post content based on tool description and original content link
	var rawIs, categoryIs;
	if (tool.type === 'tool') {
		rawIs = `${tool.description} <br><br> Original content: ${process.env.GATEWAY_WEB_URL}/tool/${tool.id}`;
		categoryIs = process.env.DISCOURSE_CATEGORY_TOOLS_ID;
	} else if (tool.type === 'project') {
		rawIs = `${tool.description} <br><br> Original content: ${process.env.GATEWAY_WEB_URL}/project/${tool.id}`;
		categoryIs = process.env.DISCOURSE_CATEGORY_PROJECTS_ID;
	} else if (tool.type === 'dataset') {
		let {
			datasetfields: { abstract },
		} = tool;
		rawIs = `${tool.description || abstract} <br><br> Original content: ${process.env.GATEWAY_WEB_URL}/dataset/${tool.pid}`;
		categoryIs = process.env.DISCOURSE_CATEGORY_DATASETS_ID;
	} else if (tool.type === 'paper') {
		rawIs = `${tool.description} <br><br> Original content: ${process.env.GATEWAY_WEB_URL}/paper/${tool.id}`;
		categoryIs = process.env.DISCOURSE_CATEGORY_PAPERS_ID;
	} else if (tool.type === 'course') {
		rawIs = `${tool.description} <br><br> Original content: ${process.env.GATEWAY_WEB_URL}/course/${tool.id}`;
		categoryIs = process.env.DISCOURSE_CATEGORY_COURSES_ID;
	} else if (tool.type === 'collection') {
		rawIs = `${tool.description} <br><br> Original content: ${process.env.GATEWAY_WEB_URL}/collection/${tool.id}`;
		categoryIs = process.env.DISCOURSE_CATEGORY_COLLECTIONS_ID;
	}
	// 3. Assemble payload for creating a topic in Discourse
	if (tool.type === 'course') tool.title;
	else tool.name;
	const payload = {
		title: tool.name,
		raw: rawIs,
		category: categoryIs,
	};
	// 4. POST to Discourse to create the post
	try {
		const res = await axios.post(`${process.env.DISCOURSE_URL}/posts.json`, payload, config);
		// 5. If post was successful, update tool in MongoDb with topic identifier
		if (res.data.topic_id) {
			// 6. Check tool type and Return the topic identifier
			if (tool.type === 'collection') {
				await Collections.findOneAndUpdate({ id: tool.id }, { $set: { discourseTopicId: res.data.topic_id } });
				return res.data.topic_id;
			} else {
				await Data.findOneAndUpdate({ id: tool.id }, { $set: { discourseTopicId: res.data.topic_id } });
				return res.data.topic_id;
			}
		}
	} catch (err) {
		console.error(err.message);
	}
}

/**
 * Creates a Discourse post against a topic
 *
 * @param {number} topicId The unique identifier for the topic
 * @param {string} comment The text content for the post
 * @param {object} user The user object deserialised from the request cookie
 */
export async function createDiscoursePost(topicId, comment, user) {
	// Guard clause for invalid identifier passed
	if (!topicId) {
		return new Error("Topic can't be null");
	}
	// Validation clause to ensure new post is at least 20 characters as per client side validation
	if (comment.length < 20) {
		return new Error('A Discourse post must be 20 characters or longer');
	}
	// 1. Get the Discourse user credentials based on the requesting user
	const config = await getCredentials(user, true);
	// 2. Assemble payload to create new post
	const payload = {
		topic_id: topicId,
		raw: comment,
	};
	// 3. POST to Discourse to create new post in the context of the current user
	try {
		await axios.post(`${process.env.DISCOURSE_URL}/posts.json`, payload, config);
	} catch (err) {
		console.error(err.message);
	}
}

/**
 * Updates a Discourse post with new content
 *
 * @param {number} postId The unique identifier for the post
 * @param {string} comment The new text content for the post
 * @param {object} user The user object deserialised from the request cookie
 * @return {object} Updated post data
 */
export async function updateDiscoursePost(postId, comment, user) {
	// Guard clause for invalid identifier passed
	if (!postId) {
		return new Error("Topic can't be null");
	}
	// Validation clause to ensure new post is at least 20 characters as per client side validation
	if (comment.length < 20) {
		return new Error('A Discourse post must be 20 characters or longer');
	}
	// 1. Get the Discourse user credentials based on the requesting user
	const config = await getCredentials(user, true);
	// 2. Assemble payload to create new post
	const payload = {
		raw: comment,
	};
	// 3. PUT to Discourse to update existing post in the context of the current user
	try {
		const response = await axios.put(`${process.env.DISCOURSE_URL}/posts/${postId}.json`, payload, config);
		const {
			data: { post },
		} = response;
		// 4. Return the post data
		return post;
	} catch (err) {
		console.error(err.message);
	}
}

/**
 * Activate a user in Discourse
 *
 * @param {object} user Contains the details to activate a user in Discourse
 * @return {object} Credentials for the new user to access Discourse APIs
 */
export async function registerDiscourseUser(user) {
	// 1. Call internal function to generate Discourse SSO user for a gateway user
	return await getCredentials(user, false);
}

/**
 * Delete a post from Discourse
 *
 * @param {number} postId The unique identifier for the post
 * @param {object} user The user object deserialised from the request cookie
 */
export async function deleteDiscoursePost(postId, user) {
	// Guard clause for invalid identifier passed
	if (!postId) {
		return new Error("Post can't be null");
	}
	// 1. Get the Discourse user credentials based on the requesting user
	const config = await getCredentials(user, true);
	// 3. DELETE to Discourse to remove post in the context of the current user
	try {
		await axios.delete(`${process.env.DISCOURSE_URL}/posts/${postId}`, config);
	} catch (err) {
		console.error(err.message);
	}
}

/**
 * Creates a new user in Discourse
 *
 * @param {number} id The unique identifier for the user
 * @param {string} email The email address to use for the new user
 * @param {string} username The username for the new user based on {firstnane.lastname}
 * @return {object} User object from Discourse
 */
async function createUser({ id, email, username }) {
	// 1. Establish system access config for Discourse as this is always used to create users
	const config = {
		headers: {
			'Api-Key': process.env.DISCOURSE_API_KEY,
			'Api-Username': 'system',
			'user-agent': 'node.js',
			'Content-Type': 'application/json',
		},
	};
	const sso_secret = process.env.DISCOURSE_SSO_SECRET;
	// 1. Create SSO payload using users details
	const sso_params = `external_id=${id}&email=${email}&username=${username}`;
	// 2. Base64 encode params to create expected payload
	const sso_payload = base64url(sso_params);
	// 3. Generate SSO signature from SSO payload
	const sig = HmacSHA256(sso_payload, sso_secret).toString();
	// 4. Assemble Disource endpoint payload
	const payload = {
		sso: sso_payload,
		sig,
	};
	// 5. POST to Discourse sync SSO endpoint to create the new user
	try {
		const res = await axios.post(`${process.env.DISCOURSE_URL}/admin/users/sync_sso`, payload, config);
		// 6. Return the new user object from Discourse
		return res.data;
	} catch (err) {
		console.error(err.message);
	}
}

/**
 * Generates an API Key for an existing Discourse user
 *
 * @param {string} discourseUsername The username in Discourse to generate a key for
 * @return {string} User API Key
 */
async function generateAPIKey(discourseUsername) {
	// 1. Establish system access config for Discourse as this is always used to generate user API keys
	const config = {
		headers: {
			'Api-Key': process.env.DISCOURSE_API_KEY,
			'Api-Username': 'system',
			'user-agent': 'node.js',
			'Content-Type': 'application/json',
		},
	};
	// 1. Assemble payload to create API key for user in Discourse
	const payload = {
		key: {
			username: discourseUsername,
			description: 'Auto generated API key by HDR-UK Innovation Gateway',
		},
	};
	// 2. POST request to Discourse and expect API key in response
	try {
		const res = await axios.post(`${process.env.DISCOURSE_URL}/admin/api/keys`, payload, config);
		const {
			data: {
				key: { key },
			},
		} = res;
		// 3. Return key
		return key;
	} catch (err) {
		console.error(err.message);
		return '';
	}
}

/**
 * Gets the Discourse API Key for an existing user or the system depending on context
 *
 * @param {object} user The user object deserialised from the request cookie
 * @param {boolean} strict Determines whether a user requesting endpoint can default to system impersonation
 * @return {object} Configuration object for subsequent Discourse API calls
 */
async function getCredentials(user, strict) {
	// 1. Return default system credentials if no user provided and endpoint should allow system access
	if (!user && !strict) {
		return {
			headers: {
				'Api-Key': process.env.DISCOURSE_API_KEY,
				'Api-Username': 'system',
				'user-agent': 'node.js',
				'Content-Type': 'application/json',
			},
		};
	} else if (!user && strict) {
		throw new Error('Unauthorised access attempted');
	}
	// 2. Deconstruct user object deserialised from cookie in request
	let { id, discourseUsername, discourseKey, firstname, lastname, email } = user;
	// 3. If gateway user has no Discourse username then register and generate API key
	if (_.isEmpty(discourseUsername)) {
		try {
			const username = `${firstname.toLowerCase()}.${lastname.toLowerCase()}`;
			// 4. Create Discourse user
			const discourseUser = await createUser({ id, email, username });
			discourseUsername = discourseUser.username;
			// 5. Generate Discourse API key for user
			discourseKey = await generateAPIKey(discourseUser.username);
			// 6. Update MongoDb to contain users Discourse credentials
			await UserModel.findOneAndUpdate({ id: { $eq: id } }, { $set: { discourseUsername, discourseKey } });
		} catch (err) {
			console.error(err.message);
		}
		// 3. If user has username but no API key, generate new one
	} else if (_.isEmpty(discourseKey)) {
		try {
			// 4. Generate Discourse API key for user
			discourseKey = await generateAPIKey(discourseUsername);
			// 5. Update MongoDb to contain users Discourse credentials
			await UserModel.findOneAndUpdate({ id: { $eq: id } }, { $set: { discourseUsername, discourseKey } });
		} catch (err) {
			console.error(err.message);
		}
	}
	// Return identification payload of registered Discourse user
	return {
		headers: {
			'Api-Key': discourseKey,
			'Api-Username': discourseUsername,
			'user-agent': 'node.js',
			'Content-Type': 'application/json',
		},
	};
}
