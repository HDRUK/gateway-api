import FiltersRepository from './filters.repository';
import FiltersService from './filters.service';
import DatasetRepository from '../dataset/dataset.repository';
import ToolRepository from '../tool/v2//tool.repository';
import ProjectRepository from '../project/project.repository';
import PaperRepository from '../paper/paper.repository';
import CollectionsRepository from '../collections/v2/collection.repository';
import CourseRepository from '../course/v2/course.repository';
import DataUseRegisterRepository from '../dataUseRegister/dataUseRegister.repository';

const datasetRepository = new DatasetRepository();
const toolRepository = new ToolRepository();
const projectRepository = new ProjectRepository();
const paperRepository = new PaperRepository();
const collectionsRepository = new CollectionsRepository();
const courseRepository = new CourseRepository();
const dataUseRegisterRepository = new DataUseRegisterRepository();

export const filtersRepository = new FiltersRepository();
export const filtersService = new FiltersService(
	filtersRepository,
	datasetRepository,
	toolRepository,
	projectRepository,
	paperRepository,
	collectionsRepository,
	courseRepository,
	dataUseRegisterRepository
);
