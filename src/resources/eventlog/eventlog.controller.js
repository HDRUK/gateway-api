import { EventLogModel } from './eventlog.model';
import _ from 'lodash';

module.exports = {
    logEvent: async (event) => await EventLogModel.create({...event})
}