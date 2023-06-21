import Controller from '../base/controller';

export default class PaperController extends Controller {
	constructor(paperService) {
        super(paperService);
		this.paperService = paperService;
	}

	async getPaper(req, res) {
		try {
            // Extract id parameter from query string
			const { id } = req.params;
            // If no id provided, it is a bad request
			if (!id) {
				return res.status(400).json({
					success: false,
					message: 'You must provide a paper identifier',
				});
			}
            // Find the paper
			let paper = await this.paperService.getPaper(id, req.query);
            // Return if no paper found
			if (!paper) {
				return res.status(404).json({
					success: false,
					message: 'A paper could not be found with the provided id',
				});
            }
            // Return the paper
			return res.status(200).json({
				success: true,
				data: paper,
			});
		} catch (err) {
            // Return error response if something goes wrong
            process.stdout.write(`PAPER - getPaper : ${err.message}\n`);
            return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
        }
    }
    
    async getPapers(req, res) {
		try {
            // Find the papers
            let papers = await this.paperService.getPapers(req.query);
            // Return the papers
			return res.status(200).json({
				success: true,
				data: papers
			});
		} catch (err) {
            // Return error response if something goes wrong
            process.stdout.write(`PAPER - getPaper : ${err.message}\n`);
            return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
        }
	}
}
