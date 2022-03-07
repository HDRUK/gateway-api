import { ObjectID } from 'mongodb';

export const datasetSearchStub = [
	{
		_id: ObjectID('615aee882414847722e46ac1'),
		timestamps: {
			created: 100,
			updated: 100,
			submitted: 100,
		},
		pid: 'pid1',
		datasetVersion: '1.0.0',
		name: 'test1 v1',
		datasetv2: { summary: { publisher: { identifier: 'TestPublisher' }, abstract: 'abstract1', keywords: ['test'] } },
		activeflag: 'rejected',
		datasetfields: {
			metadataquality: {
				weighted_quality_score: 80,
			},
		},
		type: 'dataset',
	},
	{
		_id: ObjectID('615aee882414847722e46ac2'),
		timestamps: {
			created: 123,
			updated: 123,
			submitted: 123,
		},
		pid: 'pid1',
		datasetVersion: '2.0.0',
		name: 'A test1 v2',
		datasetv2: { summary: { publisher: { identifier: 'TestPublisher' }, abstract: 'abstract2' } },
		activeflag: 'inReview',
		datasetfields: {
			metadataquality: {
				weighted_quality_score: 60,
			},
		},
		type: 'dataset',
		counter: 5,
	},
	{
		_id: ObjectID('615aee882414847722e46ac3'),
		timestamps: {
			created: 400,
			updated: 400,
			submitted: 400,
		},
		pid: 'pid2',
		datasetVersion: '1.0.0',
		name: 'B test2 v1',
		datasetv2: { summary: { publisher: { identifier: 'TestPublisher' }, abstract: 'abstract3' } },
		activeflag: 'inReview',
		datasetfields: {
			metadataquality: {
				weighted_quality_score: 80,
			},
		},
		type: 'dataset',
		counter: 10,
	},
	{
		_id: ObjectID('615aee882414847722e46ac4'),
		timestamps: {
			created: 456,
			updated: 456,
			submitted: 456,
		},
		pid: 'pid3',
		datasetVersion: '1.0.0',
		name: 'test3 v1',
		datasetv2: { summary: { publisher: { identifier: 'TestPublisher' }, abstract: 'abstract4' } },
		activeflag: 'draft',
		datasetfields: {
			metadataquality: {
				weighted_quality_score: 80,
			},
		},
		type: 'dataset',
	},
	{
		_id: ObjectID('615aee882414847722e46ac5'),
		timestamps: {
			created: 456,
			updated: 456,
			submitted: 456,
		},
		pid: 'pid4',
		datasetVersion: '1.0.0',
		name: 'test4 v1',
		datasetv2: { summary: { publisher: { identifier: 'TestPublisher' }, abstract: 'abstract5' } },
		activeflag: 'active',
		datasetfields: {
			metadataquality: {
				weighted_quality_score: 80,
			},
		},
		type: 'dataset',
	},
	{
		_id: ObjectID('615aee882414847722e46ac6'),
		timestamps: {
			created: 456,
			updated: 456,
			submitted: 456,
		},
		pid: 'pid5',
		datasetVersion: '1.0.0',
		name: 'test5 v1',
		datasetv2: { summary: { publisher: { identifier: 'TestPublisher' }, abstract: 'abstract6' } },
		activeflag: 'rejected',
		datasetfields: {
			metadataquality: {
				weighted_quality_score: 80,
			},
		},
		type: 'dataset',
	},
	{
		_id: ObjectID('615aee882414847722e46ac7'),
		timestamps: {
			created: 456,
			updated: 456,
			submitted: 456,
		},
		pid: 'pid6',
		datasetVersion: '1.0.0',
		name: 'test6 v1',
		datasetv2: { summary: { publisher: { identifier: 'TestPublisher' }, abstract: 'abstract7' } },
		activeflag: 'archive',
		datasetfields: {
			metadataquality: {
				weighted_quality_score: 80,
			},
		},
		type: 'dataset',
	},
	{
		_id: ObjectID('615aee882414847722e46ac8'),
		timestamps: {
			created: 456,
			updated: 456,
			submitted: 456,
		},
		pid: 'pid7',
		datasetid: 'exampleID',
		questionAnswers: '{ "properties/summary/title": "test6 v1" }',
		datasetVersion: '1.0.0',
		name: 'test2 v1',
		datasetv2: { summary: { publisher: { identifier: 'AnotherTestPublisher' }, abstract: 'test' } },
		activeflag: 'inReview',
		datasetfields: {
			metadataquality: {
				weighted_quality_score: 70,
			},
		},
		type: 'dataset',
		counter: 1,
	},
];
