const urlValidator = require('../urlValidator');

const validDOILinks = [
	'https://doi.org/10.1136/bmjresp-2020-000644',
	'https://dx.doi.org/123',
	'http://doi.org/123',
	'http://dx.doi.org/123',
	'doi.org/123',
	'dx.doi.org/',
];
const inValidDOILinks = [
	'http://www.doi.org/123',
	'www.dx.doi.org/',
	'doi.com.org/4',
	'https://dx.doi.com/123',
	'www.bbc.co.uk',
	'doi',
	'123',
	'',
];
describe('should validate DOI links', () => {
	test('Valid DOI links return true', () => {
		validDOILinks.forEach(link => {
			expect(urlValidator.isDOILink(link)).toEqual(true);
		});
	});

	test('Invalid DOI links return false', () => {
		inValidDOILinks.forEach(link => {
			expect(urlValidator.isDOILink(link)).toEqual(false);
		});
	});
});
