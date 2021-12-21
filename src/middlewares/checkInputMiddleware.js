export default function checkInputMiddleware(req, res, next) {
    const { filter } = req.params;

    if (!filter) {
        return res.status(400).json({
            message: `You must provide a filter`,
        });
    }

    next();
}
