import { has, isNaN } from 'lodash';

export const resultLimit = (req, res, next, allowedLimit) => {
    let error;
    if(has(req.query, 'limit')) {
        const requestedLimit = parseInt(req.query.limit);

        if(isNaN(requestedLimit)) {
		    error = `The result limit parameter provided must be a numeric value.`;
        }
        else if (requestedLimit > allowedLimit){
		    error = `Maximum request limit exceeded.  You may only request up to a maximum of ${allowedLimit} records per page.  Please use the page query parameter to request further data.`;
        }
    }

	if (error) {
		return res.status(400).json({
			success: false,
			message: error,
		});
	}

	next();
};
