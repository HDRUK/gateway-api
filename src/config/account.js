import { getUserByUserId } from '../resources/user/user.repository';
import ga4ghUtils from '../resources/utilities/ga4gh.utils';
import { to } from 'await-to-js';
import _ from 'lodash';

const store = new Map();
const logins = new Map();
const { nanoid } = require('nanoid');

class Account {
	constructor(id, profile) {
		this.accountId = id || nanoid();
		this.profile = profile;
		store.set(this.accountId, this);
	}

	/**
	 * @param use - can either be "id_token" or "userinfo", depending on
	 *   where the specific claims are intended to be put in.
	 * @param scope - the intended scope, while oidc-provider will mask
	 *   claims depending on the scope automatically you might want to skip
	 *   loading some claims from external resources etc. based on this detail
	 *   or not return them in id tokens but only userinfo and so on.
	 */
	async claims(use, scope) {
		let claimsToSend = scope.split(' ');
		// eslint-disable-line no-unused-vars
		let claim = {
			sub: this.accountId, // it is essential to always return a sub claim
		};

		let [, user] = await to(getUserByUserId(parseInt(this.accountId)));
		if (!_.isNil(user)) {
			if (claimsToSend.includes('profile')) {
				claim.firstname = user.firstname;
				claim.lastname = user.lastname;
			}
			if (claimsToSend.includes('email')) {
				claim.email = user.email;
			}
			if (claimsToSend.includes('rquestroles')) {
				claim.rquestroles = user.advancedSearchRoles;
			}
			if (claimsToSend.includes('ga4gh_passport_v1')) {
				claim.ga4gh_passport_v1 = await ga4ghUtils.buildGa4ghVisas(user);
			}
		}

		return claim;
	}

	static async findByFederated(provider, claims) {
		const id = `${provider}.${claims.sub}`;
		if (!logins.get(id)) {
			logins.set(id, new Account(id, claims));
		}
		return logins.get(id);
	}

	static async findByLogin(login) {
		if (!logins.get(login)) {
			logins.set(login, new Account(login));
		}

		return logins.get(login);
	}

	static async findAccount(ctx, id) {
		// eslint-disable-line no-unused-vars
		// token is a reference to the token used for which a given account is being loaded,
		//   it is undefined in scenarios where account claims are returned from authorization endpoint
		// ctx is the koa request context
		if (!store.get(id)) {
			let [, user] = await to(getUserByUserId(parseInt(id)));
			new Account(id, user); // eslint-disable-line no-new
		}
		return store.get(id);
	}
}

module.exports = Account;
