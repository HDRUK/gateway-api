import DatarequestschemaRepository from './datarequest.schema.repository';
import DatarequestschemaService from './datarequest.schema.service';

export const datarequestschemaRepository = new DatarequestschemaRepository();
export const datarequestschemaService = new DatarequestschemaService(datarequestschemaRepository);
