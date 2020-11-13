import { EventLogModel } from './eventlog.model';
import _ from 'lodash';

module.exports = {
    logEvent: async (req, res) => {

        const { 
            userId = '', 
            email = '', 
            event = '', 
            provider = '', 
            providerId = '', 
            timestamp = Date.now(), 
        } = req;

        await EventLogModel.create({
            userId,
            email,
            event,
            provider,
            providerId,
            timestamp
        });
    }
}