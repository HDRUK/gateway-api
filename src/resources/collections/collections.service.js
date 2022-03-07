import { Data } from '../tool/data.model';
import { Course } from '../course/course.model';
import { Collections } from '../collections/collections.model';
import { UserModel } from '../user/user.model';
import { DataUseRegister } from '../dataUseRegister/dataUseRegister.model';
import emailGenerator from '../utilities/emailGenerator.util';
import inputSanitizer from '../utilities/inputSanitizer';
import _ from 'lodash';

export default class CollectionsService {
	constructor(collectionsRepository) {
		this.collectionsRepository = collectionsRepository;
		this.hdrukEmail = 'enquiry@healthdatagateway.org';
	}

	async getCollectionObjects(collectionID) {
		let relatedObjects = [];
		await this.collectionsRepository
			.getCollections(
				{ id: collectionID },
				{
					'relatedObjects._id': 1,
					'relatedObjects.objectId': 1,
					'relatedObjects.objectType': 1,
					'relatedObjects.pid': 1,
					'relatedObjects.updated': 1,
				}
			)
			.then(async res => {
				await new Promise(async (resolve, reject) => {
					if (_.isEmpty(res)) {
						reject(`Collection not found for ID: ${collectionID}.`);
					} else {
						for (let object of res[0].relatedObjects) {
							let relatedObject = await this.getCollectionObject(object.objectId, object.objectType, object.pid, object.updated);
							if (!_.isUndefined(relatedObject)) {
								relatedObjects.push(relatedObject);
							} else {
								await this.collectionsRepository.updateCollection({ id: collectionID }, { $pull: { relatedObjects: { _id: object._id } } });
							}
						}
						resolve(relatedObjects);
					}
				});
			});

		return relatedObjects.sort((a, b) => b.updated - a.updated);
	}

	getCollectionObject(objectId, objectType, pid, updated) {
		let id = pid && pid.length > 0 ? pid : objectId;

		return new Promise(async resolve => {
			let data;
			if (objectType !== 'dataset' && objectType !== 'course' && objectType !== 'dataUseRegister') {
				data = await Data.find(
					{ id: parseInt(id) },
					{
						id: 1,
						type: 1,
						activeflag: 1,
						tags: 1,
						description: 1,
						name: 1,
						persons: 1,
						categories: 1,
						programmingLanguage: 1,
						firstname: 1,
						lastname: 1,
						bio: 1,
						authors: 1,
						counter: { $ifNull: ['$counter', 0] },
						relatedresources: { $cond: { if: { $isArray: '$relatedObjects' }, then: { $size: '$relatedObjects' }, else: 0 } },
					}
				)
					.populate([{ path: 'persons', options: { select: { id: 1, firstname: 1, lastname: 1 } } }])
					.lean();
			} else if (!isNaN(id) && objectType === 'course') {
				data = await Course.find(
					{ id: parseInt(id) },
					{
						id: 1,
						type: 1,
						activeflag: 1,
						title: 1,
						provider: 1,
						courseOptions: 1,
						award: 1,
						domains: 1,
						tags: 1,
						description: 1,
						counter: { $ifNull: ['$counter', 0] },
						relatedresources: { $cond: { if: { $isArray: '$relatedObjects' }, then: { $size: '$relatedObjects' }, else: 0 } },
					}
				).lean();
			} else if (!isNaN(id) && objectType === 'dataUseRegister') {
				data = await DataUseRegister.find(
					{ id: parseInt(id) },
					{
						id: 1,
						type: 1,
						activeflag: 1,
						projectTitle: 1,
						organisationName: 1,
						keywords: 1,
						gatewayDatasets: 1,
						nonGatewayDatasets: 1,
						datasetTitles: 1,
						publisher: 1,
						counter: { $ifNull: ['$counter', 0] },
						relatedresources: { $cond: { if: { $isArray: '$relatedObjects' }, then: { $size: '$relatedObjects' }, else: 0 } },
					}
				)
					.populate([
						{ path: 'gatewayDatasetsInfo', select: { name: 1 } },
						{
							path: 'publisherInfo',
							select: { name: 1, _id: 0 },
						},
					])
					.lean();
			} else {
				const datasetRelatedResources = {
					$lookup: {
						from: 'tools',
						let: {
							pid: '$pid',
						},
						pipeline: [
							{ $unwind: '$relatedObjects' },
							{
								$match: {
									$expr: {
										$and: [
											{
												$eq: ['$relatedObjects.pid', '$$pid'],
											},
											{
												$eq: ['$activeflag', 'active'],
											},
										],
									},
								},
							},
							{ $group: { _id: null, count: { $sum: 1 } } },
						],
						as: 'relatedResourcesTools',
					},
				};

				const datasetRelatedCourses = {
					$lookup: {
						from: 'course',
						let: {
							pid: '$pid',
						},
						pipeline: [
							{ $unwind: '$relatedObjects' },
							{
								$match: {
									$expr: {
										$and: [
											{
												$eq: ['$relatedObjects.pid', '$$pid'],
											},
											{
												$eq: ['$activeflag', 'active'],
											},
										],
									},
								},
							},
							{ $group: { _id: null, count: { $sum: 1 } } },
						],
						as: 'relatedResourcesCourses',
					},
				};

				const datasetProjectFields = {
					$project: {
						id: 1,
						datasetid: 1,
						pid: 1,
						type: 1,
						activeflag: 1,
						name: 1,
						datasetv2: 1,
						datasetfields: 1,
						tags: 1,
						description: 1,
						counter: { $ifNull: ['$counter', 0] },
						relatedresources: {
							$add: [
								{
									$cond: {
										if: { $eq: [{ $size: '$relatedResourcesTools' }, 0] },
										then: 0,
										else: { $first: '$relatedResourcesTools.count' },
									},
								},
								{
									$cond: {
										if: { $eq: [{ $size: '$relatedResourcesCourses' }, 0] },
										then: 0,
										else: { $first: '$relatedResourcesCourses.count' },
									},
								},
							],
						},
					},
				};

				// 1. Search for a dataset based on pid
				data = await Data.aggregate([
					{ $match: { $and: [{ pid: id }, { activeflag: 'active' }] } },
					datasetRelatedResources,
					datasetRelatedCourses,
					datasetProjectFields,
				]);

				// 2. If dataset not found search for a dataset based on datasetID
				if (!data || data.length <= 0) {
					data = await Data.find({ datasetid: objectId }, { datasetid: 1, pid: 1 }).lean();
					// 3. Use retrieved dataset's pid to search by pid again
					data = await Data.aggregate([
						{ $match: { $and: [{ pid: data[0].pid }, { activeflag: 'active' }] } },
						datasetRelatedResources,
						datasetRelatedCourses,
						datasetProjectFields,
					]);
				}

				// 4. If dataset still not found search for deleted dataset by pid
				if (!data || data.length <= 0) {
					data = await Data.aggregate([
						{ $match: { $and: [{ pid: id }, { activeflag: 'archive' }] } },
						datasetRelatedResources,
						datasetRelatedCourses,
						datasetProjectFields,
					]);
				}
			}

			let relatedObject = { ...data[0], updated: Date.parse(updated) };
			resolve(relatedObject);
		});
	}

	getCollectionByEntity(entityID, dataVersionsArray) {
		var q = Collections.aggregate([
			{
				$match: {
					$and: [
						{
							relatedObjects: {
								$elemMatch: {
									$or: [
										{
											objectId: { $in: dataVersionsArray },
										},
										{
											pid: entityID,
										},
									],
								},
							},
						},
						{ publicflag: true },
						{ activeflag: 'active' },
					],
				},
			},
			{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
			{
				$project: {
					_id: 1,
					id: 1,
					name: 1,
					description: 1,
					imageLink: 1,
					relatedObjects: 1,
					'persons.firstname': 1,
					'persons.lastname': 1,
				},
			},
		]);

		return new Promise((resolve, reject) => {
			q.exec((err, data) => {
				if (err) {
					return reject(err);
				} else {
					return resolve(data);
				}
			});
		});
	}

	async editCollection(collectionID, updatedCollection) {
		let { name, description, imageLink, authors, relatedObjects, publicflag, keywords } = updatedCollection;
		let updatedon = Date.now();

		return new Promise(async (resolve, reject) => {
			await Collections.findOneAndUpdate(
				{ id: { $eq: collectionID } },
				{
					name: inputSanitizer.removeNonBreakingSpaces(name),
					description: inputSanitizer.removeNonBreakingSpaces(description),
					imageLink,
					authors,
					relatedObjects,
					publicflag,
					keywords,
					updatedon,
				},
				err => {
					err ? reject(err) : resolve();
				}
			);
		});
	}

	addCollection(collections) {
		return new Promise(async (resolve, reject) => {
			try {
				await collections.save();
				resolve();
			} catch (err) {
				reject({ success: false, error: err });
			}
		});
	}

	changeStatus(collectionID, activeflag) {
		return new Promise(async (resolve, reject) => {
			Collections.findOneAndUpdate({ id: collectionID }, { activeflag }, err => {
				err ? reject(err) : resolve();
			});
		});
	}

	deleteCollection(id) {
		return new Promise(async (resolve, reject) => {
			await Collections.findOneAndRemove({ id }, err => {
				err ? reject(err) : resolve();
			});
		});
	}

	async sendEmailNotifications(collections, activeflag, collectionCreator, isEdit) {
		// Generate URL for linking collection in email
		const collectionLink = process.env.GATEWAY_WEB_URL + '/collection/' + collections.id;

		// Query Db for all admins or authors of the collection
		var q = UserModel.aggregate([
			{ $match: { $or: [{ role: 'Admin' }, { id: { $in: collections.authors } }] } },
			{ $lookup: { from: 'tools', localField: 'id', foreignField: 'id', as: 'tool' } },
			{
				$project: {
					_id: 1,
					firstname: 1,
					lastname: 1,
					email: 1,
					role: 1,
					id: 1,
				},
			},
		]);

		// Use the returned array of email recipients to generate and send emails with SendGrid
		q.exec((err, emailRecipients) => {
			if (err) {
				return new Error({ success: false, error: err });
			} else {
				let subject;
				let html;

				emailRecipients.map(emailRecipient => {
					if (collections.authors.includes(emailRecipient.id)) {
						let author = Number(collections.authors.filter(author => author === emailRecipient.id));

						if (activeflag === 'active') {
							subject = this.generateCollectionEmailSubject(
								'Creator',
								collections.publicflag,
								collections.name,
								author === collectionCreator.id ? true : false,
								isEdit
							);
							html = this.generateCollectionEmailContent(
								'Creator',
								collections.publicflag,
								collections.name,
								collectionLink,
								author === collectionCreator.id ? true : false,
								isEdit
							);
						}
					} else if (activeflag === 'active' && emailRecipient.role === 'Admin') {
						subject = this.generateCollectionEmailSubject('Admin', collections.publicflag, collections.name, false, isEdit);
						html = this.generateCollectionEmailContent('Admin', collections.publicflag, collections.name, collectionLink, false, isEdit);
					}

					emailGenerator.sendEmail([emailRecipient], `${this.hdrukEmail}`, subject, html, false);
				});
			}
		});
	}

	generateCollectionEmailSubject(role, publicflag, collectionName, isCreator, isEdit) {
		let emailSubject;

		if (role !== 'Admin' && isCreator !== true) {
			if (isEdit === true) {
				emailSubject = `The ${
					publicflag === true ? 'public' : 'private'
				} collection ${collectionName} that you are a collaborator on has been edited and is now live`;
			} else {
				emailSubject = `You have been added as a collaborator on the ${
					publicflag === true ? 'public' : 'private'
				} collection ${collectionName}`;
			}
		} else {
			emailSubject = `${role === 'Admin' ? 'A' : 'Your'} ${
				publicflag === true ? 'public' : 'private'
			} collection ${collectionName} has been ${isEdit === true ? 'edited' : 'published'} and is now live`;
		}

		return emailSubject;
	}

	generateCollectionEmailContent(role, publicflag, collectionName, collectionLink, isCreator, isEdit) {
		return `<div>
				<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
					<table
					align="center"
					border="0"
					cellpadding="0"
					cellspacing="40"
					width="700"
					word-break="break-all"
					style="font-family: Arial, sans-serif">
						<thead>
							<tr>
								<th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
									${this.generateCollectionEmailSubject(role, publicflag, collectionName, isCreator, isEdit)}
								</th>
								</tr>
								<tr>
								<th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
									${
										publicflag === true
											? `${role === 'Admin' ? 'A' : 'Your'} public collection has been ${
													isEdit === true ? 'edited on' : 'published to'
											  } the Gateway. The collection is searchable on the Gateway and can be viewed by all users.`
											: `${role === 'Admin' ? 'A' : 'Your'} private collection has been ${
													isEdit === true ? 'edited on' : 'published to'
											  } the Gateway. Only those who you share the collection link with will be able to view the collection.`
									}
								</th>
							</tr>
						</thead>
						<tbody style="overflow-y: auto; overflow-x: hidden;">
							<tr style="width: 100%; text-align: left;">
								<td style=" font-size: 14px; color: #3c3c3b; padding: 5px 5px; width: 50%; text-align: left; vertical-align: top;">
									<a href=${collectionLink}>View Collection</a>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>`;
	}

	async getCollectionsAdmin(searchString, status, startIndex, limit) {
		return new Promise(async resolve => {
			let searchQuery;
			if (status === 'all') {
				searchQuery = {};
			} else {
				searchQuery = { $and: [{ activeflag: status }] };
			}

			let searchAll = false;

			if (searchString.length > 0) {
				searchQuery['$and'].push({ $text: { $search: searchString } });
			} else {
				searchAll = true;
			}

			await Promise.all([this.getObjectResult(searchAll, searchQuery, startIndex, limit), this.getCountsByStatus()]).then(values => {
				resolve(values);
			});
		});
	}

	async getCollections(idString, status, startIndex, limit) {
		return new Promise(async resolve => {
			let searchQuery;
			if (status === 'all') {
				searchQuery = [{ authors: parseInt(idString) }];
			} else {
				searchQuery = [{ authors: parseInt(idString) }, { activeflag: status }];
			}

			let query = Collections.aggregate([
				{ $match: { $and: searchQuery } },
				{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
				{ $sort: { updatedAt: -1, _id: 1 } },
			])
				.skip(parseInt(startIndex))
				.limit(parseInt(limit));

			await Promise.all([this.collectionsRepository.searchCollections(query), this.getCountsByStatus(idString)]).then(values => {
				resolve(values);
			});
		});
	}

	async getCollection(collectionID) {
		var q = Collections.aggregate([
			{ $match: { $and: [{ id: collectionID }] } },

			{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
		]);
		return new Promise((resolve, reject) => {
			q.exec((err, data) => {
				err ? reject(err) : resolve(data);
			});
		});
	}

	getObjectResult(searchAll, searchQuery, startIndex, limit) {
		let newSearchQuery = JSON.parse(JSON.stringify(searchQuery));
		let q = '';

		if (searchAll) {
			q = Collections.aggregate([
				{ $match: newSearchQuery },
				{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
			])
				.sort({ updatedAt: -1, _id: 1 })
				.skip(parseInt(startIndex))
				.limit(parseInt(limit));
		} else {
			q = Collections.aggregate([
				{ $match: newSearchQuery },
				{ $lookup: { from: 'tools', localField: 'authors', foreignField: 'id', as: 'persons' } },
			])
				.sort({ score: { $meta: 'textScore' } })
				.skip(parseInt(startIndex))
				.limit(parseInt(limit));
		}
		return this.collectionsRepository.searchCollections(q);
	}

	getCountsByStatus(idString) {
		let q;

		if (_.isUndefined(idString)) {
			q = Collections.find({}, { id: 1, name: 1, activeflag: 1 });
		} else {
			q = Collections.find({ authors: parseInt(idString) }, { id: 1, name: 1, activeflag: 1 });
		}

		return new Promise(resolve => {
			q.exec((err, data) => {
				const activeCount = data.filter(dat => dat.activeflag === 'active').length;
				const archiveCount = data.filter(dat => dat.activeflag === 'archive').length;

				let countSummary = { activeCount: activeCount, archiveCount: archiveCount };

				resolve(countSummary);
			});
		});
	}
}
