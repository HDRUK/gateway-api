const workflowModel = require('../workflow.model');
const workflow = require('../__mocks__/workflow');

describe('minSteps', () => {
    test('model validation requires at least one step', () => {
		expect(workflowModel.minSteps(workflow.steps)).toEqual(true);
		expect(workflowModel.minSteps([])).toEqual(false);
	});
});

describe('minReviewers', () => {
    test('model validation requires at least one reviewer in a step', () => {
		expect(workflowModel.minReviewers(workflow.steps[0].reviewers)).toEqual(true);
		expect(workflowModel.minReviewers([])).toEqual(false);
	});
});

describe('minSections', () => {
    test('model validation requires at least one section in a step', () => {
		expect(workflowModel.minSections(workflow.steps[0].sections)).toEqual(true);
		expect(workflowModel.minSections([])).toEqual(false);
	});
});