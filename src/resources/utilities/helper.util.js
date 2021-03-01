import crypto from 'crypto';

const _censorWord = str => {
	if (str.length === 1) return '*';
	else if (str.length === 2) return `${str[0]}*`;
	else return str[0] + '*'.repeat(str.length - 2) + str.slice(-1);
};

const _censorEmail = email => {
	let arr = email.split('@');
	return _censorWord(arr[0]) + '@' + _censorWord(arr[1]);
};

const _arraysEqual = (a, b) => {
	if (a === b) return true;
	if (a == null || b == null) return false;
	if (a.length !== b.length) return false;

	for (var i = 0; i < a.length; ++i) {
		if (a[i] !== b[i]) return false;
	}
	return true;
};

const _generateFriendlyId = id => {
	return id
		.toString()
		.toUpperCase()
		.match(/.{1,4}/g)
		.join('-');
};

const _generatedNumericId = () => {
	return parseInt(Math.random().toString().replace('0.', ''));
};

const _generateAlphaNumericString = length => {
	return crypto.randomBytes(length).toString('hex').substring(length);
};

const _hidePrivateProfileDetails = persons => {
	return persons.map(person => {
		let personWithPrivateDetailsRemoved = person;

		personWithPrivateDetailsRemoved.bio = person.showBio ? person.bio : '';
		personWithPrivateDetailsRemoved.organisation = person.showOrganisation ? person.organisation : '';
		personWithPrivateDetailsRemoved.sector = person.showSector ? person.sector : '';
		personWithPrivateDetailsRemoved.domain = person.showDomain ? person.domain : '';
		personWithPrivateDetailsRemoved.link = person.showLink ? person.link : '';
		personWithPrivateDetailsRemoved.orcid = person.showOrcid ? person.orcid : '';

		return personWithPrivateDetailsRemoved;
	});
};

const _getEnvironment = () => {
	let environment = '';

	switch (process.env.api_url) {
		case 'https://api.latest.healthdatagateway.org':
			environment = 'latest';
			break;
		case 'https://api.uatbeta.healthdatagateway.org':
			environment = 'uatbeta';
			break;
		case 'https://api.uat.healthdatagateway.org':
			environment = 'uat';
			break;
		case 'https://api.uat2.healthdatagateway.org':
			environment = 'uat2';
			break;
		case 'https://api.preprod.healthdatagateway.org':
			environment = 'preprod';
			break;
		case 'https://api.www.healthdatagateway.org':
			environment = 'prod';
			break;
		default:
			environment = 'local';
	}

	return environment;
};

export default {
	censorEmail: _censorEmail,
	arraysEqual: _arraysEqual,
	generateFriendlyId: _generateFriendlyId,
	generatedNumericId: _generatedNumericId,
	generateAlphaNumericString: _generateAlphaNumericString,
	hidePrivateProfileDetails: _hidePrivateProfileDetails,
	getEnvironment: _getEnvironment,
};
