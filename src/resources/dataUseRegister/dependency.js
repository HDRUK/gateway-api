import DataUseRegisterRepository from './dataUseRegister.repository';
import DataUseRegisterService from './dataUseRegister.service';

export const dataUseRegisterRepository = new DataUseRegisterRepository();
export const dataUseRegisterService = new DataUseRegisterService(dataUseRegisterRepository);
