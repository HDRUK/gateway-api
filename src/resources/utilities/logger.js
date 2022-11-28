import constants from './constants.util';

const logRequestMiddleware = options => {
	return (req, res, next) => {
		const { logCategory, action } = options;
		logger.logUserActivity(req.user, logCategory, constants.logTypes.USER, { action });
		next();
	};
};

const logSystemActivity = options => {
	const { category = 'Action not categorised', action = 'Action not described' } = options;
	process.stdout.write(`logSystemActivity : action ${action}, category ${category}`);
	// Save to database
};

const logUserActivity = (user, category, type, context) => {
	const { action } = context;
	process.stdout.write(`logUserActivity - action: ${action}`);
	// Log date/time
	// Log action
	// Log if user was logged in
	// Log userId and _id
	// Save to database
};

const logError = (err, category) => {
	process.stdout.write(`The following error occurred: ${err.message}`);
};

export const logger = {
	logRequestMiddleware,
	logSystemActivity,
	logUserActivity,
	logError,
};
