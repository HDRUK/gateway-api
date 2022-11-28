import Controller from '../base/controller';
import { logger } from '../utilities/logger';

const logCategory = 'questionbank';

export default class QuestionbankController extends Controller {
	constructor(questionbankService) {
		super(questionbankService);
		this.questionbankService = questionbankService;
	}

	async getQuestionbank(req, res) {
		try {
			const { publisherId } = req.params;

			const result = await this.questionbankService.getQuestionBankInfo(publisherId);

			return res.status(200).json({
				success: true,
				result,
			});
		} catch (err) {
			// Return error response if something goes wrong
			process.stdout.write(`QUESTIONBANK - GET QUESTIONBANK : ${err.message}\n`);
			return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
		}
	}

	async getQuestionbanks(req, res) {
		try {
			// Find the relevant questionbanks
			const questionbanks = await this.questionbankService.getQuestionbanks(req.query).catch(err => {
				logger.logError(err, logCategory);
			});
			// Return the questionbanks
			return res.status(200).json({
				success: true,
				data: questionbanks,
			});
		} catch (err) {
			// Return error response if something goes wrong
			logger.logError(err, logCategory);
			return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
		}
	}

	async publishSchema(req, res) {
		try {
			const { dataRequestSchema } = req.body;

			const newRequestSchema = await this.questionbankService.publishSchema(dataRequestSchema, req.user.id);

			return res.status(200).json({ success: true, result: newRequestSchema });
		} catch (err) {
			logger.logError(err, logCategory);
			return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
		}
	}

	async revertChanges(req, res) {
		try {
			const { publisherId } = req.params;
			const { page } = req.query;

			await this.questionbankService.revertChanges(publisherId, page);

			return res.status(200).json({ success: true });
		} catch (err) {
			logger.logError(err, logCategory);
			return res.status(500).json({
				success: false,
				message: 'Error removing the schema updates, please try again',
			});
		}
	}
}
