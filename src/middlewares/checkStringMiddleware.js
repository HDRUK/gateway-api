export default function checkStringMiddleware(req, res, next) {
    const { filter } = req.params;

    if (!filter.match(/^[a-zA-Z]+$/)) {
        return res.status(400).json({
            message: `You must provide a string filter`,
        });
    }

    next();
}
