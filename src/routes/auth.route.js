import express from 'express';
import passport from 'passport';
import { isEmpty } from 'lodash';

import constants from '../resources/utilities/constants.util';
import { authUtils } from '../utils';

const router = express.Router();

router.get('/logout', function (req, res) {
	req.logout();
	for (var prop in req.cookies) {
		res.clearCookie(prop);
	}
	return res.json({ success: true });
});

router.get('/status', function (req, res, next) {
	passport.authenticate('jwt', async function (err, user) {
		if (err || !user) {
			return res.json({
				success: true,
				data: [{ role: 'Reader', id: null, name: null, loggedIn: false }],
			});
		} else {
			// 1. Reformat teams array for frontend
			let { teams } = req.user.toObject();
			if (teams) {
				teams = teams.map(team => {
					let { publisher, type, members } = team;
					let member = members.find(member => {
						return member.memberid.toString() === req.user._id.toString();
					});
					let { roles } = member;
					return { ...publisher, type, roles };
				});
			}

			const adminArray = teams.filter(team => team.type === constants.teamTypes.ADMIN);
			if (!isEmpty(adminArray)) {
				if (adminArray[0].roles.includes(constants.roleTypes.ADMIN_DATASET)) {
					const allTeams = await authUtils.getTeams();
					allTeams.forEach(newTeam => {
						const foundTeam = teams.find(team => team._id && team._id.toString() === newTeam._id.toString());
						if (!isEmpty(foundTeam)) {
							const foundRole = foundTeam.roles.find(role => role === constants.roleTypes.METADATA_EDITOR);
							if (isEmpty(foundRole)) {
								foundTeam.roles.push(constants.roleTypes.METADATA_EDITOR);
							}
							foundTeam.isAdmin = true;
						} else {
							teams.push({
								_id: newTeam._id,
								name: newTeam.publisher.name,
								roles: [constants.roleTypes.METADATA_EDITOR],
								type: newTeam.type,
								isAdmin: true,
							});
						}
					});
				}
			}

			//Remove admin team and then sort teams alphabetically
			const teamArray = teams
				.filter(team => team.type !== constants.teamTypes.ADMIN)
				.sort(function (a, b) {
					return a.name.toUpperCase() < b.name.toUpperCase() ? -1 : a.name.toUpperCase() > b.name.toUpperCase() ? 1 : 0;
				});

			// 2. Return user info
			return res.json({
				success: true,
				data: [
					{
						role: req.user.role,
						id: req.user.id,
						name: req.user.firstname + ' ' + req.user.lastname,
						loggedIn: true,
						email: req.user.email,
						teams: [...adminArray, ...teamArray],
						provider: req.user.provider,
						advancedSearchRoles: req.user.advancedSearchRoles,
						acceptedAdvancedSearchTerms: req.user.acceptedAdvancedSearchTerms,
					},
				],
			});
		}
	})(req, res, next);
});

module.exports = router;
