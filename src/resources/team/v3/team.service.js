import HttpExceptions from "../../../exceptions/HttpExceptions";
import { TeamModel } from "../team.model";

export default class TeamService {
    constructor() {}

    async getMembersByTeamId(teamId) {
        try {
            const team = await TeamModel.findOne({ _id: teamId }).populate({
                path: 'users',
                populate: {
                    path: 'additionalInfo',
                    select: 'organisation bio showOrganisation showBio news',
                },
            });    
    
            if (!team) {
                throw new Error(`Team not Found`);
            }

            return team;
        } catch (e) {
            process.stdout.write(`TeamController.getTeamMembers : ${e.message}\n`);
            throw new HttpExceptions(e.message);
        }
    }

    async getTeamByTeamId(teamId) {
        try {
            const team = await TeamModel
                .findOne({ _id: teamId })
                .populate([
                    { path: 'users' }, 
                    { 
                        path: 'publisher', 
                        select: 'name'
                    }
                ]);    
    
            if (!team) {
                throw new Error(`Team not Found`);
            }

            return team;
        } catch (e) {
            process.stdout.write(`TeamController.getTeamByTeamId : ${e.message}\n`);
            throw new HttpExceptions(e.message);
        }
    }

    async getTeamByTeamIdSimple(teamId) {
        try {
            const team = await TeamModel
                .findOne({ _id: teamId });    
    
            if (!team) {
                throw new Error(`Team not Found`);
            }

            return team;
        } catch (e) {
            process.stdout.write(`TeamController.getTeamByTeamIdSimple : ${e.message}\n`);
            throw new HttpExceptions(e.message);
        }
    }

}
