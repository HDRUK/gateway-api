module.exports = {
	'/api/v1/datasets/{datasetID}': {
		get: {
			summary: 'Returns Dataset object.',
			tags: ['Datasets'],
			parameters: [
				{
					in: 'path',
					name: 'datasetID',
					required: true,
					description: 'The ID of the datset',
					schema: {
						type: 'string',
						example: '756daeaa-6e47-4269-9df5-477c01cdd271',
					},
				},
			],
			responses: {
				200: {
					description: 'OK',
				},
			},
		},
	},
	'/api/v1/datasets': {
		get: {
			summary: 'Returns List of Dataset objects.',
			tags: ['Datasets'],
			parameters: [
				{
					in: 'query',
					name: 'limit',
					required: false,
					description: 'Limit the number of results',
					schema: {
						type: 'integer',
						example: 3,
					},
				},
				{
					in: 'query',
					name: 'offset',
					required: false,
					description: 'Index to offset the search results',
					schema: {
						type: 'integer',
						example: 1,
					},
				},
				{
					in: 'query',
					name: 'q',
					required: false,
					description: 'Filter using search query',
					schema: {
						type: 'string',
						example: 'epilepsy',
					},
				},
			],
			responses: {
				200: {
					description: 'OK',
				},
			},
		},
	},
	'/api/v1/dataset-onboarding/publisher/{publisherID}': {
		get: {
			summary: 'Returns a list of datasets for a given publisher, or the admin team, as per the supplied query parameters.',
			security: [
				{
					cookieAuth: [],
				},
			],
			tags: ['Datasets'],
			parameters: [
				{
					in: 'path',
					name: 'publisherID',
					required: true,
					description: `The ID of the publisher, or 'admin' for the HDR UK admin team`,
					schema: {
						type: 'string',
						example: '5f3f98068af2ef61552e1d75',
					},
				},
				{
					in: 'query',
					name: 'search',
					required: false,
					description: `A search string used to filter the publisher's datasets. String is matched against the publisher name, dataset abstract and dataset title.`,
					schema: {
						type: 'string',
						example: 'covid',
					},
				},
				{
					in: 'query',
					name: 'status',
					required: false,
					description: `Filter the results by a given dataset status. Note that if the status parameter is not given, all dataset types are returned in the search results. If the publisherID is 'admin', only 'inReview' is an acceptable status.`,
					schema: {
						type: 'string',
						example: 'inReview',
						enum: ['active', 'rejected', 'inReview', 'draft', 'archive'],
					},
				},
				{
					in: 'query',
					name: 'sortBy',
					required: false,
					description: `The parameter to sort the dataset results by. Links to the 'sortDirection' which controls whether the sort is ascending or descending. Note that sorting by popularity is only applicable when 'status=active'. Defaults to 'latest' if no 'sortBy' parameter is given in the request.`,
					schema: {
						type: 'string',
						example: 'latest',
						enum: ['latest', 'alphabetic', 'recentlyadded', 'metadata', 'popularity'],
					},
				},
				{
					in: 'query',
					name: 'sortDirection',
					required: false,
					description: `Controls the sort direction (i.e., ascending or descending). Defaults to 'desc' if no 'sortDirection' parameter is given in the initial request.`,
					schema: {
						type: 'string',
						example: 'desc',
						enum: ['asc', 'desc'],
					},
				},
			],
			description: `Returns a list of datasets for either a publisher team or the HDR administration team based on supplied query parameters. All query parameters are optional. If none are given, all datasets associated with the publisher team, regardless of the dataset status, are returned (sorted by latest, descending) One exception is for 'publisherID=admin', in which case only 'inReview' datasets are returned.`,
			responses: {
				200: {
					description: 'Successful API response',
				},
				401: {
					description: 'Unauthorised - see message in response',
				},
				500: {
					description: 'Server error - see message in response',
				},
			},
		},
	},
	'/api/v2/datasets': {
		get: {
			summary: 'Returns a list of dataset objects',
			tags: ['Datasets v2.0'],
			description:
				"Version 2.0 of the datasets API introduces a large number of parameterised query string options to aid requests in collecting the data that is most relevant for a given use case.  The query parameters defined below support a variety of comparison operators such as equals, contains, greater than, and less than.  Using dot notation, any field can be queried, please see some examples below.  Note - This response is limited to 100 records by default.  Please use the 'page' query parameter to access records beyond the first 100.  The 'limit' query parameter can therefore only be specified up to a maximum of 100.",
			parameters: [
				{
					name: 'search',
					in: 'query',
					description:
						'Full text index search function which searches for partial matches in various dataset fields including name, description and abstract.  The response will contain a metascore indicating the relevancy of the match, by default results are sorted by the most relevant first unless a manual sort query parameter has been added.',
					schema: {
						type: 'string',
					},
					example: 'COVID-19',
				},
				{
					name: 'page',
					in: 'query',
					description: 'A specific page of results to retrieve',
					schema: {
						type: 'number',
					},
					example: 1,
				},
				{
					name: 'limit',
					in: 'query',
					description: 'Maximum number of results returned per page',
					schema: {
						type: 'number',
					},
					example: 10,
				},
				{
					name: 'sort',
					in: 'query',
					description:
						'Fields to apply sort operations to.  Accepts multiple fields in ascending and descending.  E.g. name for ascending or -name for descending.  Multiple fields should be comma separated as shown in the example below.',
					schema: {
						type: 'string',
					},
					example: 'datasetfields.publisher,name,-counter',
				},
				{
					name: 'fields',
					in: 'query',
					description:
						'Limit the size of the response by requesting only certain fields.  Note that some additional derived fields are always returned.  Multiple fields should be comma separate as shown in the example below.',
					schema: {
						type: 'string',
					},
					example: 'name,counter,datasetid',
				},
				{
					name: 'count',
					in: 'query',
					description: 'Returns the number of the number of entities matching the query parameters provided instead of the result payload',
					schema: {
						type: 'boolean',
					},
					example: true,
				},
				{
					name: 'datasetid',
					in: 'query',
					description: 'Filter by the unique identifier for a single version of a dataset',
					schema: {
						type: 'string',
					},
					example: '0cfe60cd-038d-4c03-9a95-894c52135922',
				},
				{
					name: 'pid',
					in: 'query',
					description: 'Filter by the identifier for a dataset that persists across versions',
					schema: {
						type: 'string',
					},
					example: '621dd611-adcf-4434-b538-eecdbe5f72cf',
				},
				{
					name: 'name',
					in: 'query',
					description: 'Filter by dataset name',
					schema: {
						type: 'string',
					},
					example: 'ARIA Dataset',
				},
				{
					name: 'activeflag',
					in: 'query',
					description: 'Filter by the status of a single dataset version',
					schema: {
						type: 'string',
						enum: ['active', 'archive'],
					},
					example: 'active',
				},
				{
					name: 'datasetfields.publisher',
					in: 'query',
					description: 'Filter by the name of the Custodian holding the dataset',
					schema: {
						type: 'string',
					},
					example: 'ALLIANCE > BARTS HEALTH NHS TRUST',
				},
				{
					name: 'metadataquality.completeness_percent[gte]',
					in: 'query',
					description:
						'Filter by the metadata quality completeness percentage using an operator [gte] for greater than or equal to, [gt] for greater than, [lte] for less than or equal to, [lt] for less than, and [eq] for equal to.',
					schema: {
						type: 'number',
					},
					example: 90.5,
				},
				{
					name: 'metadataquality.weighted_completeness_percent[gte]',
					in: 'query',
					description:
						'Filter by the metadata quality weighted completeness percentage using an operator [gte] for greater than or equal to, [gt] for greater than, [lte] for less than or equal to, [lt] for less than, and [eq] for equal to.',
					schema: {
						type: 'number',
					},
					example: 71.2,
				},
				{
					name: 'metadataquality.weighted_quality_score[gte]',
					in: 'query',
					description:
						'Filter by the metadata quality score using an operator [gte] for greater than or equal to, [gt] for greater than, [lte] for less than or equal to, [lt] for less than, and [eq] for equal to.',
					schema: {
						type: 'number',
					},
					example: 35.3,
				},
			],
			responses: {
				200: {
					description: 'Successful response containing a list of datasets matching query parameters',
				},
			},
		},
	},
	'/api/v2/datasets/{datasetid}': {
		get: {
			summary: 'Returns a dataset object.',
			tags: ['Datasets v2.0'],
			parameters: [
				{
					in: 'path',
					name: 'datasetid',
					required: true,
					description: 'The unqiue identifier for a specific version of a dataset',
					schema: {
						type: 'string',
						example: 'af20ebb2-018a-4557-8ced-0bec75dba150',
					},
				},
				{
					in: 'query',
					name: 'raw',
					required: false,
					description:
						'A flag which determines if the response triggered is the raw structure in which the data is stored rather than the dataset v2.0 standard',
					schema: {
						type: 'boolean',
						example: false,
					},
				},
			],
			description:
				'Version 2.0 of the datasets API introduces the agreed dataset v2.0 schema as defined at the following link -  https://github.com/HDRUK/schemata/edit/master/schema/dataset/2.0.0/dataset.schema.json',
			responses: {
				200: {
					description: 'Successful response containing a single dataset object',
				},
				404: {
					description: 'A dataset could not be found by the provided dataset identifier',
				},
			},
		},
	},
};
