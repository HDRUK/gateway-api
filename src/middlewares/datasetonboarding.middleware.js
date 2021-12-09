import constants from '../resources/utilities/constants.util';

const authoriseUserForPublisher = (req, res, next) => {
	const isAdminUser = req.user.teams.map(team => team.type).includes(constants.teamTypes.ADMIN);

	if (!isAdminUser) {
		const isCustodianUser = req.user.teams.map(team => team.type).includes(constants.teamTypes.PUBLISHER)
			? req.user.teams.map(team => team.publisher._id.toString()).includes(req.params.publisherID.toString())
			: false;

		if (!isCustodianUser || req.params.publisherID === constants.teamTypes.ADMIN) {
			return res.status(401).json({
				success: false,
				message: 'You are not authorised to view these datasets',
			});
		}
	}
	next();
};

const validateSearchParameters = (req, res, next) => {
	const sortOptions = Object.values(constants.datasetSortOptions);
	const datasetStatuses = Object.values(constants.datasetStatuses);

	let {
		query: { search = '', sortBy = process.env.API_DEFAULT_SORT_OPTION, sortDirection = process.env.API_DEFAULT_SORT_DIRECTION, status },
	} = req;

	if (req.params.publisherID === constants.teamTypes.ADMIN) {
		if (!status) status = 'inReview';
		if (status !== 'inReview') {
			return res.status(401).json({
				success: false,
				message: 'Only inReview datasets can be accessed by the admin team',
			});
		}
	} else {
		if (status && !datasetStatuses.includes(status)) {
			return res.status(500).json({
				success: false,
				message: `The status parameter must be one of [${datasetStatuses.join(', ')}]`,
			});
		}
	}

	if (!sortOptions.includes(sortBy)) {
		return res.status(500).json({
			success: false,
			message: `The sort parameter must be one of [${sortOptions.join(', ')}]`,
		});
	}

	if (!['asc', 'desc'].includes(sortDirection)) {
		return res.status(500).json({
			success: false,
			message: `The sort direction must be either ascending [asc] or descending [desc]`,
		});
	}

	if (sortBy === constants.datasetSortOptions.MOSTVIEWED && status !== constants.datasetStatuses.ACTIVE) {
		return res.status(500).json({
			success: false,
			message: `Sorting by popularity is only available for active datasets [status=active]`,
		});
	}

	req.query = {
		search: search.replace(/[-"@.*+/?^${}()|[\]\\]/g, ''),
		sortBy: sortBy,
		sortDirection: sortDirection,
		status: status,
	};

	next();
};

export { authoriseUserForPublisher, validateSearchParameters };
