import { Client, NumberOfRetries } from '@hubspot/api-client';
import * as Sentry from '@sentry/node';
import { isEmpty, get, isNil, isNull } from 'lodash';

import { UserModel } from '../../resources/user/user.model';
import { logger } from '../../resources/utilities/logger';

// See Hubspot API documentation for supporting info https://developers.hubspot.com/docs/api/crm

// Default service params
const apiKey = process.env.HUBSPOT_API_KEY;
const logCategory = 'Hubspot Integration';
const readEnv = process.env.NODE_ENV || 'prod';
let hubspotClient;
if (apiKey) hubspotClient = new Client({ apiKey, numberOfApiCallRetries: NumberOfRetries.Three });

/**
 * Sync A Single Gateway User With Hubspot
 *
 * @desc    Either creates a new or updates an existing contact in Hubspot using the gateway user email address for uniqueness
 * @param 	{Object} 	        gatewayUser 				User object containing bio details and contact subscription preferences
 */
const syncContact = async gatewayUser => {
	if (apiKey) {
		try {
			// Search for contact in Hubspot using email address in case they already exist as we need to merge subscription preferences rather than replace
			const response = await hubspotClient.crm.contacts.basicApi.getById(
				gatewayUser.email,
				['email', 'communication_preference'],
				[],
				false,
				'email'
			);
			if (response) {
				const { body: hubspotContact } = response;
				// When contact found, merge subscription preferences and perform update operation
				if (hubspotContact.id) {
					updateContact(hubspotContact, gatewayUser);
				}
			}
		} catch (err) {
			if (err.statusCode === 404) {
				// When contact not found, perform create operation
				createContact(gatewayUser);
			} else {
				logger.logError(err, logCategory);
			}
		}
	}
};

/**
 * Update A Hubspot Contact With Gateway User Details
 *
 * @desc    Updates a Hubspot contact record with corresponding Gateway user data using email address for unique match
 * @param 	{Object} 	        hubspotContact 				Contact object from Hubspot to be updated
 * @param 	{Object} 	        gatewayUser 				User object containing bio details and contact subscription preferences from Gateway
 */
const updateContact = async (hubspotContact, gatewayUser) => {
	if (apiKey) {
		try {
			// Extract and split hubspotContact communication preferences
			const { id, properties: { communication_preference = '' } = {} } = hubspotContact;
			const communicationPreferencesArr =
				isNull(communication_preference) || isEmpty(communication_preference.trim()) ? [] : communication_preference.trim().split(';');
			// Extract gateway user params
			const { news = false, feedback = false, orcid, sector, organisation, firstname, lastname } = gatewayUser;
			// Merge communication preferences keeping non-gateway related preferences and include any updates
			const updatedPreferencesArr = communicationPreferencesArr.filter(pref => {
				return (
					(pref === 'Gateway' && news) || (pref === 'Gateway Feedback' && feedback) || (pref !== 'Gateway' && pref !== 'Gateway Feedback')
				);
			});
			if (news && !updatedPreferencesArr.includes('Gateway')) updatedPreferencesArr.push('Gateway');
			if (feedback && !updatedPreferencesArr.includes('Gateway Feedback')) updatedPreferencesArr.push('Gateway Feedback');

			// Create update object
			const updatedContact = {
				properties: {
					firstname,
					lastname,
					orcid_number: orcid,
					related_organisation_sector: sector,
					company: organisation,
					communication_preference: updatedPreferencesArr.join(';'),
					gateway_registered_user: 'Yes',
				},
			};

			// Use API PUT operation to update the contact in Hubspot
			await hubspotClient.crm.contacts.basicApi.update(id, updatedContact);
			
		} catch (err) {
			logger.logError(err, logCategory);
		}
	}
};

/**
 * Create A Hubspot Contact With Gateway User Details
 *
 * @desc    Creates a new Hubspot contact record with corresponding Gateway user data using email address for unique match
 * @param 	{Object} 	        gatewayUser 				User object containing bio details and contact subscription preferences from Gateway
 */
const createContact = async gatewayUser => {
	if (apiKey) {
		try {
			// Extract gateway user params
			const { news = false, feedback = false, orcid, sector, organisation, firstname, lastname, email } = gatewayUser;
			const communicationPreferencesArr = [];
			if (news) communicationPreferencesArr.push('Gateway');
			if (feedback) communicationPreferencesArr.push('Gateway Feedback');

			// Create update object
			const newContact = {
				properties: {
					firstname,
					lastname,
					email,
					orcid_number: orcid,
					related_organisation_sector: sector,
					company: organisation,
					communication_preference: communicationPreferencesArr.join(';'),
					gateway_registered_user: 'Yes',
				},
			};
			// Use API POST operation to create the contact in Hubspot
			await hubspotClient.crm.contacts.basicApi.create(newContact);
		} catch (err) {
			logger.logError(err, logCategory);
		}
	}
};

/**
 * Sync Gateway Users With Hubspot
 *
 * @desc    Synchronises Gateway users with any changes reflected in Hubspot via external subscribe or unsubscribe methods e.g. mail link or sign up form sent via campaign
 */
const syncAllContacts = async () => {
	if (apiKey) {
		try {
			// Track attempted sync in Sentry using log
			if (readEnv === 'test' || readEnv === 'prod') {
				Sentry.addBreadcrumb({
					category: 'Hubspot',
					message: `Syncing Gateway users with Hubspot contacts`,
					level: Sentry.Severity.Log,
				});
			}

			// Batch import subscription changes from Hubspot
			await batchImportFromHubspot();

			// Batch update Hubspot with changes from Gateway
			await batchExportToHubspot();
		} catch (err) {
			logger.logError(err, logCategory);
		}
	}
};

/**
 * Trigger Hubspot Import To Update Changes Made Externally From Gateway
 *
 * @desc    Triggers an import of all contacts found to match a user record in the Gateway, updating where changes have been made
 */
const batchImportFromHubspot = async () => {
	if (apiKey) {
		let ops = [];
		// Get all contacts from Hubspot
		const contacts = await getAllContacts();
		// Create MongoDb bulk update from contacts
		contacts.forEach(contact => {
			const {
				properties: { communication_preference = '', email },
			} = contact;
			if (communication_preference && email) {
				const communicationPreferencesArr = isEmpty(communication_preference.trim()) ? [] : communication_preference.trim().split(';');
				const news = communicationPreferencesArr.includes('Gateway');
				const feedback = communicationPreferencesArr.includes('Gateway Feedback');
				ops.push({
					updateMany: {
						filter: { email },
						update: {
							news,
							feedback,
						},
						upsert: false,
					},
				});
			}
		});
		// Save to database
		await UserModel.bulkWrite(ops, err => {
			if (err) {
				logger.logError(err, logCategory);
			}
		});
	}
};

/**
 * Sync Hubspot Contact Details With User Profile Data From Gateway
 *
 * @desc    Updates Contact Details In Hubspot
 */
const batchExportToHubspot = async () => {
	if (apiKey) {
		// Get all users
		const users = await UserModel.find()
			.select('id email firstname lastname news feedback additionalInfo')
			.populate('additionalInfo')
			.lean();
		// Sync each user
		for (const user of users) {
			await syncContact({ ...user, ...user.additionalInfo });
		}
	}
};

/**
 * Gets All Hubspot Contacts
 *
 * @desc    Retrieves all contacts with associated communication preferences from Hubspot
 */
const getAllContacts = async () => {
	let contacts = [];
	let contactsResponse;
	let after;
	// Iterate through all pages of contacts using the 'after' functionality provided by Hubspot
	do {
		try {
			contactsResponse = await hubspotClient.crm.contacts.basicApi.getPage(100, after, ['email', 'communication_preference']);
			after = get(contactsResponse, 'body.paging.next.after');
			contacts.push(...contactsResponse.body.results);
		} catch (err) {
			logger.logError(err, logCategory);
		}
	} while (!isNil(after));

	return contacts;
};

const hubspotConnector = {
	syncContact,
	syncAllContacts,
};

export default hubspotConnector;
