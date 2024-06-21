import { Data } from './data.model';
import { cloneDeep } from 'lodash';
import { MessagesModel } from '../message/message.model';
import { UserModel } from '../user/user.model';
import { createDiscourseTopic } from '../discourse/discourse.service';
import emailGenerator from '../utilities/emailGenerator.util';
import helper from '../utilities/helper.util';
const asyncModule = require('async');
import { filtersService } from '../filters/dependency';
import { utils } from '../auth';
import { ROLES } from '../user/user.roles';
const hdrukEmail = `enquiry@healthdatagateway.org`;
const urlValidator = require('../utilities/urlValidator');
const inputSanitizer = require('../utilities/inputSanitizer');

export async function getObjectById(id) {
	return await Data.findOne({ id }).exec();
}

const addTool = async (req, res) => {
	return new Promise(async (resolve, reject) => {
		let data = new Data();
		const toolCreator = req.body.toolCreator;
		const {
			type,
			name,
			link,
			description,
			resultsInsights,
			categories,
			license,
			authors,
			authorsNew,
			leadResearcher,
			tags,
			journal,
			journalYear,
			relatedObjects,
			programmingLanguage,
			isPreprint,
			document_links,
		} = req.body;
		data.id = parseInt(Math.random().toString().replace('0.', ''));
		data.type = inputSanitizer.removeNonBreakingSpaces(type);
		data.name = inputSanitizer.removeNonBreakingSpaces(name);
		data.link = urlValidator.validateURL(inputSanitizer.removeNonBreakingSpaces(link));
		data.authorsNew = inputSanitizer.removeNonBreakingSpaces(authorsNew);
		data.leadResearcher = inputSanitizer.removeNonBreakingSpaces(leadResearcher);
		data.journal = inputSanitizer.removeNonBreakingSpaces(journal);
		data.journalYear = inputSanitizer.removeNonBreakingSpaces(journalYear);
		data.description = inputSanitizer.removeNonBreakingSpaces(description);
		data.resultsInsights = inputSanitizer.removeNonBreakingSpaces(resultsInsights);

		if (categories && typeof categories !== 'undefined')
			data.categories.category = inputSanitizer.removeNonBreakingSpaces(categories.category);
		data.license = inputSanitizer.removeNonBreakingSpaces(license);
		data.authors = authors;
		(data.tags.features = inputSanitizer.removeNonBreakingSpaces(tags.features)),
			(data.tags.topics = inputSanitizer.removeNonBreakingSpaces(tags.topics));
		data.activeflag = 'review';
		data.updatedon = Date.now();
		data.relatedObjects = relatedObjects;

		if (programmingLanguage) {
			programmingLanguage.forEach(p => {
				p.programmingLanguage = inputSanitizer.removeNonBreakingSpaces(p.programmingLanguage);
				p.version = inputSanitizer.removeNonBreakingSpaces(p.version);
			});
		}
		data.programmingLanguage = programmingLanguage;

		data.isPreprint = isPreprint;
		data.uploader = req.user.id;

		data.document_links = validateDocumentLinks(document_links);

		let newDataObj = await data.save();
		if (!newDataObj) reject(new Error(`Can't persist data object to DB.`));

		let message = new MessagesModel();
		message.messageID = parseInt(Math.random().toString().replace('0.', ''));
		message.messageTo = 0;
		message.messageObjectID = data.id;
		message.messageType = 'add';
		message.messageDescription = `Approval needed: new ${data.type} added ${name}`;
		message.messageSent = Date.now();
		message.isRead = false;
		let newMessageObj = await message.save();
		if (!newMessageObj) reject(new Error(`Can't persist message to DB.`));

		// 1. Generate URL for linking tool from email
		const toolLink = process.env.homeURL + '/' + data.type + '/' + data.id;

		// 2. Query Db for all admins who have opted in to email updates
		var q = UserModel.aggregate([
			// Find all users who are admins
			{ $match: { role: 'Admin' } },
			// Reduce response payload size to required fields
			{ $project: { _id: 1, firstname: 1, lastname: 1, email: 1, role: 1 } },
		]);

		// 3. Use the returned array of email recipients to generate and send emails with SendGrid
		q.exec((err, emailRecipients) => {
			if (err) {
				return new Error({ success: false, error: err });
			}

			// Create object to pass through email data
			let options = {
				resourceType: data.type,
				resourceName: data.name,
				resourceLink: toolLink,
				type: 'admin',
			};
			// Create email body content
			let html = emailGenerator.generateEntityNotification(options);

			// Send email
			emailGenerator.sendEmail(emailRecipients, `${hdrukEmail}`, `A new ${data.type} has been added and is ready for review`, html, false);
		});

		await sendEmailNotificationToAuthors(data, toolCreator);
		await storeNotificationsForAuthors(data, toolCreator);

		resolve(newDataObj);
	});
};

const editTool = async (req, res) => {
	return new Promise(async (resolve, reject) => {
		const toolCreator = req.body.toolCreator;
		let {
			type,
			name,
			link,
			description,
			resultsInsights,
			categories,
			license,
			authors,
			authorsNew,
			leadResearcher,
			tags,
			journal,
			journalYear,
			relatedObjects,
			isPreprint,
			document_links,
		} = req.body;
		let id = req.params.id;
		let programmingLanguage = req.body.programmingLanguage;
		let updatedon = Date.now();

		if (!categories || typeof categories === 'undefined')
			categories = { category: '', programmingLanguage: [], programmingLanguageVersion: '' };

		if (programmingLanguage) {
			programmingLanguage.forEach(p => {
				p.programmingLanguage = inputSanitizer.removeNonBreakingSpaces(p.programmingLanguage);
				p.version = inputSanitizer.removeNonBreakingSpaces(p.version);
			});
		}

		let documentLinksValidated = validateDocumentLinks(document_links);

		let data = {
			id: id,
			name: name,
			authors: authors,
			type: type,
		};

		Data.findOneAndUpdate(
			{ id: { $eq: id } },
			{
				type: inputSanitizer.removeNonBreakingSpaces(type),
				name: inputSanitizer.removeNonBreakingSpaces(name),
				link: urlValidator.validateURL(inputSanitizer.removeNonBreakingSpaces(link)),
				description: inputSanitizer.removeNonBreakingSpaces(description),
				resultsInsights: inputSanitizer.removeNonBreakingSpaces(resultsInsights),
				authorsNew: inputSanitizer.removeNonBreakingSpaces(authorsNew),
				leadResearcher: inputSanitizer.removeNonBreakingSpaces(leadResearcher),
				journal: inputSanitizer.removeNonBreakingSpaces(journal),
				journalYear: inputSanitizer.removeNonBreakingSpaces(journalYear),
				categories: {
					category: inputSanitizer.removeNonBreakingSpaces(categories.category),
					programmingLanguage: categories.programmingLanguage,
					programmingLanguageVersion: categories.programmingLanguageVersion,
				},
				license: inputSanitizer.removeNonBreakingSpaces(license),
				authors,
				programmingLanguage,
				tags: {
					features: inputSanitizer.removeNonBreakingSpaces(tags.features),
					topics: inputSanitizer.removeNonBreakingSpaces(tags.topics),
				},
				relatedObjects,
				isPreprint,
				document_links: documentLinksValidated,
				updatedon,
			},
			err => {
				if (err) {
					reject(new Error(`Failed to update.`));
				}
			}
		).then(tool => {
			if (tool == null) {
				reject(new Error(`No record found with id of ${id}.`));
			} else {
				filtersService.optimiseFilters(tool.type);
				// Send email notification of update to all authors who have opted in to updates
				sendEmailNotificationToAuthors(data, toolCreator);
				storeNotificationsForAuthors(data, toolCreator);
			}
			resolve(tool);
		});
	});
};

const deleteTool = async (req, res) => {
	return new Promise(async (resolve, reject) => {
		const { id } = req.params.id;
		Data.findOneAndDelete({ id: req.params.id }, err => {
			if (err) reject(err);
		}).then(tool => {
			if (tool == null) {
				reject(`No Content`);
			} else {
				resolve(id);
			}
		});
	});
};

const getAllTools = async (req, res) => {
	return new Promise(async (resolve, reject) => {
		let startIndex = 0;
		let limit = 1000;
		let typeString = '';
		let searchString = '';

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
			searchString = req.query.q || '';
		}

		let searchQuery = { $and: [{ type: typeString }] };
		let searchAll = false;

		if (searchString.length > 0) {
			searchQuery['$and'].push({ $text: { $search: searchString } });
		} else {
			searchAll = true;
		}
		await Promise.all([getObjectResult(typeString, searchAll, searchQuery, startIndex, limit)]).then(values => {
			resolve(values[0]);
		});
	});
};

const getToolsAdmin = async (req, res) => {
	return new Promise(async (resolve, reject) => {
		let startIndex = 0;
		let limit = 40;
		let typeString = '';
		let searchString = '';
		let status = 'all';

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
			searchString = req.query.q || '';
		}
		if (req.query.status) {
			status = req.query.status;
		}

		let searchQuery;
		if (status === 'all') {
			searchQuery = { $and: [{ type: typeString }] };
		} else {
			searchQuery = { $and: [{ type: typeString }, { activeflag: status }] };
		}

		let searchAll = false;

		if (searchString.length > 0) {
			searchQuery['$and'].push({ $text: { $search: searchString } });
		} else {
			searchAll = true;
		}

		await Promise.all([getObjectResult(typeString, searchAll, searchQuery, startIndex, limit), getCountsByStatus(typeString)]).then(
			values => {
				resolve(values);
			}
		);
	});
};

const getTools = async (req, res) => {
	return new Promise(async (resolve, reject) => {
		let startIndex = 0;
		let limit = 40;
		let typeString = '';
		let idString = req.user.id;
		let status = 'all';

		if (req.query.offset) {
			startIndex = req.query.offset;
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
		if (req.query.status) {
			status = req.query.status;
		}

		let searchQuery;
		if (status === 'all') {
			searchQuery = [{ type: typeString }, { authors: parseInt(idString) }];
		} else {
			searchQuery = [{ type: typeString }, { authors: parseInt(idString) }, { activeflag: status }];
		}

		let query = Data.aggregate([
			{ $match: { $and: searchQuery } },
			{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
			{ $sort: { updatedAt: -1 } },
		])
			.skip(parseInt(startIndex))
			.limit(parseInt(limit));

		await Promise.all([getUserTools(query), getCountsByStatusCreator(typeString, idString)]).then(values => {
			resolve(values);
		});

		function getUserTools(query) {
			return new Promise((resolve, reject) => {
				query.exec((err, data) => {
					data &&
						data.map(dat => {
							dat.persons = helper.hidePrivateProfileDetails(dat.persons);
						});
					if (typeof data === 'undefined') resolve([]);
					else resolve(data);
				});
			});
		}
	});
};

const setStatus = async (req, res) => {
	return new Promise(async (resolve, reject) => {
		try {
			const { activeflag, rejectionReason } = req.body;
			const id = req.params.id;
			const userId = req.user.id;
			let tool;

			if (utils.whatIsRole(req) === ROLES.Admin) {
				tool = await Data.findOneAndUpdate({ id: id }, { $set: { activeflag: activeflag } });
				if (!tool) {
					reject(new Error('Tool not found'));
				}
			} else if (activeflag === 'archive') {
				tool = await Data.findOneAndUpdate({ $and: [{ id: id }, { authors: userId }] }, { $set: { activeflag: activeflag } });
				if (!tool) {
					reject(new Error('Tool not found or user not authorised to change Tool status'));
				}
			} else {
				reject(new Error('Not authorised to change the status of this Tool'));
			}

			if (tool.authors) {
				tool.authors.forEach(async authorId => {
					await createMessage(authorId, id, tool.name, tool.type, activeflag, rejectionReason);
				});
			}
			await createMessage(0, id, tool.name, tool.type, activeflag, rejectionReason);

			if (!tool.discourseTopicId && tool.activeflag === 'active') {
				await createDiscourseTopic(tool);
			}

			filtersService.optimiseFilters(tool.type);
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
		message.messageDescription = `Your ${toolType} ${toolName} has been approved and is now live ${toolLink}`;
	} else if (activeflag === 'archive') {
		message.messageType = 'archive';
		message.messageDescription = `Your ${toolType} ${toolName} has been archived ${toolLink}`;
	} else if (activeflag === 'rejected') {
		message.messageType = 'rejected';
		message.messageDescription = `Your ${toolType} ${toolName} has been rejected ${toolLink}`;
		message.messageDescription = rejectionReason
			? message.messageDescription.concat(` Rejection reason: ${rejectionReason}`)
			: message.messageDescription;
	}
	message.messageID = parseInt(Math.random().toString().replace('0.', ''));
	message.messageTo = authorId;
	message.messageObjectID = toolId;
	message.messageSent = Date.now();
	message.isRead = false;
	await message.save();
}

async function sendEmailNotifications(tool, activeflag, rejectionReason) {
	// 1. Generate tool URL for linking user from email
	const toolLink = process.env.homeURL + '/' + tool.type + '/' + tool.id;
	let resourceType = tool.type.charAt(0).toUpperCase() + tool.type.slice(1);

	// 2. Build email subject
	let subject;
	if (activeflag === 'active') {
		subject = `${resourceType} ${tool.name} has been approved and is now live`;
	} else if (activeflag === 'archive') {
		subject = `${resourceType} ${tool.name} has been archived`;
	} else if (activeflag === 'rejected') {
		subject = `${resourceType} ${tool.name} has been rejected`;
	}

	// 3. Create object to pass through email data
	let options = {
		resourceType: tool.type,
		resourceName: tool.name,
		resourceLink: toolLink,
		subject,
		rejectionReason: rejectionReason,
		activeflag,
		type: 'author',
	};
	// 4. Create email body content
	let html = emailGenerator.generateEntityNotification(options);

	// 5. Find all authors of the tool who have opted in to email updates
	var q = UserModel.aggregate([
		// Find all authors of this tool
		{ $match: { $or: [{ role: 'Admin' }, { id: { $in: tool.authors } }] } },
		// Perform lookup to check opt in/out flag in tools schema
		{ $lookup: { from: 'tools', localField: 'id', foreignField: 'id', as: 'tool' } },
		// Filter out any user who has opted out of email notifications
		{ $match: { 'tool.emailNotifications': true } },
		// Reduce response payload size to required fields
		{ $project: { _id: 1, firstname: 1, lastname: 1, email: 1, role: 1, 'tool.emailNotifications': 1 } },
	]);

	// 6. Use the returned array of email recipients to generate and send emails with SendGrid
	q.exec((err, emailRecipients) => {
		if (err) {
			return new Error({ success: false, error: err });
		}

		emailGenerator.sendEmail(emailRecipients, `${hdrukEmail}`, subject, html, false);
	});
}

async function sendEmailNotificationToAuthors(tool, toolOwner) {
	// 1. Generate tool URL for linking user from email
	const toolLink = process.env.homeURL + `/${tool.type}/` + tool.id;

	// 2. Find all authors of the tool who have opted in to email updates
	var q = UserModel.aggregate([
		// Find all authors of this tool
		{ $match: { id: { $in: tool.authors } } },
		// Perform lookup to check opt in/out flag in tools schema
		{ $lookup: { from: 'tools', localField: 'id', foreignField: 'id', as: 'tool' } },
		// Filter out any user who has opted out of email notifications
		{ $match: { 'tool.emailNotifications': true } },
		// Reduce response payload size to required fields
		{ $project: { _id: 1, firstname: 1, lastname: 1, email: 1, role: 1, 'tool.emailNotifications': 1 } },
	]);

	// 3. Create object to pass through email data
	let options = {
		resourceType: tool.type,
		resourceName: tool.name,
		resourceLink: toolLink,
		type: 'co-author',
		resourceAuthor: toolOwner.name,
	};
	// 4. Create email body content
	let html = emailGenerator.generateEntityNotification(options);

	// 5. Use the returned array of email recipients to generate and send emails with SendGrid
	q.exec((err, emailRecipients) => {
		if (err) {
			return new Error({ success: false, error: err });
		}
		emailGenerator.sendEmail(
			emailRecipients,
			`${hdrukEmail}`,
			`${toolOwner.name} added you as an author of the ${tool.type} ${tool.name}`,
			html,
			false
		);
	});
}

async function storeNotificationsForAuthors(tool, toolOwner) {
	// clone deep the object tool take a deep clone of properties
	let toolCopy = cloneDeep(tool);

	toolCopy.authors.push(0);

	asyncModule.eachSeries(toolCopy.authors, async author => {
		let message = new MessagesModel();
		message.messageType = 'author';
		message.messageSent = Date.now();
		message.messageDescription = `${toolOwner.name} added you as an author of the ${toolCopy.type} ${toolCopy.name}`;
		message.isRead = false;
		message.messageObjectID = toolCopy.id;
		message.messageID = parseInt(Math.random().toString().replace('0.', ''));
		message.messageTo = author;

		await message.save(async err => {
			if (err) {
				return new Error({ success: false, error: err });
			}
			return { success: true, id: message.messageID };
		});
	});
}

function getObjectResult(type, searchAll, searchQuery, startIndex, limit) {
	let newSearchQuery = JSON.parse(JSON.stringify(searchQuery));
	let q = '';

	if (searchAll) {
		q = Data.aggregate([
			{ $match: newSearchQuery },
			{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
			{ $lookup: { from: 'tools', localField: 'id', foreignField: 'authors', as: 'objects' } },
			{ $lookup: { from: 'reviews', localField: 'id', foreignField: 'toolID', as: 'reviews' } },
		])
			.sort({ updatedAt: -1, _id: 1 })
			.skip(parseInt(startIndex))
			.limit(parseInt(limit));
	} else {
		q = Data.aggregate([
			{ $match: newSearchQuery },
			{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
			{ $lookup: { from: 'tools', localField: 'id', foreignField: 'authors', as: 'objects' } },
			{ $lookup: { from: 'reviews', localField: 'id', foreignField: 'toolID', as: 'reviews' } },
		])
			.sort({ score: { $meta: 'textScore' } })
			.skip(parseInt(startIndex))
			.limit(parseInt(limit));
	}
	return new Promise((resolve, reject) => {
		q.exec((err, data) => {
			data.map(dat => {
				dat.persons = helper.hidePrivateProfileDetails(dat.persons);
			});
			if (typeof data === 'undefined') resolve([]);
			else resolve(data);
		});
	});
}

function getCountsByStatus(type) {
	let q = Data.find({ type: type }, { id: 1, name: 1, activeflag: 1 });

	return new Promise((resolve, reject) => {
		q.exec((err, data) => {
			const activeCount = data.filter(dat => dat.activeflag === 'active').length;
			const reviewCount = data.filter(dat => dat.activeflag === 'review').length;
			const rejectedCount = data.filter(dat => dat.activeflag === 'rejected').length;
			const archiveCount = data.filter(dat => dat.activeflag === 'archive').length;

			let countSummary = { activeCount: activeCount, reviewCount: reviewCount, rejectedCount: rejectedCount, archiveCount: archiveCount };

			resolve(countSummary);
		});
	});
}

function getCountsByStatusCreator(type, idString) {
	let q = Data.find({ $and: [{ type: type }, { authors: parseInt(idString) }] }, { id: 1, name: 1, activeflag: 1 });

	return new Promise((resolve, reject) => {
		q.exec((err, data) => {
			const activeCount = data.filter(dat => dat.activeflag === 'active').length;
			const reviewCount = data.filter(dat => dat.activeflag === 'review').length;
			const rejectedCount = data.filter(dat => dat.activeflag === 'rejected').length;
			const archiveCount = data.filter(dat => dat.activeflag === 'archive').length;

			let countSummary = { activeCount: activeCount, reviewCount: reviewCount, rejectedCount: rejectedCount, archiveCount: archiveCount };

			resolve(countSummary);
		});
	});
}

function validateDocumentLinks(document_links) {
	let documentLinksValidated = { doi: [], pdf: [], html: [] };
	if (document_links) {
		document_links.doi.forEach(url => {
			if (urlValidator.isDOILink(url)) {
				documentLinksValidated.doi.push(urlValidator.validateURL(url));
			} else {
				documentLinksValidated.html.push(urlValidator.validateURL(url));
			}
		});
		document_links.pdf.forEach(url => {
			if (urlValidator.isDOILink(url)) {
				documentLinksValidated.doi.push(urlValidator.validateURL(url));
			} else {
				documentLinksValidated.pdf.push(urlValidator.validateURL(url));
			}
		});
		document_links.html.forEach(url => {
			if (urlValidator.isDOILink(url)) {
				documentLinksValidated.doi.push(urlValidator.validateURL(url));
			} else {
				documentLinksValidated.html.push(urlValidator.validateURL(url));
			}
		});
	}
	return documentLinksValidated;
}

function formatRetroDocumentLinks(document_links) {
	let documentLinksValidated = { doi: [], pdf: [], html: [] };

	document_links.forEach(obj => {
		for (const [key, value] of Object.entries(obj)) {
			switch (key) {
				case 'doi':
					documentLinksValidated.doi.push(value);
					break;
				case 'pdf':
					documentLinksValidated.pdf.push(value);
					break;
				case 'html':
					documentLinksValidated.html.push(value);
					break;
				default:
					break;
			}
		}
	});

	return documentLinksValidated;
}

export { addTool, editTool, deleteTool, setStatus, getTools, getToolsAdmin, getAllTools, formatRetroDocumentLinks };
