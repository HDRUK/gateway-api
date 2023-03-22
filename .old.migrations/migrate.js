import cli from 'migrate-mongoose/src/cli'; //lgtm [js/unused-local-variable]
import mongoose from 'mongoose';

mongoose.connect(`${process.env.MIGRATE_dbConnectionUri}/${process.env.database}/?retryWrites=true&w=majority`, {
	useNewUrlParser: true,
	useFindAndModify: false,
	useUnifiedTopology: true,
	autoIndex: false,
});
