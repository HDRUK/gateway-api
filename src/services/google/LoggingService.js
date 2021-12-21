const LoggerGCP = require('simple-gcp-logging');

export default class LoggingService {
    _logger;
    constructor() {
        this._logger = LoggerGCP.createLoggerGCP({
            projectId: process.env.LOGGING_PROJECT_ID,
            logName: process.env.LOGGING_LOG_NAME,
        });
    }

    sendDataInLogging(data, severity) {
        this._logger.setData(data);
        this._logger.setSeverity(severity);
        this._logger.writeLog();
    }
}