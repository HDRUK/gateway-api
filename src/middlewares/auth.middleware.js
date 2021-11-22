const captureReferer = (req, res, next) => {
	if (req.headers.referer) {
		req.param.returnpage = req.headers.referer;
	}
	next();
};

const catchLoginErrorAndRedirect = (req, res, next) => {
	if (req.auth.err || !req.auth.user) {
		if (req.auth.err === 'loginError') {
			return res.status(200).redirect(process.env.homeURL + '/loginerror');
		}

		let redirect = '/';
		let returnPage = null;
		if (req.param.returnpage) {
			returnPage = Url.parse(req.param.returnpage);
			redirect = returnPage.path;
			delete req.param.returnpage;
		}

		let redirectUrl = process.env.homeURL + redirect;

		return res.status(200).redirect(redirectUrl);
	}
	next();
};

export { captureReferer, catchLoginErrorAndRedirect };
