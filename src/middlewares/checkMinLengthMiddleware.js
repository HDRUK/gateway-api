export default function checkMinLengthMiddleware(req, res, next) {
    const { filter } = req.params;

    const minNumberOfChars = process.env.MIN_NUMBER_OF_CHARS;

    if (filter.length < minNumberOfChars) {
        return res.status(400).json({
            message: `You must provide a string filter with minim ${minNumberOfChars} characters`,
        });
    }

    next();
}
