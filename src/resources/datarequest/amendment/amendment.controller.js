import { DataRequestModel } from '../datarequest.model';
import { AmendmentModel } from './amendment.model';
import constants from '../../utilities/constants.util';
import helperUtil from '../../utilities/helper.util';
import _ from 'lodash';

const teamController = require('../../team/team.controller');

//POST api/v1/data-access-request/:id/amendments
const setAmendment = async (req, res) => {
	try {
		// 1. Get the required request params
		const {
			params: { id },
		} = req;
		let { _id: userId } = req.user;
		let {
			questionId = '',
			questionSetId = '',
			mode = '',
			reason = '',
			answer = '',
		} = req.body;
		if (_.isEmpty(questionId) || _.isEmpty(questionSetId)) {
			return res.status(400).json({
				success: false,
				message:
					'You must supply the unique identifiers for the question requiring amendment',
			});
		}
		// 2. Retrieve DAR from database
		let accessRecord = await DataRequestModel.findOne({ _id: id })
			.select('publisher amendmentIterations')
			.populate({
				path: 'publisherObj',
				select: '_id',
				populate: {
					path: 'team',
				},
			});
		if (!accessRecord) {
			return res
				.status(404)
				.json({ status: 'error', message: 'Application not found.' });
		}
		// 3. Check permissions of user is manager of associated team
		let authorised = false;
		let {
			publisherObj: { team },
		} = accessRecord;
		authorised = teamController.checkTeamPermissions(
			constants.roleTypes.REVIEWER,
			team.toObject(),
			userId
		);
		// 4. Refuse access if not authorised
		if (!authorised) {
			return res
				.status(401)
				.json({ status: 'failure', message: 'Unauthorised' });
		}
		// 5. Ensure the current iteration is not being modified by applicants
		if (
			getAmendmentIterationParty(accessRecord) === constants.userTypes.APPLICANT
		) {
			return res.status(400).json({
				status: 'failure',
				message:
					'You cannot request amendments to this application as the applicant(s) are currently editing the submission.',
			});
		}
		// 6. Add or remove amendment depending on mode
		switch (mode) {
			case constants.amendmentModes.ADDED:
				addAmendment(
					accessRecord,
					questionId,
					questionSetId,
					answer,
					reason,
					req.user,
					true
				);
				break;
			case constants.amendmentModes.REMOVED:
				removeAmendment(accessRecord, questionId);
				break;
		}
		// 7. Save changes to database
		await accessRecord.save(async (err) => {
			if (err) {
				console.error(err);
				res.status(500).json({ status: 'error', message: err });
			} else {
				return res.status(200).json({
					success: true,
				});
			}
		});
	} catch (err) {
		console.error(err.message);
		return res.status(500).json({
			success: false,
			message: 'An error occurred updating the application amendment',
		});
	}
};

const addAmendment = (
	accessRecord,
	questionId,
	questionSetId,
	answer,
	reason,
	user,
	requested
) => {
	// 1. Create new amendment object with key representing the questionId
	let amendment = {
		[`${questionId}`]: new AmendmentModel({
			questionSetId,
			requested,
			reason,
			answer,
			requestedBy: requested ? `${user.firstname} ${user.lastname}` : '',
			requestedByUser: requested ? user._id : '',
			dateRequested: requested ? Date.now() : '',
			updatedBy: requested ? '' : `${user.firstname} ${user.lastname}`,
			updatedByUser: requested ? '' : user._id,
			dateUpdated: requested ? '' : Date.now(),
		}),
	};
	// 2. Find the index of the latest amendment iteration of the DAR
	let index = getLatestAmendmentIterationIndex(accessRecord);
	// 3. If index is not -1, we need to append the new amendment to existing iteration object otherwise create a new one
	if (index !== -1) {
		accessRecord.amendmentIterations[index].questionAnswers = {
			...accessRecord.amendmentIterations[index].questionAnswers,
			...amendment,
		};
	} else {
		// 4. If new iteration has been trigger by applicant given requested is false, then we automatically return the iteration
		let amendmentIteration = {
			dateReturned: requested ? '' : Date.now(),
			returnedBy: requested ? '' : user._id,
			dateCreated: Date.now(),
			createdBy: user._id,
			questionAnswers: { ...amendment },
		};
		accessRecord.amendmentIterations = [
			...accessRecord.amendmentIterations,
			amendmentIteration,
		];
	}
};

const updateAmendment = (accessRecord, questionId, answer, user) => {
	// 1. Locate amendment in current iteration
	const currentIterationIndex = getLatestAmendmentIterationIndex(accessRecord);
	// 2. Return unmoodified record if invalid update
	if (
		currentIterationIndex === -1 ||
		_.isNil(
			accessRecord.amendmentIterations[currentIterationIndex].questionAnswers[
				questionId
			]
		)
	) {
		return accessRecord;
	}
	// 3. Check if the update amendment reflects a change since the last version of the answer
	if (currentIterationIndex > -1) {
		const latestAnswer = getLatestQuestionAnswer(accessRecord, questionId);
		if (!_.isNil(latestAnswer)) {
			if (
				answer === latestAnswer ||
				helperUtil.arraysEqual(answer, latestAnswer)
			) {
				removeAmendment(accessRecord, questionId);
				return accessRecord;
			}
		} else if (_.isNil(latestAnswer) && _.isEmpty(answer)) {
			// Remove the amendment if there was no previous answer and the latest update is empty
			removeAmendment(accessRecord, questionId);
			return accessRecord;
		}
	}
	// 4. Find and update the question with the new answer
	accessRecord.amendmentIterations[currentIterationIndex].questionAnswers[
		questionId
	] = {
		...accessRecord.amendmentIterations[currentIterationIndex].questionAnswers[
			questionId
		],
		answer,
		updatedBy: `${user.firstname} ${user.lastname}`,
		updatedByUser: user._id,
		dateUpdated: Date.now(),
	};
	// 5. Return updated access record
	return accessRecord;
};

const removeAmendment = (accessRecord, questionId) => {
	// 1. Find the index of the latest amendment amendmentIteration of the DAR
	let index = getLatestAmendmentIterationIndex(accessRecord);
	// 2. Remove the key and associated object from the current iteration if it exists
	if (index !== -1) {
		accessRecord.amendmentIterations[index].questionAnswers = _.omit(
			accessRecord.amendmentIterations[index].questionAnswers,
			questionId
		);
	}
};

const doesAmendmentExist = (accessRecord, questionId) => {
	// 1. Get current amendment iteration
	const latestIteration = getCurrentAmendmentIteration(
		accessRecord.amendmentIterations
	);
	if (_.isNil(latestIteration) || _.isNil(latestIteration.questionAnswers)) {
		return false;
	}
	// 2. Check if questionId has been added by Custodian for amendment
	return latestIteration.questionAnswers.hasOwnProperty(questionId);
};

const handleApplicantAmendment = (
	accessRecord,
	questionId,
	questionSetId,
	answer = '',
	user
) => {
	// 1. Check if an amendment already exists for the question
	let isExisting = doesAmendmentExist(accessRecord, questionId);
	// 2. Update existing
	if (isExisting) {
		accessRecord = updateAmendment(accessRecord, questionId, answer, user);
	} else {
		// 3. Get the latest/previous answer for this question for comparison to new answer
		const latestAnswer = getLatestQuestionAnswer(accessRecord, questionId);
		let performAdd = false;
		// 4. Always add the new amendment if there was no original answer
		if (_.isNil(latestAnswer)) {
			performAdd = true;
			// 5. If a previous answer exists, ensure it is different to the most recent answer before adding
		} else if (
			answer !== latestAnswer ||
			!helperUtil.arraysEqual(answer, latestAnswer)
		) {
			performAdd = true;
		}

		if (performAdd) {
			// 6. Add new amendment otherwise
			addAmendment(
				accessRecord,
				questionId,
				questionSetId,
				answer,
				'',
				user,
				false
			);
		}
	}
	// 7. Update the amendment count
	let {
		unansweredAmendments = 0,
		answeredAmendments = 0,
	} = countUnsubmittedAmendments(accessRecord, constants.userTypes.APPLICANT);
	accessRecord.unansweredAmendments = unansweredAmendments;
	accessRecord.answeredAmendments = answeredAmendments;
	// 8. Return updated access record
	return accessRecord;
};

const getLatestAmendmentIterationIndex = (accessRecord) => {
	// 1. Guard for incorrect type passed
	let { amendmentIterations = [] } = accessRecord;
	if (_.isEmpty(amendmentIterations)) {
		return -1;
	}
	// 2. Find the latest unsubmitted date created in the amendment iterations array
	let mostRecentDate = new Date(
		Math.max.apply(
			null,
			amendmentIterations.map((iteration) =>
				_.isUndefined(iteration.dateSubmitted)
					? new Date(iteration.dateCreated)
					: ''
			)
		)
	);
	// 3. Pull out the related object using a filter to find the object with the latest date
	return amendmentIterations.findIndex((iteration) => {
		let date = new Date(iteration.dateCreated);
		return date.getTime() == mostRecentDate.getTime();
	});
};

const getAmendmentIterationParty = (accessRecord) => {
	// 1. Look for an amendment iteration that is in flight
	//    An empty date submitted with populated date returned indicates that the current correction iteration is now with the applicants
	let index = accessRecord.amendmentIterations.findIndex(
		(v) => _.isUndefined(v.dateSubmitted) && !_.isUndefined(v.dateReturned)
	);
	// 2. Deduce the user type from the current iteration state
	if (index === -1) {
		return constants.userTypes.CUSTODIAN;
	} else {
		return constants.userTypes.APPLICANT;
	}
};

const filterAmendments = (accessRecord = {}, userType) => {
	if (_.isEmpty(accessRecord)) {
		return {};
	}
	let { amendmentIterations = [] } = accessRecord;
	// 1. Extract all revelant iteration objects and answers based on the user type
	// Applicant should only see requested amendments that have been returned by the custodian
	if (userType === constants.userTypes.APPLICANT) {
		amendmentIterations = [...amendmentIterations].filter((iteration) => {
			return !_.isUndefined(iteration.dateReturned);
		});
	} else if (userType === constants.userTypes.CUSTODIAN) {
		// Custodian should only see amendment answers that have been submitted by the applicants
		amendmentIterations = [...amendmentIterations].map((iteration) => {
			if (_.isUndefined(iteration.dateSubmitted)) {
				iteration = removeIterationAnswers(accessRecord, iteration);
			}
			return iteration;
		});
	}
	// 2. Return relevant iteratiions
	return amendmentIterations;
};

const injectAmendments = (accessRecord, userType) => {
	// 1. Filter out amendments that have not yet been exposed to the opposite party
	let amendmentIterations = filterAmendments(accessRecord, userType);
	// 2. Update the question answers to reflect all the changes that have been made in later iterations
	accessRecord.questionAnswers = formatQuestionAnswers(
		accessRecord.questionAnswers,
		amendmentIterations
	);
	// 3. Add amendment requests from latest iteration and append historic responses
	//accessRecord.jsonSchema = formatSchema(JSON.parse(accessRecord.jsonSchema), amendmentIterations);
	// 4. Return the updated access record
	return accessRecord;
};

//const formatSchema = (jsonSchema, amendmentIterations) => {
// 1. Add history for all questions in previous iterations
// TODO for versioning
// 2. Get latest iteration to add amendment requests
//const latestIteration = getCurrentAmendmentIteration(amendmentIterations);
// 3. Loop through each key in the iteration to append review indicator
// Version 2 placeholderr
//return jsonSchema;
//};

const getLatestQuestionAnswer = (accessRecord, questionId) => {
	// 1. Include original submission of question answer
	let parsedQuestionAnwsers = {};
	if (typeof accessRecord.questionAnswers === 'string') {
		parsedQuestionAnwsers = JSON.parse(accessRecord.questionAnswers);
	} else {
		parsedQuestionAnwsers = _.cloneDeep(accessRecord.questionAnswers);
	}
	let initialSubmission = {
		questionAnswers: {
			[`${questionId}`]: {
				answer: parsedQuestionAnwsers[questionId],
				dateUpdated: accessRecord.dateSubmitted,
			},
		},
	};
	let relevantVersions = [
		initialSubmission,
		...accessRecord.amendmentIterations,
	];
	if (relevantVersions.length > 1) {
		relevantVersions = _.slice(
			relevantVersions,
			0,
			relevantVersions.length - 1
		);
	}
	// 2. Reduce all versions to find latest instance of question answer
	const latestAnswers = relevantVersions.reduce((arr, version) => {
		// 3. Move to next version if the question was not modified in this one
		if (_.isNil(version.questionAnswers[questionId])) {
			return arr;
		}
		let { answer, dateUpdated } = version.questionAnswers[questionId];
		let foundIndex = arr.findIndex(
			(amendment) => amendment.questionId === questionId
		);
		// 4. If the amendment does not exist in our array of latest answers, add it
		if (foundIndex === -1) {
			arr.push({ questionId, answer, dateUpdated });
			// 5. Otherwise update the amendment if this amendment was made more recently
		} else if (
			new Date(dateUpdated).getTime() >
			new Date(arr[foundIndex].dateUpdated).getTime()
		) {
			arr[foundIndex] = { questionId, answer, dateUpdated };
		}
		return arr;
	}, []);

	if (_.isEmpty(latestAnswers)) {
		return undefined;
	} else {
		return latestAnswers[0].answer;
	}
};

const formatQuestionAnswers = (questionAnswers, amendmentIterations) => {
	// 1. Reduce all amendment iterations to find latest answers
	const latestAnswers = amendmentIterations.reduce((arr, iteration) => {
		if (_.isNil(iteration.questionAnswers)) {
			return arr;
		}
		// 2. Loop through each amendment key per iteration
		Object.keys(iteration.questionAnswers).forEach((questionId) => {
			let { answer, dateUpdated } = iteration.questionAnswers[questionId];
			let foundIndex = arr.findIndex(
				(amendment) => amendment.questionId === questionId
			);
			// 3. If the amendment does not exist in our array of latest answers, add it
			if (foundIndex === -1) {
				arr.push({ questionId, answer, dateUpdated });
				// 4. Otherwise update the amendment if this amendment was made more recently
			} else if (
				new Date(dateUpdated).getTime() >
				new Date(arr[foundIndex].dateUpdated).getTime()
			) {
				arr[foundIndex] = { questionId, answer, dateUpdated };
			}
		});
		return arr;
	}, []);
	// 5. Format data correctly for question answers
	const formattedLatestAnswers = [...latestAnswers].reduce((obj, item) => {
		obj[item.questionId] = item.answer;
		return obj;
	}, {});
	// 6. Return combined object
	return { ...questionAnswers, ...formattedLatestAnswers };
};

const getCurrentAmendmentIteration = (amendmentIterations) => {
	// 1. Guard for incorrect type passed
	if (
		_.isEmpty(amendmentIterations) ||
		_.isNull(amendmentIterations) ||
		_.isUndefined(amendmentIterations)
	) {
		return undefined;
	}
	// 2. Find the latest unsubmitted date created in the amendment iterations array
	let mostRecentDate = new Date(
		Math.max.apply(
			null,
			amendmentIterations.map((iteration) =>
				_.isUndefined(iteration.dateSubmitted)
					? new Date(iteration.dateCreated)
					: ''
			)
		)
	);
	// 3. Pull out the related object using a filter to find the object with the latest date
	let mostRecentObject = amendmentIterations.filter((iteration) => {
		let date = new Date(iteration.dateCreated);
		return date.getTime() == mostRecentDate.getTime();
	})[0];
	// 4. Return the correct object
	return mostRecentObject;
};

const removeIterationAnswers = (accessRecord = {}, iteration) => {
	// 1. Guard for invalid object passed
	if (!iteration || !iteration.questionAnswers || _.isEmpty(accessRecord)) {
		return undefined;
	}
	// 2. Loop through each question answer by key (questionId)
	Object.keys(iteration.questionAnswers).forEach((key) => {
		// 3. Fetch the previous answer
		iteration.questionAnswers[key]['answer'] = getLatestQuestionAnswer(
			accessRecord,
			key
		);
	});
	// 4. Return answer stripped iteration object
	return iteration;
};

const doResubmission = (accessRecord, userId) => {
	// 1. Find latest iteration and if not found, return access record unmodified as no resubmission should take place
	let index = getLatestAmendmentIterationIndex(accessRecord);
	if (index === -1) {
		return accessRecord;
	}
	// 2. Mark submission type as a resubmission later used to determine notification generation
	accessRecord.submissionType = constants.submissionTypes.RESUBMISSION;
	accessRecord.amendmentIterations[index] = {
		...accessRecord.amendmentIterations[index],
		dateSubmitted: new Date(),
		submittedBy: userId,
	};
	// 3. Return updated access record for saving
	return accessRecord;
};

const countUnsubmittedAmendments = (accessRecord, userType) => {
	// 1. Find latest iteration and if not found, return 0
	let unansweredAmendments = 0;
	let answeredAmendments = 0;
	let index = getLatestAmendmentIterationIndex(accessRecord);
	if (
		index === -1 ||
		_.isNil(accessRecord.amendmentIterations[index].questionAnswers) ||
		(_.isNil(accessRecord.amendmentIterations[index].dateSubmitted) &&
			userType === constants.userTypes.CUSTODIAN)
	) {
		return { unansweredAmendments: 0, answeredAmendments: 0 };
	}
	// 2. Count answered and unanswered amendments in unsubmitted iteration
	Object.keys(accessRecord.amendmentIterations[index].questionAnswers).forEach(
		(questionId) => {
			if (
				_.isNil(
					accessRecord.amendmentIterations[index].questionAnswers[questionId]
						.answer
				)
			) {
				unansweredAmendments++;
			} else {
				answeredAmendments++;
			}
		}
	);
	// 3. Return counts
	return { unansweredAmendments, answeredAmendments };
};

module.exports = {
	handleApplicantAmendment: handleApplicantAmendment,
	doesAmendmentExist: doesAmendmentExist,
	doResubmission: doResubmission,
	updateAmendment: updateAmendment,
	setAmendment: setAmendment,
	addAmendment: addAmendment,
	removeAmendment: removeAmendment,
	filterAmendments: filterAmendments,
	removeIterationAnswers: removeIterationAnswers,
	getCurrentAmendmentIteration: getCurrentAmendmentIteration,
	getLatestAmendmentIterationIndex: getLatestAmendmentIterationIndex,
	getAmendmentIterationParty: getAmendmentIterationParty,
	injectAmendments: injectAmendments,
	formatQuestionAnswers: formatQuestionAnswers,
	countUnsubmittedAmendments: countUnsubmittedAmendments,
	getLatestQuestionAnswer: getLatestQuestionAnswer,
};
