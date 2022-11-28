//import { UserModel } from '../src/resources/user/user.model';

/**
 * Make any changes you need to make to the database here
 */
async function up() {
	// Write migration here
	//await UserModel.findOneAndUpdate({ email: 'robin.kavanagh@paconsulting.com' }, { firstname: 'robin2' });
	process.stdout.write(`Sample migration ran successfully\n`);
}

/**
 * Make any changes that UNDO the up function side effects here (if possible)
 */
async function down() {
	// Write migration here
	//await UserModel.findOneAndUpdate({ email: 'robin.kavanagh@paconsulting.com' }, { firstname: 'robin' });
}

module.exports = { up, down };
