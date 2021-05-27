import emailGeneratorUtil from '../utilities/emailGenerator.util';
import { UserModel } from './user.model';

export async function createUser({ firstname, lastname, email, providerId, provider, role }) {
	return new Promise(async (resolve, reject) => {
		const id = parseInt(Math.random().toString().replace('0.', ''));
		// create new user from details from provider
		const user = await UserModel.create({
			id,
			providerId,
			provider,
			firstname,
			lastname,
			email,
			role,
		});
		// if a user has been created send new introduction email
		if(user) {
			const msg = {
				to: email,
				from: 'gateway@hdruk.ac.uk',
				templateId: process.env.SENDGRID_INTRO_EMAIL
			}
			emailGeneratorUtil.sendIntroEmail(msg);
		}
		// return user via promise
		return resolve(user);
	});
}

export async function updateUser({ id, firstname, lastname, email, discourseKey, discourseUsername, feedback, news }) {
	return new Promise(async (resolve, reject) => {
		return resolve(
			await UserModel.findOneAndUpdate(
				{ id: id },
				{
					firstname,
					lastname,
					email,
					discourseKey,
					discourseUsername,
					feedback, 
					news
				}
			)
		);
	});
}

export async function updateRedirectURL({ id, redirectURL }) {
	return new Promise(async (resolve, reject) => {
		return resolve(
			await UserModel.findOneAndUpdate(
				{ id: id },
				{
					redirectURL: redirectURL,
				}
			)
		);
	});
}
