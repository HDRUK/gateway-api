import Controller from '../../base/controller';

export default class ToolController extends Controller {
	constructor(toolService) {
        super(toolService);
		this.toolService = toolService;
	}

	async getTool(req, res) {
		try {
            // Extract id parameter from query string
			const { id } = req.params;
            // If no id provided, it is a bad request
			if (!id) {
				return res.status(400).json({
					success: false,
					message: 'You must provide a tool identifier',
				});
			}
            // Find the tool
			let tool = await this.toolService.getTool(id, req.query);
            // Return if no tool found
			if (!tool) {
				return res.status(404).json({
					success: false,
					message: 'A tool could not be found with the provided id',
				});
            }
            // Return the tool
			return res.status(200).json({
				success: true,
				data: tool,
			});
		} catch (err) {
            // Return error response if something goes wrong
            process.stdout.write(`TOOL - getTool : ${err.message}\n`);
            return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
        }
    }
    
    async getTools(req, res) {
		try {
            // Find the tools
            let tools = await this.toolService.getTools(req.query);
            // Return the tools
			return res.status(200).json({
				success: true,
				data: tools
			});
		} catch (err) {
            // Return error response if something goes wrong
            process.stdout.write(`TOOL - getTools : ${err.message}\n`);
            return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
        }
	}
}
