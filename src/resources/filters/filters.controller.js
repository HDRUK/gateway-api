import Controller from '../base/controller';

export default class FiltersController extends Controller {
	constructor(filtersService) {
		super(filtersService);
		this.filtersService = filtersService;
	}

	async getFilters(req, res) {
		try {
			// Extract id parameter from query string
			const { id } = req.params;
			// If no id provided, it is a bad request
			if (!id) {
				return res.status(400).json({
					success: false,
					message: 'You must provide a filters identifier',
				});
			}
			// Find the filters
			let filters = await this.filtersService.getFilters(id, req.query);
			// Return if no filters found
			if (!filters) {
				return res.status(404).json({
					success: false,
					message: 'A filter could not be found with the provided id',
				});
			}
			// Return the filters
			return res.status(200).json({
				success: true,
				data: filters,
			});
		} catch (err) {
			// Return error response if something goes wrong
			process.stdout.write(`DISCOURSE - getFilters : ${err.message}`);
			return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
		}
	}
}
