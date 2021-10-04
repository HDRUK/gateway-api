import { isNil } from 'lodash';
import bcrypt from 'bcrypt';

import { UserModel } from './user.model';
import { TeamModel } from '../team/team.model';
import helper from '../utilities/helper.util';

export async function getUserById(id) {
	const user = await UserModel.findById(id).populate({
		path: 'teams',
		select: 'publisher type members -_id',
		populate: {
			path: 'publisher',
			select: 'name',
		},
	});
	return user;
}

export async function getUserByProviderId(providerId) {
	return await UserModel.findOne({ providerId }).exec();
}

export async function getUserByUserId(id) {
	return await UserModel.findOne({ id }).exec();
}

export async function getUsersByIds(userIds) {
	return await UserModel.find({ id: { $in: userIds } }, '_id').lean();
}

export async function getServiceAccountByClientCredentials(clientId, clientSecret) {
	// 1. Locate service account by clientId, return undefined if no document located
	const id = clientId.toString();
	const serviceAccount = await UserModel.findOne({ clientId: id, isServiceAccount: true });
	if (isNil(serviceAccount)) {
		return;
	}
	// 2. Extract hashed client secret from DB
	const { clientSecret: hashedClientSecret = '' } = serviceAccount;
	// 3. Compare client secret to hashed client secret to check for auth match
	const match = await bcrypt.compare(clientSecret, hashedClientSecret);
	// 4. Return the service account if matched
	if (match) {
		return serviceAccount;
	}
	// 5. Return undefined if secret did not match
	return;
}

export async function createServiceAccount(firstname, lastname, email, teamId) {
	// 1. Set up default params
	const isServiceAccount = true,
		role = 'creator',
		teamRole = 'manager',
		providerId = 'Service Account';
	// 2. Ensure team is valid before creating service account
	const id = teamId.toString();
	const team = await TeamModel.findById(id);
	// 3. Return undefined if no team found
	if (isNil(team)) {
		return;
	}
	// 4. Generate Client Id and Client Secret
	const clientId = helper.generateAlphaNumericString(15);
	const clientSecret = helper.generateAlphaNumericString(50);
	// 5. Hash Client Secret for storage in DB
	const saltRounds = 10;
	const hashedClientSecret = await bcrypt.hash(clientSecret, saltRounds);
	// 6. Create service account user with the hashed Client Secret
	const serviceAccount = await UserModel.create({
		role,
		isServiceAccount,
		providerId,
		firstname,
		lastname,
		email,
		clientId,
		clientSecret: hashedClientSecret,
	});
	// 7. Create membership for service account to team
	const newMember = {
		memberid: serviceAccount._id,
		roles: [teamRole],
	};
	// 8. Add membership for the service account to the team
	TeamModel.update({ _id: team._id }, { $push: { members: newMember } });
	// 9. Reinstate unhashed client secret for single return instance
	serviceAccount.clientSecret = clientSecret;
	// 10. Return service account details
	return serviceAccount;
}
