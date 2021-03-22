import { model, Schema } from 'mongoose';

import FiltersClass from './filters.entity';

const filtersSchema = new Schema(
	{
		id: String,
		keys: { type: Schema.Types.Mixed, default: {} },
	},
	{
		toJSON: { virtuals: true },
		toObject: { virtuals: true },
	}
);

// Load entity class
filtersSchema.loadClass(FiltersClass);

export const Filters = model('Filters', filtersSchema);
