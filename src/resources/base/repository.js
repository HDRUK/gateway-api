import { isNaN } from 'lodash';

export default class Repository {
	constructor(Model) {
		this.collection = Model;
	}

	// @desc    Allows us to query a collection via the model inheriting this class with various options
	async find(query = {}, { multiple = true, count, lean, populate } = {}) {
		//Build query
		let queryObj = { ...query };

		// Check if each param should be a Number.  Allows searching for a value in a Numbers array
		Object.keys(queryObj).forEach(key => {
			queryObj[key] = isNaN(queryObj[key] * 1) ? queryObj[key] : queryObj[key] * 1;
		});

		// Population from query
		if (query.populate) {
			populate = query.populate.split(',').join(' ');
		}

		// Filtering
		const excludedFields = ['page', 'sort', 'limit', 'fields', 'count', 'search', 'expanded', 'populate'];
		excludedFields.forEach(el => delete queryObj[el]);

		// Keyword search
		queryObj = query.search ? { $text: { $search: query['search'] }, ...queryObj } : queryObj;

		let queryStr = JSON.stringify(queryObj);

		// Advanced filtering
		queryStr = processQueryParamOperators(queryStr);

		let results = multiple
			? query.search
				? this.collection.find(JSON.parse(queryStr), { searchRelevance: { $meta: 'textScore' } })
				: this.collection.find(JSON.parse(queryStr))
			: this.collection.findOne(JSON.parse(queryStr));

		// Sorting
		const sortBy = query.sort ? query.sort.split(',').join(' ') : query.search ? { searchRelevance: { $meta: 'textScore' } } : null;
		results = sortBy ? results.sort(sortBy) : results;

		// Field limiting
		const fields = query.fields ? query.fields.split(',').join(' ') : null;
		results = fields ? results.select(fields) : results.select('-__v');

		// Pagination
		const page = query.page * 1 || 1;
		const limit = query['limit'] !== undefined ? query.limit * 1 : 1500;
		const skip = (page - 1) * limit;
		results = results.skip(skip).limit(limit);

		// Population
		results = populate ? results.populate(populate) : results;

		// Count user option
		count = query.count || count;

		// Execute query
		if (count) {
			return results.countDocuments().exec();
		} else if (lean) {
			return results.lean().exec();
		} else {
			return results.exec();
		}
	}

	// @desc    Allows us to count to total number of documents within this collection via the model inheriting this class
	async count() {
		return this.collection.estimatedDocumentCount();
	}

	// @desc    Allows us to create a new Mongoose document within the collection via the model inheriting this class
	async create(body) {
		const document = new this.collection(body);
		return document.save();
	}

	// @desc    Allows us to update an existing Mongoose document within the collection via the model inheriting this class
	async update(document, body = {}) {
		const id = typeof document._id !== 'undefined' ? document._id : document;
		return this.collection.findByIdAndUpdate(id, body, { new: true });
	}

	// @desc    Allows us to update existing Mongoose documents found by a query within the collection via the model inheriting this class
	async updateByQuery(query = {}, body = {}) {
		return this.collection.findOneAndUpdate(query, body, { new: true, upsert: true });
	}

	// @desc    Allows us to delete an existing Mongoose document within the collection via the model inheriting this class
	async remove(document) {
		const reloadedDocument = await this.reload(document);
		return reloadedDocument.remove();
	}

	// @desc    Allows us to convert identifiers to Mongoose documents, plain entities to Mongoose documents,
	//          or to simply reload Mongoose documents with different query parameters (selected fields, populated fields,
	//          or a lean version)
	async reload(document, { select, populate, lean } = {}) {
		if (!select && !populate && !lean && document instanceof this.collection) {
			return document;
		}

		return typeof document._id !== 'undefined'
			? this.findById(document._id, { select, populate, lean })
			: this.findById(document, { select, populate, lean });
	}

	// @desc    A helper function to find all documents with given options
	async findAll({ count, lean, populate } = {}) {
		return this.find({}, { multiple: true, count, lean, populate });
	}

	// @desc    A helper function to find a single document by unique identifier
	async findById(id, { lean, populate } = {}) {
		return this.find({ _id: id }, { multiple: false, count: false, lean, populate });
	}

	// @desc    A helper function to find the first document returned by a given query
	async findOne(query = {}, { lean, populate } = {}) {
		return this.find(query, { multiple: false, count: false, lean, populate });
	}

	// @desc    A helper function to count the number of documents returned by a given query
	async findCountOf(query = {}) {
		return this.find(query, { multiple: true, count: true });
	}
}

export const processQueryParamOperators = queryStr => {
	return queryStr.replace(/\"\b(gte|gt|lte|lt|eq)\b\":\"\b(\-?\d*\.?\d+)\b\"/g, (match, operator, value) => {
		const parsedValue = parseFloat(value);
		return `"$${operator}":${parsedValue}`;
	});
};
