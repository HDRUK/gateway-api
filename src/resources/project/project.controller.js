import Controller from '../base/controller';

export default class ProjectController extends Controller {
	constructor(projectService) {
        super(projectService);
		this.projectService = projectService;
	}

	async getProject(req, res) {
		try {
            // Extract id parameter from query string
			const { id } = req.params;
            // If no id provided, it is a bad request
			if (!id) {
				return res.status(400).json({
					success: false,
					message: 'You must provide a project identifier',
				});
			}
            // Find the project
			let project = await this.projectService.getProject(id, req.query);
            // Return if no project found
			if (!project) {
				return res.status(404).json({
					success: false,
					message: 'A project could not be found with the provided id',
				});
            }
            // Return the project
			return res.status(200).json({
				success: true,
				data: project,
			});
		} catch (err) {
            // Return error response if something goes wrong
            process.stdout.write(`PROJECT - GET PROJECT : ${err.message}\n`);
            return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
        }
    }
    
    async getProjects(req, res) {
		try {
            // Find the projects
            let projects = await this.projectService.getProjects(req.query);
            // Return the projects
			return res.status(200).json({
				success: true,
				data: projects
			});
		} catch (err) {
            // Return error response if something goes wrong
            process.stdout.write(`PROJECT - GET PROJECTS : ${err.message}\n`);
            return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
        }
	}
}
