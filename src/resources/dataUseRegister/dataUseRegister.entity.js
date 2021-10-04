import Entity from '../base/entity';

export default class DataUseRegisterClass extends Entity {
	constructor(obj) {
		super();
		if(!obj.id) obj.id = this.generateId();
		obj.type = 'dataUseRegister';
		Object.assign(this, obj);
	}
}
