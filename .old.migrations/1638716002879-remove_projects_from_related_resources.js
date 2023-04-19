import { Data } from '../src/resources/tool/data.model';
import { Collections } from '../src/resources/collections/collections.model';
import { Course } from '../src/resources/course/course.model';

/**
 * Make any changes you need to make to the database here
 */
async function up() {
	//Remove projects that are in related resources for tools and papers
	await Data.update({ 'relatedObjects.objectType': 'project' }, { $pull: { relatedObjects: { objectType: 'project' } } }, { multi: true });
	//Remove projects that are in related resources for collections
	await Collections.update(
		{ 'relatedObjects.objectType': 'project' },
		{ $pull: { relatedObjects: { objectType: 'project' } } },
		{ multi: true }
	);
	//Remove projects that are in related resources for courses
	await Course.update(
		{ 'relatedObjects.objectType': 'project' },
		{ $pull: { relatedObjects: { objectType: 'project' } } },
		{ multi: true }
	);
}

/**
 * Make any changes that UNDO the up function side effects here (if possible)
 */
async function down() {
	// Write migration here
}

module.exports = { up, down };
