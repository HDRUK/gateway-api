import QuestionbankService from './questionbank.service';
import { publisherService } from '../publisher/dependency';
import { globalService } from '../global/dependency';
import { datasetService } from '../dataset/dependency';
import DataRequestRepository from '../datarequest/datarequest.repository';

export const dataRequestRepository = new DataRequestRepository();
export const questionbankService = new QuestionbankService(publisherService, globalService, dataRequestRepository, datasetService);
