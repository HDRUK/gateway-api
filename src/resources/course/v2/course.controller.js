import Controller from '../../base/controller';

export default class CourseController extends Controller {
	constructor(courseService) {
        super(courseService);
		this.courseService = courseService;
	}

	async getCourse(req, res) {
		try {
            // Extract id parameter from query string
			const { id } = req.params;
            // If no id provided, it is a bad request
			if (!id) {
				return res.status(400).json({
					success: false,
					message: 'You must provide a course identifier',
				});
			}
            // Find the course
			let course = await this.courseService.getCourse(id, req.query);
            // Return if no course found
			if (!course) {
				return res.status(404).json({
					success: false,
					message: 'A course could not be found with the provided id',
				});
            }
            // Return the course
			return res.status(200).json({
				success: true,
				data: course,
			});
		} catch (err) {
            // Return error response if something goes wrong
            process.stdout.write(`COURSE - setStatus : ${err.message}\n`);
            return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
        }
    }
    
    async getCourses(req, res) {
		try {
            // Find the courses
            let courses = await this.courseService.getCourses(req.query);
            // Return the courses
			return res.status(200).json({
				success: true,
				data: courses
			});
		} catch (err) {
            // Return error response if something goes wrong
            process.stdout.write(`COURSE - getCourses : ${err.message}\n`);
            return res.status(500).json({
				success: false,
				message: 'A server error occurred, please try again',
			});
        }
	}
}
