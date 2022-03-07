import Mailchimp from 'mailchimp-api-v3';
import * as Sentry from '@sentry/node';
import Crypto from 'crypto';
import constants from '../../resources/utilities/constants.util';
import { UserModel } from '../../resources/user/user.model';
import { chunk } from 'lodash';

// See MailChimp API documentation for supporting info https://mailchimp.com/developer/marketing/api/

// Default service params
const apiKey = process.env.MAILCHIMP_API_KEY;
let mailchimp;
if (apiKey) mailchimp = new Mailchimp(apiKey);
const tags = ['Gateway User'];
const defaultSubscriptionStatus = constants.mailchimpSubscriptionStatuses.SUBSCRIBED;
const readEnv = process.env.NODE_ENV || 'prod';

/**
 * Create MailChimp Subscription Subscriber
 *
 * @desc    Adds a subscriber to a subscription with the status provided
 * @param 	{String} 	        subscriptionId 	    Unique identifier for a subscription to update
 * @param 	{String} 	        user 				User object containing bio details
 * @param 	{String} 			status 				New status to assign to an email address for a subscription - subscribed or pending
 */
const addSubscriptionMember = async (subscriptionId, user, status) => {
	if (apiKey) {
		// 1. Assemble payload POST body for MailChimp Marketing API
		let { id, email, firstname, lastname } = user;
		const body = {
			email_address: email,
			status: status || defaultSubscriptionStatus,
			status_if_new: status || defaultSubscriptionStatus,
			tags,
			merge_fields: {
				FNAME: firstname,
				LNAME: lastname,
			},
		};
		// 2. Track attempted update in Sentry using log
		if (readEnv === 'test' || readEnv === 'prod') {
			Sentry.addBreadcrumb({
				category: 'MailChimp',
				message: `Adding subscription for user: ${id} to subscription: ${subscriptionId}`,
				level: Sentry.Severity.Log,
			});
		}
		// 3. POST to MailChimp Marketing API to add the Gateway user to the MailChimp subscription members
		const md5email = Crypto.createHash('md5').update(email).digest('hex');
		await mailchimp.put(`lists/${subscriptionId}/members/${md5email}`, body).catch(err => {
			if (readEnv === 'test' || readEnv === 'prod') {
				Sentry.captureException(err);
			}
			console.error(`Message: ${err.message} Errors: ${JSON.stringify(err.errors)}`);
		});
	}
};

/**
 * Update MailChimp Subscription Membership For Gateway Users
 *
 * @desc    Updates memberships to a MailChimp subscription for a list of Gateway Users
 * @param 	{String} 	        subscriptionId 	    Unique identifier for a subscription to update
 * @param 	{Array<Object>} 	users 		        List of Gateway Users (max 500 per operation)
 * @param 	{String} 			status 				New status to assign to each membership for a subscription - subscribed, unsubscribed, cleaned or pending
 */
const updateSubscriptionUsers = async (subscriptionId, users = [], status) => {
	if (apiKey) {
		// 1. Build members array providing required metadata for MailChimp
		const members = users.map(user => {
			return {
				userId: user.id,
				email_address: user.email,
				status,
				tags,
				merge_fields: {
					FNAME: user.firstName,
					LNAME: user.lastName,
				},
			};
		});
		// 2. Update subscription members in MailChimp
		await updateSubscriptionMembers(subscriptionId, members);
	}
};

/**
 * Update MailChimp Subscription Subscribers
 *
 * @desc    Updates a subscription with a new status for each provided email address, new email addresses will be added
 * @param 	{String} 	        subscriptionId 	    Unique identifier for a subscription to update
 * @param 	{Array<Object>} 	members 		    List of email addresses to update (max 500)
 */
const updateSubscriptionMembers = async (subscriptionId, members) => {
	if (apiKey) {
		// 1. Chunk members into smaller payloads
		const allMembers = chunk(members, 100);
		// 2. Iterate through all chunks
		for (const membersChunk of allMembers) {
			// 3. Assemble payload POST body for MailChimp Marketing API
			const body = {
				members: membersChunk,
				skip_merge_validation: true,
				skip_duplicate_check: true,
				update_existing: true,
			};
			// 4. Track attempted updates in Sentry using log
			if (readEnv === 'test' || readEnv === 'prod') {
				Sentry.addBreadcrumb({
					category: 'MailChimp',
					message: `Updating subscribed for members: ${members.map(
						member => `${member.userId} to ${member.status}`
					)} against subscription: ${subscriptionId}`,
					level: Sentry.Severity.Log,
				});
			}
			// 5. POST to MailChimp Marketing API to update member statuses
			await mailchimp.post(`lists/${subscriptionId}`, body).catch(err => {
				if (readEnv === 'test' || readEnv === 'prod') {
					Sentry.captureException(err);
				}
				console.error(`Message: ${err.message} Errors: ${JSON.stringify(err.errors)}`);
			});
		}
	}
};

/**
 * Sync Gateway User With MailChimp Members Subscription Statuses
 *
 * @desc    Synchronises all subscription statuses between Gateway users based on account preferences with any changes reflected in MailChimp
 *          via external subscribe or unsubscribe methods e.g. mail link or sign up form sent via campaign
 * @param 	{String} 	        subscriptionId 	    Unique identifier for a subscription to update
 */
const syncSubscriptionMembers = async subscriptionId => {
	if (apiKey) {
		// 1. Track attempted sync in Sentry using log
		if (readEnv === 'test' || readEnv === 'prod') {
			Sentry.addBreadcrumb({
				category: 'MailChimp',
				message: `Syncing users for subscription: ${subscriptionId}`,
				level: Sentry.Severity.Log,
			});
		}
		// 2. Get total member count to anticipate chunking required to process all contacts
		const {
			stats: { member_count: subscribedCount, unsubscribe_count: unsubscribedCount },
		} = await mailchimp.get(`lists/${subscriptionId}?fields=stats.member_count,stats.unsubscribe_count`).catch(err => {
			if (readEnv === 'test' || readEnv === 'prod') {
				Sentry.captureException(err);
			}
			console.error(`Message: ${err.message} Errors: ${JSON.stringify(err.errors)}`);
		});
		const memberCount = subscribedCount + unsubscribedCount;
		// 3. Batch update database to sync MailChimp to reflect users unsubscribed/subscribed externally
		await batchImportFromMailChimp(subscriptionId, memberCount);
		// 4. Push any unsynchronised new contacts from Gateway to MailChimp
		await batchExportToMailChimp(subscriptionId);
	}
};

/**
 * Trigger MailChimp Status Import For Subscription
 *
 * @desc    Triggers an import of all member statuses for a subscription and updates the database with all changes found in MailChimp
 * @param 	{String} 	        subscriptionId 	        Unique identifier for a subscription to update
 * @param   {Number} 	        memberCount 	        The number of members currently registered against a subscription in MailChimp
 * @param   {String} 	        subscriptionBoolKey     The name of the boolean key that tracks the subscription status in the Gateway database
 */
const batchImportFromMailChimp = async (subscriptionId, memberCount) => {
	if (apiKey) {
		// 1. Get corresponding Gateway subscription variable e.g. feedback, news
		const subscriptionBoolKey = getSubscriptionBoolKey(subscriptionId);
		let processedCount = 0;
		// 2. Iterate bulk update process until all contacts have been processed from MailChimp
		while (processedCount < memberCount) {
			const { members = [] } = await mailchimp.get(`lists/${subscriptionId}/members?count=100&offset=${processedCount}`);
			let ops = [];
			// 3. For each member returned by MailChimp, create a bulk update operation to update the corresponding Gateway user if they exist
			members.forEach(member => {
				const subscribedBoolValue = member.status === 'subscribed' ? true : false;
				const { email_address: email } = member;
				ops.push({
					updateMany: {
						filter: { email },
						update: {
							[subscriptionBoolKey]: subscribedBoolValue,
						},
						upsert: false,
					},
				});
			});
			// 4. Run bulk update
			await UserModel.bulkWrite(ops);
			// 5. Increment counter to move onto next chunk of members
			processedCount = processedCount + members.length;
		}
	}
};

/**
 * Sync MailChimp Subscription Members With Users In Gateway
 *
 * @desc    Updates Subscription In MailChimp
 * @param 	{String} 	        subscriptionId 	    Unique identifier for a subscription to find the database key for
 */
const batchExportToMailChimp = async subscriptionId => {
	if (apiKey) {
		const subscriptionBoolKey = getSubscriptionBoolKey(subscriptionId);
		// 1. Get all users from db
		const users = await UserModel.find().select('id email firstname lastname news feedback').lean();
		// 2. Build members array providing required metadata for MailChimp
		const members = users.reduce((arr, user) => {
			// Get subscription status from user profile
			const status = user[subscriptionBoolKey]
				? constants.mailchimpSubscriptionStatuses.SUBSCRIBED
				: constants.mailchimpSubscriptionStatuses.UNSUBSCRIBED;
			// Check if the same email address has already been processed (email address can be attached to multiple user accounts)
			const memberIdx = arr.findIndex(member => member.email_address === user.email);
			if (status === constants.mailchimpSubscriptionStatuses.SUBSCRIBED) {
				if (memberIdx === -1) {
					// If email address has not be processed, return updated membership object
					return [
						...arr,
						{
							userId: user.id,
							email_address: user.email,
							status,
							tags,
							merge_fields: {
								FNAME: user.firstname,
								LNAME: user.lastname,
							},
						},
					];
				} else {
					// If email address has been processed, and the current status is unsubscribed, override membership status
					if (status === constants.mailchimpSubscriptionStatuses.UNSUBSCRIBED) {
						arr[memberIdx].status = constants.mailchimpSubscriptionStatuses.UNSUBSCRIBED;
					}
					return arr;
				}
			}
			return arr;
		}, []);
		// 3. Update subscription members in MailChimp
		await updateSubscriptionMembers(subscriptionId, members);
	}
};

/**
 * Determine Database Key For Subscription Status
 *
 * @desc    Returns the database key used to track the opt-in status per user for a subscription
 * @param 	{String} 	        subscriptionId 	    Unique identifier for a subscription to find the database key for
 */
const getSubscriptionBoolKey = subscriptionId => {
	// 1. Extract variables from environment settings
	const newsSubscriptionId = process.env.MAILCHIMP_NEWS_AUDIENCE_ID;
	const feedbackSubscriptionId = process.env.MAILCHIMP_FEEDBACK_AUDIENCE_ID;
	// 2. Assemble lookup table
	const lookup = {
		[newsSubscriptionId]: 'news',
		[feedbackSubscriptionId]: 'feedback',
	};
	// 3. Return relevant key for subscription
	return lookup[subscriptionId];
};

const mailchimpConnector = {
	addSubscriptionMember,
	updateSubscriptionUsers,
	syncSubscriptionMembers,
};

export default mailchimpConnector;
