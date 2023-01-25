
import { LoggingService } from "../services";

const errorHandler = (error, req, res, next) => {
	const errorStatusCode = error.status || 500;
	const loggingService = new LoggingService();
	const loggingEnabled = parseInt(process.env.LOGGING_LOG_ENABLED) || 0;

	const errorResponseMessage = {
		type: 'error',
		message: error.message,
	};
	const errorFullMessage = {
		type: 'error',
		message: error.message,
		stack: error.stack.split("\n"),
	};

	process.stdout.write(JSON.stringify(errorFullMessage));
	
	if (loggingEnabled) {
		loggingService.sendDataInLogging(errorFullMessage, 'ERROR');
	}

	res.status(errorStatusCode).json(errorResponseMessage);

	return;
}

export { errorHandler }