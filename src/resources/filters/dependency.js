import FiltersRepository from './filters.repository';
import FiltersService from './filters.service';

export const filtersRepository = new FiltersRepository();
export const filtersService = new FiltersService(filtersRepository);
