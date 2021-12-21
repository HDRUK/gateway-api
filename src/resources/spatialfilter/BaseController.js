import { BigQueryService, LoggingService } from '../../services/index';

class BaseController {
    _logger;
    _bigQuery;
    constructor() {
        this._logger = new LoggingService();
        this._bigQuery = new BigQueryService();
    }
}

module.exports = BaseController;