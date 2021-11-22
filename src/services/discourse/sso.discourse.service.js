import discourse_sso from 'discourse-sso';

export function discourseLogin(payload, sig, user) {
	const sso = new discourse_sso(process.env.DISCOURSE_SSO_SECRET);

	if (!sso.validate(payload, sig)) {
		throw Error(`Error validating Discourse SSO payload for user with id: ${user.id}.`);
	}

	const nonce = sso.getNonce(payload);
	const userparams = {
		nonce: nonce,
		external_id: user.id,
		email: user.email,
		username: `${user.firstname.toLowerCase()}.${user.lastname.toLowerCase()}`,
		name: `${user.firstname} ${user.lastname}`,
	};

	const q = sso.buildLoginString(userparams);

	return `${process.env.DISCOURSE_URL}/session/sso_login?${q}`;
}
