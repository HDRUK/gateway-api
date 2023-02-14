/* eslint-disable no-undef */
import passport from 'passport';
import jwt from 'jsonwebtoken';
import { to } from 'await-to-js';
import Url from 'url';
import { isEmpty, isNull } from 'lodash';
import queryString from 'query-string';

import { ROLES } from '../user/user.roles';
import { UserModel } from '../user/user.model';
import { Course } from '../course/course.model';
import { Collections } from '../collections/collections.model';
import { Data } from '../tool/data.model';
import { TeamModel } from '../team/team.model';
import constants from '../utilities/constants.util';
import { discourseLogin } from './sso/sso.discourse.service';
import { updateRedirectURL } from './../user/user.service';
import { getObjectById } from './../tool/data.repository';

const eventLogController = require('./../eventlog/eventlog.controller');

const setup = () => {
	passport.serializeUser((user, done) => done(null, user._id));

	passport.deserializeUser(async (id, done) => {
		try {
			const user = await UserModel.findById(id);
			return done(null, user);
		} catch (err) {
			return done(err, null);
		}
	});
};

const signToken = (user, expiresIn = 604800) => {
	return jwt.sign({ data: user }, process.env.JWTSecret, {
		//Here change it so only id
		algorithm: 'HS256',
		expiresIn,
	});
};

const camundaToken = () => {
	return jwt.sign(
		// This structure must not change or the authenication between camunda and the gateway will fail
		// username: An admin user the exists within the camunda-admin group
		// groupIds: The admin group that has been configured on the camunda portal.
		{ username: process.env.BPMN_ADMIN_USER, groupIds: ['camunda-admin'], tenantIds: [] },
		process.env.JWTSecret || 'local',
		{
			//Here change it so only id
			algorithm: 'HS256',
			expiresIn: 604800,
		}
	);
};

const checkIsInRole =
	(...roles) =>
	(req, res, next) => {
		if (!req.user) {
			return res.redirect('/login');
		}

		const hasRole = roles.find(role => req.user.role === role);
		if (!hasRole) {
			return res.redirect('/login');
		}

		return next();
	};

const whatIsRole = req => {
	if (!req.user) {
		return 'Reader';
	} else {
		return req.user.role;
	}
};

const checkIsUser = () => (req, res, next) => {
	if (req.user) {
		if (req.params.userID && req.params.userID === req.user.id.toString()) return next();
		else if (req.params.id && req.params.id === req.user.id.toString()) return next();
		else if (req.body.id && req.body.id.toString() === req.user.id.toString()) return next();
	}

	return res.status(401).json({
		status: 'error',
		message: 'Unauthorised to perform this action.',
	});
};

const checkAllowedToAccess = type => async (req, res, next) => {
	const { user, params } = req;
	if (!isEmpty(user)) {
		if (user.role === ROLES.Admin) return next();
		else if (!isEmpty(params.id)) {
			let data = {};
			if (type === 'course') {
				data = await Course.findOne({ id: params.id }, { creator: 1 }).lean();
				if (!isEmpty(data) && [data.creator].includes(user.id)) return next();
			} else if (type === 'collection') {
				data = await Collections.findOne({ id: params.id }, { authors: 1 }).lean();
				if (!isEmpty(data) && data.authors.includes(user.id)) return next();
			} else {
				data = await Data.findOne({ id: params.id }, { authors: 1, uploader: 1 }).lean();
				if (!isEmpty(data) && [...data.authors, data.uploader].includes(user.id)) return next();
			}
		}
	}

	return res.status(401).json({
		status: 'error',
		message: 'Unauthorised to perform this action.',
	});
};

const getTeams = async () => {
	const teams = await TeamModel.find({ type: { $ne: constants.teamTypes.ADMIN } }, { _id: 1, type: 1 })
		.populate({
			path: 'publisher',
			select: 'name',
		})
		.lean();

	return teams;
};

const catchLoginErrorAndRedirect = (req, res, next) => {
	if (req.auth.err || !req.auth.user) {
		if (req.auth.err === 'loginError') {
			return res.status(200).redirect(process.env.homeURL + '/loginerror');
		}

		let redirect = '/';
		let returnPage = null;
		if (req.param.returnpage) {
			returnPage = Url.parse(req.param.returnpage);
			redirect = returnPage.path;
			delete req.param.returnpage;
		}

		let redirectUrl = process.env.homeURL + redirect;

		return res.status(200).redirect(redirectUrl);
	}
	next();
};

const loginAndSignToken = (req, res, next) => {
	req.login(req.auth.user, async err => {
		if (err) {
			return next(err);
		}

		let redirect = '/';
		let returnPage = null;
		let queryStringParsed = null;
		if (req.param.returnpage) {
			returnPage = Url.parse(req.param.returnpage);
			redirect = returnPage.path;
			queryStringParsed = queryString.parse(returnPage.query);
		}

		let [, profile] = await to(getObjectById(req.user.id));
		if (!profile) {
			await to(updateRedirectURL({ id: req.user.id, redirectURL: redirect }));
			return res.redirect(process.env.homeURL + '/completeRegistration/' + req.user.id);
		}

		if (req.param.returnpage) {
			delete req.param.returnpage;
		}

		let redirectUrl = process.env.homeURL + redirect;
		if (queryStringParsed && queryStringParsed.sso && queryStringParsed.sig) {
			try {
				redirectUrl = discourseLogin(queryStringParsed.sso, queryStringParsed.sig, req.user);
			} catch (err) {
				process.stdout.write(`UTILS - loginAndSignToken : ${err.message}\n`);
				return res.status(500).send('Error authenticating the user.');
			}
		}

		//Build event object for user login and log it to DB
		let eventObj = {
			userId: req.user.id,
			event: `user_login_${req.user.provider}`,
			timestamp: Date.now(),
		};

		await eventLogController.logEvent(eventObj);

		return res
			.status(200)
			.cookie('jwt', signToken({ _id: req.user._id, id: req.user.id, timeStamp: Date.now() }), {
				httpOnly: true,
				secure: process.env.api_url ? true : false,
			})
			.redirect(redirectUrl);
	});
};

const userIsTeamManager = () => async (req, res, next) => {
	const { user, params } = req;
	const members = await TeamModel.findOne({ _id: params.id }, { _id: 0, members: { $elemMatch: { memberid: user._id } } }).lean();
	if ((!isEmpty(members) && members.members[0].roles.includes(constants.roleTypes.MANAGER)) || user.role === 'Admin') return next();

	return res.status(401).json({
		status: 'error',
		message: 'Unauthorised to perform this action.',
	});
};

function isUserMemberOfTeamById(user, teamId) {
	let { teams } = user;
	return teams.filter(team => !isNull(team.publisher)).some(team => team.publisher._id.equals(teamId));
}

function isUserMemberOfTeamByName(user, publisherName) {
	let { teams } = user;
	return teams.filter(team => !isNull(team.publisher)).some(team => team.publisher.name === publisherName);
}

export {
	setup,
	signToken,
	camundaToken,
	checkIsInRole,
	whatIsRole,
	checkIsUser,
	checkAllowedToAccess,
	getTeams,
	catchLoginErrorAndRedirect,
	loginAndSignToken,
	isUserMemberOfTeamById,
	isUserMemberOfTeamByName,
	userIsTeamManager,
};
