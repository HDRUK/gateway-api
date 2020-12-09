import mongoose from 'mongoose';

module.exports = {
	applicant : {
		_id: new mongoose.Types.ObjectId(),
		firstname: 'test',
		lastname: 'applicant 1'
	},
	collaborator : {
		_id: new mongoose.Types.ObjectId(),
		firstname: 'test',
		lastname: 'collaborator 1'
	},
	custodian : {
		_id: new mongoose.Types.ObjectId(),
		firstname: 'test',
		lastname: 'custodian 1'
	}
}
