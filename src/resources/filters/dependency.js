import FiltersRepository from './filters.repository';
import FiltersService from './filters.service';
import DatasetRepository from '../dataset/dataset.repository';

const datasetRepository = new DatasetRepository();

export const filtersRepository = new FiltersRepository();
export const filtersService = new FiltersService(filtersRepository, datasetRepository);
