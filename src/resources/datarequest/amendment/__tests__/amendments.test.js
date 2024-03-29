import constants from '../../../utilities/constants.util';
import DataRequestClass from '../../datarequest.entity';
import { amendmentService } from '../dependency';
import _ from 'lodash';

const dataRequest = require('../../__mocks__/datarequest');
const users = require('../../__mocks__/users');

describe('addAmendment', () => {
	test('given a data request with an existing active amendment iteration, and a custodian triggers an amendment request, then the specified amendment is added to the active iteration', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[0]);
		const questionId = 'title',
			questionSetId = 'applicant',
			answer = '',
			reason = 'the title was incorrectly selected',
			user = users.custodian,
			requested = true;
		const expected = {
			questionSetId,
			requested,
			reason,
			requestedBy: `${user.firstname} ${user.lastname}`,
			requestedByUser: user._id,
		};
		// Act
		amendmentService.addAmendment(data, questionId, questionSetId, answer, reason, user, requested);
		// Assert
		expect(dataRequest[0].amendmentIterations[1].questionAnswers).not.toHaveProperty('title');
		expect(Object.keys(data.amendmentIterations[1].questionAnswers).length).toBe(2);
		expect(data.amendmentIterations[1].questionAnswers).toHaveProperty('title');
		expect(data.amendmentIterations[1].questionAnswers['title']).toHaveProperty('dateRequested');
		expect(data.amendmentIterations[1].questionAnswers['title']).toMatchObject(expected);
	});

	test('given a data request with an existing active iteration, and an applicant makes an unrequested amendment, then the specified amendment including the updated answer is added to the current iteration', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[0]);
		const questionId = 'dateofbirth',
			questionSetId = 'applicant',
			answer = '15/01/1982',
			reason = '',
			user = users.applicant,
			requested = false;
		const expected = {
			questionSetId,
			answer,
			requested,
			reason,
			updatedBy: `${user.firstname} ${user.lastname}`,
			updatedByUser: user._id,
		};
		// Act
		amendmentService.addAmendment(data, questionId, questionSetId, answer, reason, user, requested);
		// Assert
		expect(dataRequest[0].amendmentIterations[1].questionAnswers).not.toHaveProperty('dateofbirth');
		expect(Object.keys(data.amendmentIterations[1].questionAnswers).length).toBe(2);
		expect(data.amendmentIterations[1].questionAnswers).toHaveProperty('dateofbirth');
		expect(data.amendmentIterations[1].questionAnswers['dateofbirth']).toHaveProperty('dateUpdated');
		expect(data.amendmentIterations[1].questionAnswers['dateofbirth']['dateRequested']).toBeFalsy();
		expect(data.amendmentIterations[1].questionAnswers['dateofbirth']['requestedBy']).toBeFalsy();
		expect(data.amendmentIterations[1].questionAnswers['dateofbirth']['requestedByUser']).toBeFalsy();
		expect(data.amendmentIterations[1].questionAnswers['dateofbirth']).toMatchObject(expected);
	});

	test('given a data request with an existing active iteration, and an applicant updates an existing amendment, the new amendment takes precedence', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[0]);
		const questionId = 'dateofbirth',
			questionSetId = 'applicant',
			answer = '15/01/1982',
			secondAnswer = '16/01/1982',
			reason = '',
			user = users.applicant,
			requested = false;
		const expected = {
			questionSetId,
			answer: secondAnswer,
			requested,
			reason,
			updatedBy: `${user.firstname} ${user.lastname}`,
			updatedByUser: user._id,
		};
		// Act
		amendmentService.addAmendment(data, questionId, questionSetId, answer, reason, user, requested);
		let firstAnswer = data.amendmentIterations[1].questionAnswers['dateofbirth']['answer'];
		let firstDateUpdated = data.amendmentIterations[1].questionAnswers['dateofbirth']['dateUpdated'];
		setTimeout(() => {
			amendmentService.addAmendment(data, questionId, questionSetId, secondAnswer, reason, user, requested);
			// Assert
			expect(dataRequest[0].amendmentIterations[1].questionAnswers).not.toHaveProperty('dateofbirth');
			expect(firstAnswer).toBe(answer);
			expect(firstDateUpdated.getTime()).toBeLessThan(data.amendmentIterations[1].questionAnswers['dateofbirth']['dateUpdated'].getTime());
			expect(Object.keys(data.amendmentIterations[1].questionAnswers).length).toBe(2);
			expect(data.amendmentIterations[1].questionAnswers).toHaveProperty('dateofbirth');
			expect(data.amendmentIterations[1].questionAnswers['dateofbirth']).toHaveProperty('dateUpdated');
			expect(data.amendmentIterations[1].questionAnswers['dateofbirth']['answer']).not.toBe(firstAnswer);
			expect(data.amendmentIterations[1].questionAnswers['dateofbirth']['dateRequested']).toBeFalsy();
			expect(data.amendmentIterations[1].questionAnswers['dateofbirth']['requestedBy']).toBeFalsy();
			expect(data.amendmentIterations[1].questionAnswers['dateofbirth']['requestedByUser']).toBeFalsy();
			expect(data.amendmentIterations[1].questionAnswers['dateofbirth']).toMatchObject(expected);
		}, 1);
	});

	test('given a data request without an active amendment iteration, and a custodian triggers an amendment request, then the specified amendment is added to a new iteration as the only key', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[1]);
		const questionId = 'title',
			questionSetId = 'applicant',
			answer = '',
			reason = 'the title was incorrectly selected',
			user = users.custodian,
			requested = true;
		const expected = {
			createdBy: user._id,
			questionAnswers: {
				title: {
					questionSetId,
					requested,
					reason,
					requestedBy: `${user.firstname} ${user.lastname}`,
					requestedByUser: user._id,
				},
			},
		};
		// Act
		amendmentService.addAmendment(data, questionId, questionSetId, answer, reason, user, requested);
		// Assert
		expect(dataRequest[1].amendmentIterations).toHaveLength(0);
		expect(data.amendmentIterations).toHaveLength(1);
		expect(Object.keys(data.amendmentIterations[0].questionAnswers).length).toBe(1);
		expect(data.amendmentIterations[0]).toHaveProperty('dateCreated');
		expect(data.amendmentIterations[0].questionAnswers['title']).toHaveProperty('dateRequested');
		expect(data.amendmentIterations[0]).toMatchObject(expected);
	});

	test('given a data request without an existing active iteration, and an applicant makes an unrequested amendment, then the specified amendment including the updated answer is added to a new iteration as the only key', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[1]);
		const questionId = 'dateofbirth',
			questionSetId = 'applicant',
			answer = '15/01/1982',
			reason = '',
			user = users.applicant,
			requested = false;
		const expected = {
			createdBy: user._id,
			questionAnswers: {
				dateofbirth: {
					questionSetId,
					answer,
					requested,
					reason,
					updatedBy: `${user.firstname} ${user.lastname}`,
					updatedByUser: user._id,
				},
			},
		};
		// Act
		amendmentService.addAmendment(data, questionId, questionSetId, answer, reason, user, requested);
		// Assert
		expect(dataRequest[1].amendmentIterations).toHaveLength(0);
		expect(data.amendmentIterations).toHaveLength(1);
		expect(Object.keys(data.amendmentIterations[0].questionAnswers).length).toBe(1);
		expect(data.amendmentIterations[0]).toHaveProperty('dateCreated');
		expect(data.amendmentIterations[0].questionAnswers['dateofbirth']).toHaveProperty('dateUpdated');
		expect(data.amendmentIterations[0]).toMatchObject(expected);
	});
});

describe('getCurrentAmendmentIteration', () => {
	test('extracts most recent iteration object by created date', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[0]);
		const expected = {
			dateCreated: '2020-11-03T11:14:01.843+00:00',
			createdBy: '5f03530178e28143d7af2eb1',
			questionAnswers: {
				lastName: {
					questionSetId: 'applicant',
					requested: true,
					reason: 'test reason',
					requestedBy: 'Robin Kavanagh',
					requestedByUser: '5f03530178e28143d7af2eb1',
					dateRequested: '2020-11-03T11:14:01.840+00:00',
				},
			},
		};
		// Act
		const result = amendmentService.getCurrentAmendmentIteration(data.amendmentIterations);
		// Assert
		expect(result).toEqual(expected);
	});
});

describe('getLatestAmendmentIterationIndex', () => {
	test('extracts most recent iteration object index by created date', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[0]);
		// Act
		const result = amendmentService.getLatestAmendmentIterationIndex(data);
		// Assert
		expect(result).toBe(1);
	});
});

describe('getAmendmentIterationParty', () => {
	test('given a data request application has been submitted by the applicant, the custodian is now the current responsible party until application is returned', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[0]);
		// Act
		const result = amendmentService.getAmendmentIterationParty(data);
		// Assert
		expect(result).toBe(constants.userTypes.CUSTODIAN);
	});

	test('given a data request application has been returned by the custodian, the applicant is now the current responsible party', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[0]);
		// Act
		data.amendmentIterations[1].dateReturned = new Date();
		// Assert
		expect(amendmentService.getAmendmentIterationParty(data)).toBe(constants.userTypes.APPLICANT);
	});
});

describe('removeIterationAnswers', () => {
	// Arrage
	const expected = {
		dateCreated: '2020-10-05T11:14:01.843+00:00',
		createdBy: '5f03530178e28143d7af2eb1',
		dateReturned: '2020-10-05T12:14:01.843+00:00',
		returnedBy: '5f03530178e28143d7af2eb1',
		questionAnswers: {
			country: {
				questionSetId: 'applicant',
				requested: true,
				reason: 'country selection is invalid',
				requestedBy: 'Robin Kavanagh',
				requestedByUser: '5f03530178e28143d7af2eb1',
				dateRequested: '2020-10-04T17:14:01.843+00:00',
				answer: 'UK',
			},
			reasonforaccess: {
				questionSetId: 'reasons',
				requested: true,
				reason: 'reason for access is not accepted',
				requestedBy: 'Robin Kavanagh',
				requestedByUser: '5f03530178e28143d7af2eb1',
				dateRequested: '2020-10-04T17:14:01.843+00:00',
			},
		},
	};
	const data = _.cloneDeep(dataRequest);
	const cases = [
		[data[4], data[4].amendmentIterations[2], expected]
	];
	test.each(cases)(
		'given an amendment iteration which is not resubmitted, it strips answers',
		(accessRecord, iteration, expectedResult) => {
			// Act
			const result = amendmentService.removeIterationAnswers(accessRecord, iteration);
			// Assert
			expect(result).toEqual(expectedResult);
		}
	);
});

describe('handleApplicantAmendment', () => {
	test('given an applicant makes an amendment, then the corresponding amendment is updated or created depending on existance of requested or previous amendment', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[1]);
		const questionId = 'lastName',
			questionSetId = 'applicant',
			answer = 'Smith',
			user = users.applicant;
		// Act
		data = amendmentService.handleApplicantAmendment(data, questionId, questionSetId, answer, user);
		// Assert
		expect(dataRequest[1].amendmentIterations.length).toBeFalsy();
		expect(Object.keys(data.amendmentIterations[0].questionAnswers).length).toBe(1);
		expect(data.amendmentIterations[0].questionAnswers[questionId]).toHaveProperty('dateUpdated');
		expect(data.amendmentIterations[0].questionAnswers[questionId]['answer']).toBe('Smith');
		expect(data.amendmentIterations[0].questionAnswers[questionId]['updatedBy']).toBe('test applicant 1');
		expect(data.amendmentIterations[0].questionAnswers[questionId]['updatedByUser']).toBe(user._id);
	});

	test('given an applicant makes an amendment, and updates the same question, then the latest answer is correctly stored in the same iteration version', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[1]);
		const questionId = 'lastName',
			questionSetId = 'applicant',
			answer = 'Smyth',
			secondAnswer = 'Smith',
			user = users.applicant;
		data = amendmentService.handleApplicantAmendment(data, questionId, questionSetId, answer, user);
		// Act
		data = amendmentService.handleApplicantAmendment(data, questionId, questionSetId, secondAnswer, user);
		// Assert
		expect(dataRequest[1].amendmentIterations.length).toBeFalsy();
		expect(Object.keys(data.amendmentIterations[0].questionAnswers).length).toBe(1);
		expect(data.amendmentIterations[0].questionAnswers[questionId]).toHaveProperty('dateUpdated');
		expect(data.amendmentIterations[0].questionAnswers[questionId]['answer']).toBe('Smith');
		expect(data.amendmentIterations[0].questionAnswers[questionId]['updatedBy']).toBe('test applicant 1');
		expect(data.amendmentIterations[0].questionAnswers[questionId]['updatedByUser']).toBe(user._id);
	});
});

describe('removeAmendment', () => {
	test('given a data requst with an existing amendment iteration, and a custodian removes a requested amendment, then the access record is updated', () => {
		//Arrange
		let data = _.cloneDeep(dataRequest[0]);
		const questionId = 'lastName';
		const initialLastName = data.amendmentIterations[1].questionAnswers[questionId];
		const expected = {
			questionSetId: 'applicant',
			requested: true,
			reason: 'test reason',
			requestedBy: 'Robin Kavanagh',
			requestedByUser: '5f03530178e28143d7af2eb1',
			dateRequested: '2020-11-03T11:14:01.840+00:00',
		};
		//Act
		amendmentService.removeAmendment(data, questionId);
		//Assert
		expect(initialLastName).toEqual(expected);
		expect(dataRequest[0].amendmentIterations[1]).not.toBeFalsy();
		expect(data.amendmentIterations[1]).toBeFalsy();
	});
});

describe('doesAmendmentExist', () => {
	// Arrange
	const data = _.cloneDeep(dataRequest);
	const cases = [
		[data[0], 'lastName', true],
		[data[0], 'firstName', false],
		[{}, '', false],
		[data[1], 'firstName', false],
	];
	test.each(cases)(
		'given a data request object %p and %p as the question amended, returns %p for an amendment existing',
		(data, questionId, expectedResult) => {
			// Act
			const result = amendmentService.doesAmendmentExist(data, questionId);
			// Assert
			expect(result).toBe(expectedResult);
		}
	);
});

describe('updateAmendment', () => {
	test('given a data request with an existing active amendment iteration, and an applicant updates their own existing amendment, then the existing amendment is updated', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[2]);
		const questionId = 'lastName',
			answer = 'Smith',
			user = users.applicant,
			initialUpdatedDate = dataRequest[2].amendmentIterations[0].questionAnswers['lastName'].dateUpdated;
		// Act
		data = amendmentService.updateAmendment(data, questionId, answer, user);
		// Assert
		expect(Object.keys(data.amendmentIterations[0].questionAnswers).length).toBe(1);
		expect(new Date(data.amendmentIterations[0].questionAnswers['lastName']['dateUpdated']).getTime()).toBeGreaterThan(
			new Date(initialUpdatedDate).getTime()
		);
		expect(data.amendmentIterations[0].questionAnswers['lastName']['answer']).toBe('Smith');
		expect(data.amendmentIterations[0].questionAnswers['lastName']['updatedBy']).toBe('test applicant 1');
		expect(data.amendmentIterations[0].questionAnswers['lastName']['updatedByUser']).toBe(user._id);
	});
	test('given a data request with an existing active amendment iteration, and a collaborator updates an amendment they did not create, then the existing amendment is updated', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[2]);
		const questionId = 'lastName',
			answer = 'Smith',
			user = users.collaborator;
		const { dateUpdated: initialUpdatedDate, updatedBy: initialUpdatedBy } = dataRequest[2].amendmentIterations[0].questionAnswers[
			'lastName'
		];
		// Act
		data = amendmentService.updateAmendment(data, questionId, answer, user);
		// Assert
		expect(initialUpdatedBy).toBe('test applicant 1');
		expect(Object.keys(data.amendmentIterations[0].questionAnswers).length).toBe(1);
		expect(new Date(data.amendmentIterations[0].questionAnswers['lastName']['dateUpdated']).getTime()).toBeGreaterThan(
			new Date(initialUpdatedDate).getTime()
		);
		expect(data.amendmentIterations[0].questionAnswers['lastName']['answer']).toBe('Smith');
		expect(data.amendmentIterations[0].questionAnswers['lastName']['updatedBy']).toBe('test collaborator 1');
		expect(data.amendmentIterations[0].questionAnswers['lastName']['updatedByUser']).toBe(user._id);
	});
	// test collab
	test('given a data request with an existing active amendment iteration, and an applicant updates a non-existing amendment which is an invalid operation, then the access record is unchanged', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[2]);
		const questionId = 'firstName',
			answer = 'James',
			user = users.applicant;
		// Act
		data = amendmentService.updateAmendment(data, questionId, answer, user);
		// Assert
		expect(Object.keys(data.amendmentIterations[0].questionAnswers).length).toBe(1);
		expect(data.amendmentIterations[0].questionAnswers['firstName']).toBeFalsy();
		expect(data).toEqual(dataRequest[2]);
	});
	test('given a data request without an active amendment iteration, and an applicant updates an existing amendment which is an invalid operation, then the access record is unchanged', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[1]);
		const questionId = 'firstName',
			answer = 'James',
			user = users.applicant;
		// Act
		data = amendmentService.updateAmendment(data, questionId, answer, user);
		// Assert
		expect(data.amendmentIterations.length).toBeFalsy();
		expect(data).toEqual(dataRequest[1]);
	});
});

describe('formatQuestionAnswers', () => {
	test('given an access record with a number of amendments made post submissions, then the access record is updated with the latest answers', () => {
		// Arrange
		const data = _.cloneDeep(dataRequest[0]);
		// Act
		data.questionAnswers = amendmentService.formatQuestionAnswers(data.questionAnswers, data.amendmentIterations);
		// Assert
		expect(dataRequest[0].questionAnswers['firstName']).toBe('ra');
		expect(dataRequest[0].questionAnswers['lastName']).toBe('adsf');
		expect(data.questionAnswers['firstName']).toBe('James');
		expect(data.questionAnswers['lastName']).toBe('Smyth');
	});
	test('given an access record with a number of amendments made through multiple re-submissions, then the access record is updated with the latest answers', () => {
		// Arrange
		const data = _.cloneDeep(dataRequest[3]);
		// Act
		data.questionAnswers = amendmentService.formatQuestionAnswers(data.questionAnswers, data.amendmentIterations);
		// Assert
		expect(data.questionAnswers['firstName']).toBe('Mark');
		expect(data.questionAnswers['lastName']).toBe('Connolly');
	});
});

describe('filterAmendments', () => {
	test('given an access record with an amendment iteration that has not been returned to the applicants, then the amendment iteration is filtered out for the applicant', () => {
		// Arrange
		const data = _.cloneDeep(dataRequest[3]);
		// Act
		const result = amendmentService.filterAmendments(data, constants.userTypes.APPLICANT);
		// Assert
		expect(result.length).toBe(2);
		expect(result[result.length - 1].dateReturned).not.toBeFalsy();
	});
	test('given an access record with an amendment iteration that has not been returned to the applicants, then the amendment iteration is still visible to the custodian', () => {
		// Arrange
		const data = _.cloneDeep(dataRequest[3]);
		// Act
		const result = amendmentService.filterAmendments(data, constants.userTypes.CUSTODIAN);
		// Assert
		expect(result.length).toBe(3);
		expect(result[result.length - 1].dateCreated).not.toBeFalsy();
		expect(result[result.length - 1].dateReturned).toBeFalsy();
	});
	test('given an access record with an amendment iteration that has not been resubmitted to the custodian, then the latest amendment iteration answers are not visible to the custodian', () => {
		// Arrange
		const data = _.cloneDeep(dataRequest[4]);
		// Act
		const result = amendmentService.filterAmendments(data, constants.userTypes.CUSTODIAN);
		// Assert
		expect(result.length).toBe(3);
		expect(result[result.length - 1].questionAnswers['country']['answer']).toBe('UK');
		expect(result[result.length - 1].dateCreated).not.toBeFalsy();
		expect(result[result.length - 1].dateReturned).not.toBeFalsy();
		expect(result[result.length - 1].dateSubmitted).toBeFalsy();
	});
	test('given an access record with an amendment iteration that has not been resubmitted to the custodian, then the latest amendment iteration answers are still visible to the applicant', () => {
		// Arrange
		const data = _.cloneDeep(dataRequest[4]);
		// Act
		const result = amendmentService.filterAmendments(data, constants.userTypes.APPLICANT);
		// Assert
		expect(result.length).toBe(3);
		expect(result[result.length - 1].questionAnswers['country']).toHaveProperty('answer');
		expect(result[result.length - 1].dateCreated).not.toBeFalsy();
		expect(result[result.length - 1].dateReturned).not.toBeFalsy();
		expect(result[result.length - 1].dateSubmitted).toBeFalsy();
	});
});

describe('injectAmendments', () => {
	test('given an access record containing an amendment iteration that has not yet been resubmitted, the custodian receives the previous answers', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[5]);
		// Act
		data = amendmentService.injectAmendments(data, constants.userTypes.CUSTODIAN);
		// Assert
		expect(data.questionAnswers['firstName']).toBe('Mark');
		expect(data.questionAnswers['lastName']).toBe('Connolly');
		expect(data.questionAnswers['country']).toBeFalsy();
	});
	test('given an access record containing an amendment iteration that has not yet been resubmitted, the applicant receives the latest answers', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[5]);
		// Act
		data = amendmentService.injectAmendments(data, constants.userTypes.APPLICANT);
		// Assert
		expect(data.questionAnswers['firstName']).toBe('Mark');
		expect(data.questionAnswers['lastName']).toBe('Connolly');
		expect(data.questionAnswers['country']).toBe('United Kingdom');
	});
	test('given an access record has no amendment iterations, the record is returned unmodified for an applicant', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[6]);
		// Act
		data = amendmentService.injectAmendments(data, constants.userTypes.APPLICANT);
		// Assert
		expect(data).toEqual(dataRequest[6]);
	});
	test('given an access record has no amendment iterations, the record is returned unmodified for a custodian', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[6]);
		// Act
		data = amendmentService.injectAmendments(data, constants.userTypes.CUSTODIAN);
		// Assert
		expect(data).toEqual(dataRequest[6]);
	});
});

describe('doResubmission', () => {
	test('given a data access record is resubmitted with a valid amendment iteration, then the iteration is updated to submitted', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[4]);
		let accessRecord = new DataRequestClass(data);
		// Act
		accessRecord = amendmentService.doResubmission(accessRecord, users.applicant._id);
		// Assert
		expect(dataRequest[4].amendmentIterations[2].dateSubmitted).toBeFalsy();
		expect(dataRequest[4].amendmentIterations[2].submittedBy).toBeFalsy();
		expect(accessRecord.amendmentIterations[0]).toEqual(dataRequest[4].amendmentIterations[0]);
		expect(accessRecord.amendmentIterations[0]).toEqual(dataRequest[4].amendmentIterations[0]);
		expect(accessRecord.amendmentIterations[1]).toEqual(dataRequest[4].amendmentIterations[1]);
		expect(accessRecord.amendmentIterations[1]).toEqual(dataRequest[4].amendmentIterations[1]);
		expect(accessRecord.amendmentIterations[2]).toHaveProperty('dateSubmitted');
		expect(accessRecord.amendmentIterations[2].submittedBy).toBe(users.applicant._id);
	});
});

describe('countAmendments', () => {
	test('given a data access record with unsubmitted amendments, the correct number of answered and unanswered amendments in returned', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[5]);
		// Act
		const result = amendmentService.countAmendments(data, constants.userTypes.APPLICANT);
		// Assert
		expect(result.unansweredAmendments).toBe(2);
		expect(result.answeredAmendments).toBe(1);
	});
	test('given a data access record with no amendments, the correct number of answered and unanswered amendments in returned', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[6]);
		// Act
		const result = amendmentService.countAmendments(data, constants.userTypes.APPLICANT);
		// Assert
		expect(result.unansweredAmendments).toBe(0);
		expect(result.answeredAmendments).toBe(0);
	});
});

describe('getLatestQuestionAnswer', () => {
	// Arrange
	let data = _.cloneDeep(dataRequest);
	const cases = [
		[data[0], 'firstName', 'James'],
		[data[0], 'lastName', 'Smyth'],
		[data[2], 'lastName', 'Connilly'],
		[data[3], 'country', ''],
		[data[3], 'firstName', 'Mark'],
	];
	test.each(cases)(
		'given a data access record with multiple amendment versions, the latest previous answer is returned',
		(accessRecord, questionId, expectedResult) => {
			// Act
			const result = amendmentService.getLatestQuestionAnswer(accessRecord, questionId);
			// Assert
			expect(result).toBe(expectedResult);
		}
	);
});

describe('revertAmendmentAnswer', () => {
	test('given a data access record with an unsubmitted amendment, and the applicant reverts the amendment answer, then the updated answer is removed from the current iteration', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[4]);
		let questionId = 'country';
		let user = users.applicant;
		// Act
		amendmentService.revertAmendmentAnswer(data, questionId, user);
		// Assert
		expect(dataRequest[4].amendmentIterations[2].questionAnswers[questionId].answer).not.toBeFalsy();
		expect(data.amendmentIterations[2].questionAnswers[questionId].answer).toBeFalsy();
	});
	test('given an invalid revert amendment operation occurs for an existing question with no answer to remove, then the access record remains unchanged', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[4]);
		let questionId = 'reasonforaccess';
		let user = users.applicant;
		// Act
		amendmentService.revertAmendmentAnswer(data, questionId, user);
		// Assert
		expect(dataRequest[4]).toEqual(data);
	});
	test('given an invalid revert amendment operation occurs on a data access record, then the access record remains unchanged', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[4]);
		let questionId = 'firstname';
		let user = users.applicant;
		// Act
		amendmentService.revertAmendmentAnswer(data, questionId, user);
		// Assert
		expect(dataRequest[4]).toEqual(data);
	});
});

describe('injectNavigationAmendment', () => {
	// Arrange
	const cases = [
		[_.cloneDeep(dataRequest[0].jsonSchema), 'applicant', 'safePeople', constants.userTypes.CUSTODIAN, 'completed', 'returned', {"flag": "WARNING"}, {}],
		[_.cloneDeep(dataRequest[0].jsonSchema), 'applicant', 'safePeople', constants.userTypes.CUSTODIAN, 'incomplete', 'returned', {"flag": "WARNING"}, {}],
		[_.cloneDeep(dataRequest[0].jsonSchema), 'principleInvestigator', 'safePeople', constants.userTypes.CUSTODIAN, 'completed', 'submitted', {"flag": "SUCCESS"}, {}],
		[_.cloneDeep(dataRequest[0].jsonSchema), 'principleInvestigator', 'safePeople', constants.userTypes.CUSTODIAN, 'incomplete', 'inProgress', {"flag": "WARNING"}, {}],
		[_.cloneDeep(dataRequest[0].jsonSchema), 'applicant', 'safePeople', constants.userTypes.APPLICANT, 'completed', 'returned', {"flag": "SUCCESS"}, {}],
		[_.cloneDeep(dataRequest[0].jsonSchema), 'applicant', 'safePeople', constants.userTypes.APPLICANT, 'incomplete', 'returned', {"flag": "DANGER"}, {}],
		[_.cloneDeep(dataRequest[0].jsonSchema), 'principleInvestigator', 'safePeople', constants.userTypes.APPLICANT, 'completed', 'submitted', {"flag": "SUCCESS"}, {}],
		[_.cloneDeep(dataRequest[0].jsonSchema), 'principleInvestigator', 'safePeople', constants.userTypes.APPLICANT, 'incomplete', 'submitted', {"flag": "DANGER"}, {}]
	];
	test.each(cases)(
		'given a valid json schema, and a requested amendment, then the corresponding navigation panels are highlighted to reflect the amendment status',
		(jsonSchema, questionSetId, pageId, userType, completed, iterationStatus, expectedPageResult, expectedPanelResult) => {
			// Act
			const result = amendmentService.injectNavigationAmendment(jsonSchema, questionSetId, userType, completed, iterationStatus);
			// Assert
			expect(result.pages.find(page => page.pageId === pageId)).toMatchObject(expectedPageResult);
			expect(result.questionPanels.find(panel => panel.panelId === questionSetId)).toMatchObject(expectedPageResult);
		}
	);
	test('given a valid json schema, containing multiple amendments with differing statuses, then the corresponding navigation panels are highlighted to reflect the amendment status', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[0]);
		let pageId = 'safePeople';
		// Act
		let jsonSchema = amendmentService.injectNavigationAmendment(data.jsonSchema, 'applicant', constants.userTypes.APPLICANT, 'completed', 'submitted');
		jsonSchema = amendmentService.injectNavigationAmendment(data.jsonSchema, 'principleInvestigator', constants.userTypes.APPLICANT, 'incomplete', 'submitted');
		// Assert
		expect(jsonSchema.pages.find(page => page.pageId === pageId)).toMatchObject({"flag": "DANGER"});
		expect(jsonSchema.questionPanels.find(panel => panel.panelId === 'applicant')).toMatchObject({"flag": "SUCCESS"});
		expect(jsonSchema.questionPanels.find(panel => panel.panelId === 'principleInvestigator')).toMatchObject({"flag": "DANGER"});
	});
	test('given a valid json schema, containing multiple amendments with incomplete statuses, then the corresponding navigation panels are highlighted as danger', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[0]);
		let pageId = 'safePeople';
		// Act
		let jsonSchema = amendmentService.injectNavigationAmendment(data.jsonSchema, 'applicant', constants.userTypes.APPLICANT, 'incomplete', 'submitted');
		jsonSchema = amendmentService.injectNavigationAmendment(data.jsonSchema, 'principleInvestigator', constants.userTypes.APPLICANT, 'incomplete', 'submitted');
		// Assert
		expect(jsonSchema.pages.find(page => page.pageId === pageId)).toMatchObject({"flag": "DANGER"});
		expect(jsonSchema.questionPanels.find(panel => panel.panelId === 'applicant')).toMatchObject({"flag": "DANGER"});
		expect(jsonSchema.questionPanels.find(panel => panel.panelId === 'principleInvestigator')).toMatchObject({"flag": "DANGER"});
	});
	test('given a valid json schema, containing multiple amendments with entirely complete statuses, then the corresponding navigation panels are highlighted as success', () => {
		// Arrange
		let data = _.cloneDeep(dataRequest[0]);
		let pageId = 'safePeople';
		// Act
		let jsonSchema = amendmentService.injectNavigationAmendment(data.jsonSchema, 'applicant', constants.userTypes.APPLICANT, 'completed', 'submitted');
		jsonSchema = amendmentService.injectNavigationAmendment(data.jsonSchema, 'principleInvestigator', constants.userTypes.APPLICANT, 'completed', 'submitted');
		// Assert
		expect(jsonSchema.pages.find(page => page.pageId === pageId)).toMatchObject({"flag": "SUCCESS"});
		expect(jsonSchema.questionPanels.find(panel => panel.panelId === 'applicant')).toMatchObject({"flag": "SUCCESS"});
		expect(jsonSchema.questionPanels.find(panel => panel.panelId === 'principleInvestigator')).toMatchObject({"flag": "SUCCESS"});
	});
});
