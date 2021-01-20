const validateURL = link => {
	if (link && !/^https?:\/\//i.test(link)) {
		link = 'https://' + link;
	}
	return link;
};

const validateOrcidURL = link => {
	if (!/^https?:\/\/orcid.org\//i.test(link)) {
		link = 'https://orcid.org/' + link;
	}
	return link;
};

const _isDOILink = link => {
	return /^(?:(http)(s)?(:\/\/))?(dx.)?doi.org\/([\w.\/-]*)/i.test(link);
};

module.exports = {
	validateURL: validateURL,
	validateOrcidURL: validateOrcidURL,
	isDOILink: _isDOILink,
};
