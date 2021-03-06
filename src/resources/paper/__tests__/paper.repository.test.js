import sinon from 'sinon';

import PaperRepository from '../paper.repository';
import { papersStub } from '../__mocks__/papers';

describe('PaperRepository', function () {
	describe('getPaper', function () {
		it('should return a paper by a specified id', async function () {
			const paperStub = papersStub[0];
			const paperRepository = new PaperRepository();
			const stub = sinon.stub(paperRepository, 'findOne').returns(paperStub);
			const paper = await paperRepository.getPaper(paperStub.id);

			expect(stub.calledOnce).toBe(true);

			expect(paper.type).toEqual(paperStub.type);
			expect(paper.id).toEqual(paperStub.id);
			expect(paper.name).toEqual(paperStub.name);
			expect(paper.description).toEqual(paperStub.description);
			expect(paper.resultsInsights).toEqual(paperStub.resultsInsights);
			expect(paper.paperid).toEqual(paperStub.paperid);
			expect(paper.categories).toEqual(paperStub.categories);
			expect(paper.license).toEqual(paperStub.license);
			expect(paper.authors).toEqual(paperStub.authors);
			expect(paper.activeflag).toEqual(paperStub.activeflag);
			expect(paper.counter).toEqual(paperStub.counter);
			expect(paper.discourseTopicId).toEqual(paperStub.discourseTopicId);
			expect(paper.relatedObjects).toEqual(paperStub.relatedObjects);
			expect(paper.uploader).toEqual(paperStub.uploader);
			expect(paper.journal).toEqual(paperStub.journal);
			expect(paper.journalYear).toEqual(paperStub.journalYear);
			expect(paper.isPreprint).toEqual(paperStub.isPreprint);
		});
	});

	describe('getPapers', function () {
		it('should return an array of papers', async function () {
			const paperRepository = new PaperRepository();
			const stub = sinon.stub(paperRepository, 'find').returns(papersStub);
			const papers = await paperRepository.getPapers();

			expect(stub.calledOnce).toBe(true);

			expect(papers.length).toBeGreaterThan(0);
		});
	});

	describe('findCountOf', function () {
		it('should return the number of documents found by a given query', async function () {
			const paperRepository = new PaperRepository();
			const stub = sinon.stub(paperRepository, 'findCountOf').returns(1);
			const paperCount = await paperRepository.findCountOf({ name: 'Admitted Patient Care Paper' });
			
			expect(stub.calledOnce).toBe(true);

			expect(paperCount).toEqual(1);
		});
	});
});