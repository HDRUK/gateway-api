import mongoose from 'mongoose';
import { connection } from 'mongoose';

const connectToDatabase = async () => {
	try {
		const mongoURI =
			'mongodb+srv://' +
			process.env.user +
			':' +
			process.env.password +
			'@' +
			process.env.cluster +
			'/' +
			process.env.database +
			'?ssl=true&retryWrites=true&w=majority';
		await mongoose.connect(mongoURI, {
			useNewUrlParser: true,
			useFindAndModify: false,
			useUnifiedTopology: true,
			autoIndex: false, // Don't build indexes
			poolSize: 10, // Maintain up to 10 socket connections
			// If not connected, return errors immediately rather than waiting for reconnect
			bufferMaxEntries: 0,
		});

		process.stdout.write(`MongoDB connected...\n`);
	} catch (err) {
		process.stdout.write(`connectToDatabase : ${err.message}\n`);

		process.exit(1);
	}
};

export { connectToDatabase, connection };
