import StatsRepository from '../stats.repository';
import { topSearchTerms } from '../__mocks__/topSearchTerms';
describe('StatsRepository', function () {
	describe('getDuplicateTerms for entityType "dataset"', function () {
		it('should return array of duplicate terms and contains term "covid"', async function () {
			const statsRepository = new StatsRepository();
			const terms = await statsRepository.getDuplicateTerms('dataset', topSearchTerms);
			expect(terms).toEqual(expect.arrayContaining(['covid', 'cancer', 'epilepsy', '']));
			expect(terms.length).toBeGreaterThan(0);
		});
	});
	describe('getDuplicateTerms for entityType "tool"', function () {
		it('should return array of duplicate terms and contains term "epilepsy"', async function () {
			const statsRepository = new StatsRepository();
			const terms = await statsRepository.getDuplicateTerms('tool', topSearchTerms);
			expect(terms).toEqual(expect.arrayContaining(['test', 'epilepsy', '']));
			expect(terms.length).toBeGreaterThan(0);
		});
	});

	describe('getDuplicateTerms for entityType "paper"', function () {
		it('should return array of duplicate terms and contains term "epilepsy"', async function () {
			const statsRepository = new StatsRepository();
			const terms = await statsRepository.getDuplicateTerms('paper', topSearchTerms);
			expect(terms).toEqual(expect.arrayContaining(['test', 'covid', 'barts', 'cancer', 'epilepsy', '']));
			expect(terms.length).toBeGreaterThan(0);
		});
	});

	describe('getDuplicateTerms for entityType "test"', function () {
		it('should return array of duplicate terms with a empty string ""', async function () {
			const statsRepository = new StatsRepository();
			const terms = await statsRepository.getDuplicateTerms('test', topSearchTerms);
			expect(['']).toEqual(expect.arrayContaining(terms));
			expect(terms.length).toBeGreaterThan(0);
		});
	});
});
