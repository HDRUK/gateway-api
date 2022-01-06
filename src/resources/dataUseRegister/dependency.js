import DataUseRegisterRepository from './dataUseRegister.repository';
import DataUseRegisterService from './dataUseRegister.service';
import DataUseRegisterController from './dataUseRegister.controller';

export const dataUseRegisterController = new DataUseRegisterController();
export const dataUseRegisterRepository = new DataUseRegisterRepository();
export const dataUseRegisterService = new DataUseRegisterService(dataUseRegisterRepository);
