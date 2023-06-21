import { Course } from './course.model';
import { MessagesModel } from '../message/message.model';
import { UserModel } from '../user/user.model';
import { createDiscourseTopic } from '../discourse/discourse.service';
import emailGenerator from '../utilities/emailGenerator.util';
import helper from '../utilities/helper.util';
import { utils } from '../auth';
import { ROLES } from '../user/user.roles';
import { filtersService } from '../filters/dependency';
const hdrukEmail = `enquiry@healthdatagateway.org`;
const urlValidator = require('../utilities/urlValidator');
const inputSanitizer = require('../utilities/inputSanitizer');

export async function getObjectById(id) {
	return await Course.findOne({ id }).exec();
}

const addCourse = async req => {
	return new Promise(async (resolve, reject) => {
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
			req.body.courseOptions.forEach(x => {
				if (x.flexibleDates) x.startDate = null;
				x.studyMode = inputSanitizer.removeNonBreakingSpaces(x.studyMode);
				x.studyDurationMeasure = inputSanitizer.removeNonBreakingSpaces(x.studyDurationMeasure);
				if (x.fees) {
					x.fees.forEach(y => {
						y.feeDescription = inputSanitizer.removeNonBreakingSpaces(y.feeDescription);
						y.feePer = inputSanitizer.removeNonBreakingSpaces(y.feePer);
					});
				}
			});
		}
		course.courseOptions = req.body.courseOptions;

		if (req.body.entries) {
			req.body.entries.forEach(x => {
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
		if (!newCourse) reject(new Error(`Can't persist data object to DB.`));

		await createMessage(course.creator, course.id, course.title, course.type, 'add');
		await createMessage(0, course.id, course.title, course.type, 'add');
		// Send email notification of status update to admins and authors who have opted in
		await sendEmailNotifications(course, 'add');
		resolve(newCourse);
	});
};

const editCourse = async req => {
	return new Promise(async (resolve, reject) => {
		let id = req.params.id;

		if (req.body.entries) {
			req.body.entries.forEach(e => {
				e.level = inputSanitizer.removeNonBreakingSpaces(e.level);
				e.subject = inputSanitizer.removeNonBreakingSpaces(e.subject);
			});
		}

		if (req.body.courseOptions) {
			req.body.courseOptions.forEach(x => {
				if (x.flexibleDates) x.startDate = null;
				x.studyMode = inputSanitizer.removeNonBreakingSpaces(x.studyMode);
				x.studyDurationMeasure = inputSanitizer.removeNonBreakingSpaces(x.studyDurationMeasure);
				if (x.fees) {
					x.fees.forEach(y => {
						y.feeDescription = inputSanitizer.removeNonBreakingSpaces(y.feeDescription);
						y.feePer = inputSanitizer.removeNonBreakingSpaces(y.feePer);
					});
				}
			});
		}

		let relatedObjects = req.body.relatedObjects;
		let courseOptions = req.body.courseOptions;
		let entries = req.body.entries;
		let updatedon = Date.now();

		Course.findOneAndUpdate(
			{ id: { $eq: id } },
			{
				title: inputSanitizer.removeNonBreakingSpaces(req.body.title),
				link: urlValidator.validateURL(inputSanitizer.removeNonBreakingSpaces(req.body.link)),
				provider: inputSanitizer.removeNonBreakingSpaces(req.body.provider),
				description: inputSanitizer.removeNonBreakingSpaces(req.body.description),
				courseDelivery: inputSanitizer.removeNonBreakingSpaces(req.body.courseDelivery),
				location: inputSanitizer.removeNonBreakingSpaces(req.body.location),
				keywords: inputSanitizer.removeNonBreakingSpaces(req.body.keywords),
				domains: inputSanitizer.removeNonBreakingSpaces(req.body.domains),
				relatedObjects,
				courseOptions,
				entries,
				restrictions: inputSanitizer.removeNonBreakingSpaces(req.body.restrictions),
				award: inputSanitizer.removeNonBreakingSpaces(req.body.award),
				competencyFramework: inputSanitizer.removeNonBreakingSpaces(req.body.competencyFramework),
				nationalPriority: inputSanitizer.removeNonBreakingSpaces(req.body.nationalPriority),
				updatedon,
			},
			err => {
				if (err) {
					reject(new Error(`Failed to update.`));
				}
			}
		).then(async course => {
			if (course == null) {
				reject(new Error(`No record found with id of ${id}.`));
			}
			filtersService.optimiseFilters('course');

			await createMessage(course.creator, id, course.title, course.type, 'edit');
			await createMessage(0, id, course.title, course.type, 'edit');
			// Send email notification of status update to admins and authors who have opted in
			await sendEmailNotifications(course, 'edit');

			resolve(course);
		});
	});
};

const deleteCourse = async req => {
	return new Promise(async (resolve, reject) => {
		const { id } = req.params.id;
		Course.findOneAndDelete({ id: req.params.id }, err => {
			if (err) reject(err);
		}).then(course => {
			if (course == null) {
				reject(`No Content`);
			} else {
				resolve(id);
			}
		});
	});
};

const getAllCourses = async req => {
	return new Promise(async resolve => {
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
		if (req.query.q) {
			searchString = req.query.q || '';
		}

		let searchQuery = { $and: [{ type: 'course' }] };
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

const getCourseAdmin = async req => {
	return new Promise(async resolve => {
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
		if (req.query.q) {
			searchString = req.query.q || '';
		}
		if (req.query.status) {
			status = req.query.status;
		}

		let searchQuery;
		if (status === 'all') {
			searchQuery = { $and: [{ type: 'course' }] };
		} else {
			searchQuery = { $and: [{ type: 'course' }, { activeflag: status }] };
		}

		let searchAll = false;

		if (searchString.length > 0) {
			searchQuery['$and'].push({ $text: { $search: searchString } });
		} else {
			searchAll = true;
		}
		await Promise.all([getObjectResult(typeString, searchAll, searchQuery, startIndex, limit), getCountsByStatus()]).then(values => {
			resolve(values);
		});
	});
};

const getCourse = async req => {
	return new Promise(async resolve => {
		let startIndex = 0;
		let limit = 40;
		let idString = req.user.id;
		let status = 'all';

		if (req.query.offset) {
			startIndex = req.query.offset;
		}
		if (req.query.limit) {
			limit = req.query.limit;
		}
		if (req.query.id) {
			idString = req.query.id;
		}

		let searchQuery;
		if (status === 'all') {
			searchQuery = [{ type: 'course' }, { creator: parseInt(idString) }];
		} else {
			searchQuery = [{ type: 'course' }, { creator: parseInt(idString) }, { activeflag: status }];
		}

		let query = Course.aggregate([
			{ $match: { $and: searchQuery } },
			{ $lookup: { from: 'tools', localField: 'creator', foreignField: 'id', as: 'persons' } },
			{ $sort: { updatedAt: -1 } },
		])
			.skip(parseInt(startIndex))
			.limit(parseInt(limit));

		await Promise.all([getUserCourses(query), getCountsByStatusCreator(idString)]).then(values => {
			resolve(values);
		});

		function getUserCourses(query) {
			return new Promise((resolve, reject) => {
				query.exec((err, data) => {
					if (err) reject({ success: false, error: err });

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

const setStatus = async req => {
	return new Promise(async (resolve, reject) => {
		try {
			const { activeflag, rejectionReason } = req.body;
			const id = req.params.id;
			const userId = req.user.id;
			let course;

			if (utils.whatIsRole(req) === ROLES.Admin) {
				course = await Course.findOneAndUpdate({ id: id }, { $set: { activeflag: activeflag } });
				if (!course) {
					reject(new Error('Course not found'));
				}
			} else if (activeflag === 'archive') {
				course = await Course.findOneAndUpdate({ $and: [{ id: id }, { creator: userId }] }, { $set: { activeflag: activeflag } });
				if (!course) {
					reject(new Error('Course not found or user not authorised to change Course status'));
				}
			} else {
				reject(new Error('Not authorised to change the status of this Course'));
			}
			filtersService.optimiseFilters('course');

			await createMessage(course.creator, id, course.title, course.type, activeflag, rejectionReason);
			await createMessage(0, id, course.title, course.type, activeflag, rejectionReason);

			if (!course.discourseTopicId && course.activeflag === 'active') {
				await createDiscourseTopic(course);
			}

			// Send email notification of status update to admins and authors who have opted in
			await sendEmailNotifications(course, activeflag, rejectionReason);

			resolve(id);
		} catch (err) {
			process.stdout.write(`COURSE - setStatus : ${err.message}\n`);
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
	} else if (activeflag === 'add') {
		message.messageType = 'add';
		message.messageDescription = `Your ${toolType} ${toolName} has been submitted for approval`;
	} else if (activeflag === 'edit') {
		message.messageType = 'edit';
		message.messageDescription = `Your ${toolType} ${toolName} has been updated`;
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
	let adminCanUnsubscribe = true;
	// 1. Generate tool URL for linking user from email
	const toolLink = process.env.homeURL + '/' + tool.type + '/' + tool.id;
	let resourceType = tool.type.charAt(0).toUpperCase() + tool.type.slice(1);

	// 2. Build email subject
	if (activeflag === 'active') {
		subject = `${resourceType} ${tool.title} has been approved and is now live`;
	} else if (activeflag === 'archive') {
		subject = `${resourceType} ${tool.title} has been archived`;
	} else if (activeflag === 'rejected') {
		subject = `${resourceType} ${tool.title} has been rejected`;
	} else if (activeflag === 'add') {
		subject = `${resourceType} ${tool.title} has been submitted for approval`;
		adminCanUnsubscribe = false;
	} else if (activeflag === 'edit') {
		subject = `${resourceType} ${tool.title} has been updated`;
	}

	// Create object to pass through email data
	let options = {
		resourceType: tool.type,
		resourceName: tool.title,
		resourceLink: toolLink,
		subject,
		rejectionReason: rejectionReason,
		activeflag,
		type: 'author',
	};
	// Create email body content
	let html = emailGenerator.generateEntityNotification(options);

	if (adminCanUnsubscribe) {
		// 3. Find the creator of the course and admins if they have opted in to email updates
		var q = UserModel.aggregate([
			// Find the creator of the course and Admins
			{ $match: { $or: [{ role: 'Admin' }, { id: tool.creator }] } },
			// Perform lookup to check opt in/out flag in tools schema
			{ $lookup: { from: 'tools', localField: 'id', foreignField: 'id', as: 'tool' } },
			// Filter out any user who has opted out of email notifications
			{ $match: { 'tool.emailNotifications': true } },
			// Reduce response payload size to required fields
			{ $project: { _id: 1, firstname: 1, lastname: 1, email: 1, role: 1, 'tool.emailNotifications': 1 } },
		]);

		// 4. Use the returned array of email recipients to generate and send emails with SendGrid
		q.exec((err, emailRecipients) => {
			if (err) {
				return new Error({ success: false, error: err });
			}
			emailGenerator.sendEmail(emailRecipients, `${hdrukEmail}`, subject, html, false);
		});
	} else {
		// 3. Find the creator of the course if they have opted in to email updates
		var q = UserModel.aggregate([
			// Find all authors of this tool
			{ $match: { id: tool.creator } },
			// Perform lookup to check opt in/out flag in tools schema
			{ $lookup: { from: 'tools', localField: 'id', foreignField: 'id', as: 'tool' } },
			// Filter out any user who has opted out of email notifications
			{ $match: { 'tool.emailNotifications': true } },
			// Reduce response payload size to required fields
			{ $project: { _id: 1, firstname: 1, lastname: 1, email: 1, role: 1, 'tool.emailNotifications': 1 } },
		]);

		// 4. Use the returned array of email recipients to generate and send emails with SendGrid
		q.exec((err, emailRecipients) => {
			if (err) {
				return new Error({ success: false, error: err });
			}
			emailGenerator.sendEmail(emailRecipients, `${hdrukEmail}`, subject, html, false);
		});

		// 5. Find all admins regardless of email opt-in preference
		q = UserModel.aggregate([
			// Find all admins
			{ $match: { role: 'Admin' } },
			// Reduce response payload size to required fields
			{ $project: { _id: 1, firstname: 1, lastname: 1, email: 1, role: 1 } },
		]);

		// 6. Use the returned array of email recipients to generate and send emails with SendGrid
		q.exec((err, emailRecipients) => {
			if (err) {
				return new Error({ success: false, error: err });
			}

			// Create object to pass through email data
			options = {
				resourceType: tool.type,
				resourceName: tool.title,
				resourceLink: toolLink,
				subject,
				rejectionReason: rejectionReason,
				activeflag,
				type: 'admin',
			};

			html = emailGenerator.generateEntityNotification(options);

			emailGenerator.sendEmail(emailRecipients, `${hdrukEmail}`, subject, html, adminCanUnsubscribe);
		});
	}
}

function getObjectResult(type, searchAll, searchQuery, startIndex, limit) {
	let newSearchQuery = JSON.parse(JSON.stringify(searchQuery));
	let q = '';

	if (searchAll) {
		q = Course.aggregate([
			{ $match: newSearchQuery },
			{ $lookup: { from: 'tools', localField: 'creator', foreignField: 'id', as: 'persons' } },
			{ $lookup: { from: 'tools', localField: 'id', foreignField: 'authors', as: 'objects' } },
			{ $lookup: { from: 'reviews', localField: 'id', foreignField: 'toolID', as: 'reviews' } },
		])
			.sort({ updatedAt: -1, _id: 1 })
			.skip(parseInt(startIndex))
			.limit(parseInt(limit));
	} else {
		q = Course.aggregate([
			{ $match: newSearchQuery },
			{ $lookup: { from: 'tools', localField: 'creator', foreignField: 'id', as: 'persons' } },
			{ $lookup: { from: 'tools', localField: 'id', foreignField: 'authors', as: 'objects' } },
			{ $lookup: { from: 'reviews', localField: 'id', foreignField: 'toolID', as: 'reviews' } },
		])
			.sort({ score: { $meta: 'textScore' } })
			.skip(parseInt(startIndex))
			.limit(parseInt(limit));
	}
	return new Promise((resolve, reject) => {
		q.exec((err, data) => {
			if (typeof data === 'undefined') {
				resolve([]);
			} else {
				data.map(dat => {
					dat.persons = helper.hidePrivateProfileDetails(dat.persons);
				});
				resolve(data);
			}
		});
	});
}

function getCountsByStatus() {
	let q = Course.find({}, { id: 1, title: 1, activeflag: 1 });

	return new Promise(resolve => {
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

function getCountsByStatusCreator(idString) {
	let q = Course.find({ $and: [{ type: 'course' }, { creator: parseInt(idString) }] }, { id: 1, title: 1, activeflag: 1 });

	return new Promise(resolve => {
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

export { addCourse, editCourse, deleteCourse, setStatus, getCourse, getCourseAdmin, getAllCourses };
