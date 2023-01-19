
import { LoggingService } from "../services";

const errorHandler = (error, req, res, next) => {
	const errorStatusCode = error.status || 500;
	const loggingService = new LoggingService();
	const loggingEnabled = parseInt(process.env.LOGGING_LOG_ENABLED) || 0;

	const errorMessage = {
		type: 'error',
		message: error.message,
		stack: error.stack.split("\n"),
	};

	if (loggingEnabled) {
		loggingService.sendDataInLogging(errorMessage, 'ERROR');
	}

	res.status(errorStatusCode).json(errorMessage);

	return;
}

export { errorHandler }