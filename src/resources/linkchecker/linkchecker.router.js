import express from 'express';
import { getObjectResult } from './linkchecker.repository';
import { getUserByUserId } from '../user/user.repository';
import { Data } from '../tool/data.model';
import emailGenerator from '../utilities/emailGenerator.util';
import _ from 'lodash';
const sgMail = require('@sendgrid/mail');

const hdrukEmail = `enquiry@healthdatagateway.org`;

const axios = require('axios');
const router = express.Router();

router.post('/', async (req, res) => {
	let parsedBody = {};
	if (req.header('content-type') === 'application/json') {
		parsedBody = req.body;
	} else {
		parsedBody = JSON.parse(req.body);
	}

	//Check for key
	if (parsedBody.key !== process.env.linkcheckerkey) {
		return res.json({ success: false, error: 'Link checker failed' });
	}

	let results = [];

	const allowedKeys = ['link', 'description', 'resultsInsights'];

	results = await getObjectResult(true, { $and: [{ activeflag: 'active' }] });

	const getAllUsers = persons =>
		new Promise(async resolve => {
			let users = [];
			for (let p of persons) {
				let user = await getUserByUserId(p.id);
				if (!_.isEmpty(user)) {
					users.push({
						_id: user._id,
						id: user.id,
						firstname: user.firstname,
						lastname: user.lastname,
						email: user.email,
					});
				}
			}
			// at end resolve the request
			resolve(users);
		});

	const getErrorLink = link =>
		new Promise(async resolve => {
			try {
				await axios.get(link);
				resolve('');
			} catch (error) {
				resolve('error');
			}
		});

	const checkLinks = (item, key) =>
		new Promise(async resolve => {
			let errors = {};
			let linkErrors = [];
			if (allowedKeys.includes(key)) {
				// return [url, url];
				let links = item[key].match(/\bhttps?:\/\/\S+/gi);
				// test links for errors
				if (!_.isEmpty(links)) {
					for (let link of links) {
						// test our link is valid or not
						let result = (await getErrorLink(link)) || '';
						// check to see if it contains a string with a value
						if (!_.isEmpty(result)) {
							linkErrors.push(link);
						}
					}

					if (!_.isEmpty(linkErrors)) {
						// we return errros: { link: [url, url, url]}
						errors[key] = linkErrors;
					}
				}
			}
			//returns after processing our await via new promise
			resolve(errors);
		});

	const sendEmailToUsers = async (users, errors, item) => {
		let footer;
		sgMail.setApiKey(process.env.SENDGRID_API_KEY);
		let resourceLink = process.env.homeURL + '/' + item.type + '/' + item.id;

		for (let user of users) {
			footer = emailGenerator.generateEmailFooter(user, 'true');

			let checkUser = await Data.find({
				id: user.id,
			});

			if (checkUser[0].emailNotifications === true) {
				let msg = {
					to: user.email,
					from: hdrukEmail,
					subject: `Updates required for links in ${item.name}.`,
					html: `${user.firstname} ${user.lastname}, <br /><br />
                           Please review your ${item.type} "${item.name}"  here: ${resourceLink}. This ${item.type} contains stale links which require updating.
                           <br /> <br /> ${footer}`,
				};

				await sgMail.send(msg, false, err => {
					process.stdout.write(`LINKCHECKER - sendEmailToUsers: error`);
				});
			}
		}
	};

	results.map(async item => {
		let errors = {};
		// 1. deconstruct the item and select persons [1,2,4,5,6]
		let { persons } = { ...item };

		let users = [];
		// 1. users = [{id, firstname, lastname, email}, {}, {}];
		if (!_.isEmpty(persons)) {
			users = await getAllUsers(persons);
		} else {
			users = [{ email: 'support@healthdatagateway.org', firstname: 'HDRUK', lastname: 'Support' }];
		}

		// loop over the item object and check each key meets link checking
		for (let key in item) {
			// error: {link: [url, url]}
			let result = (await checkLinks(item, key)) || {};
			// link doing result.link
			if (!_.isEmpty(result)) {
				errors = {
					...errors,
					[key]: result[key],
				};
			}
		}

		// send email to all users
		// loop over the users async await and send email here
		if (!_.isEmpty(errors)) {
			await sendEmailToUsers(users, errors, item).then(() => {
				return res.json({ success: true });
			});
		}
	});
});

module.exports = router;
