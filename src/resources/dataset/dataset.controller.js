import Controller from '../base/controller';

export default class DatasetController extends Controller {
	constructor(datasetService) {
		super(datasetService);
		this.datasetService = datasetService;
	}

	async getDataset(req, res) {
		try {
			// Extract id parameter from query string
			const { id } = req.params;

			// Find the dataset
			const options = { lean: false, populate: { path: 'submittedDataAccessRequests' } };
			let dataset = await this.datasetService.getDataset(id, req.query, options);
			// Return if no dataset found
			if (!dataset) {
				return res.status(404).json({
					success: false,
					message: 'A dataset could not be found with the provided id',
				});
			}
			// Return the dataset
			return res.status(200).json({
				success: true,
				...dataset,
			});
		} catch (err) {
			// Return error response if something goes wrong
			process.stdout.write(`DATA SET - getDataset : ${err.message}\n`);
			return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
		}
	}

	async getDatasets(req, res) {
		try {
			// Find the datasets
			const options = { lean: false, populate: { path: 'submittedDataAccessRequests' } };
			let datasets = await this.datasetService.getDatasets(req.query, options);
			// Return the datasets
			return res.status(200).json({
				success: true,
				datasets,
			});
		} catch (err) {
			// Return error response if something goes wrong
			process.stdout.write(`DATA SET - getDatasets : ${err.message}\n`);
			return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
		}
	}
}
