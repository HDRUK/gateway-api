import { isEmpty, isNull, isEqual } from 'lodash';

import constants from '../resources/utilities/constants.util';
import { dataUseRegisterService } from '../resources/dataUseRegister/dependency';

const _isUserMemberOfTeam = (user, teamId) => {
	let { teams } = user;
	return teams.filter(team => !isNull(team.publisher)).some(team => team.publisher._id.equals(teamId));
};

const _isUserDataUseAdmin = user => {
	let { teams } = user;

	if (teams) {
		teams = teams.map(team => {
			let { publisher, type, members } = team;
			let member = members.find(member => {
				return member.memberid.toString() === user._id.toString();
			});
			let { roles } = member;
			return { ...publisher, type, roles };
		});
	}

	return teams
		.filter(team => team.type === constants.teamTypes.ADMIN)
		.some(team => team.roles.includes(constants.roleTypes.ADMIN_DATA_USE));
};

const validateUpdateRequest = (req, res, next) => {
	const { id } = req.params;

	if (!id) {
		return res.status(400).json({
			success: false,
			message: 'You must provide a data user register identifier',
		});
	}

	next();
};

const validateUploadRequest = (req, res, next) => {
	const { teamId, dataUses } = req.body;
	let errors = [];

	if (!teamId) {
		errors.push('You must provide the custodian team identifier to associate the data uses to');
	}

	if (!dataUses || isEmpty(dataUses)) {
		errors.push('You must provide data uses to upload');
	}

	if (!isEmpty(errors)) {
		return res.status(400).json({
			success: false,
			message: errors.join(', '),
		});
	}

	next();
};

const authorizeUpdate = async (req, res, next) => {
	const requestingUser = req.user;
	const { id } = req.params;
	const { projectIdText, datasetTitles } = req.body;

	const dataUseRegister = await dataUseRegisterService.getDataUseRegister(id);

	if (!dataUseRegister) {
		return res.status(404).json({
			success: false,
			message: 'The requested data use register entry could not be found',
		});
	}

	const { publisher } = dataUseRegister;
	const isAuthor = dataUseRegister.gatewayApplicants.includes(requestingUser._id);
	const authorised = _isUserDataUseAdmin(requestingUser) || _isUserMemberOfTeam(requestingUser, publisher._id) || isAuthor;
	if (!authorised) {
		return res.status(401).json({
			success: false,
			message: 'You are not authorised to perform this action',
		});
	}

	if (!dataUseRegister.manualUpload) {
		if (projectIdText && !isEqual(projectIdText, dataUseRegister.projectIdText))
			return res.status(401).json({
				success: false,
				message: 'You are not authorised to update the project ID of an automatic data use register',
			});

		if (datasetTitles && !isEqual(datasetTitles, dataUseRegister.datasetTitles))
			return res.status(401).json({
				success: false,
				message: 'You are not authorised to update the datasets of an automatic data use register',
			});
	}

	next();
};

const authorizeUpload = async (req, res, next) => {
	const requestingUser = req.user;
	const { teamId } = req.body;

	const authorised = _isUserDataUseAdmin(requestingUser) || _isUserMemberOfTeam(requestingUser, teamId);

	if (!authorised) {
		return res.status(401).json({
			success: false,
			message: 'You are not authorised to perform this action',
		});
	}

	next();
};

export { validateUpdateRequest, validateUploadRequest, authorizeUpdate, authorizeUpload };
