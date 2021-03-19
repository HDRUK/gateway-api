import { model, Schema } from 'mongoose';
import constants from '../utilities/constants.util';

const DataRequestSchemas = new Schema({
  id: Number,
  status: String,
  version: {
    type: Number,
    default: 1
  },
  dataSetId: {
    type: String,
    default: '',
  },
  publisher: {
    type: String,
    default: ''
  },
  formType: {
    type: String,
    default: constants.FormTypes.Extended5Safe,
    enum: Object.values(constants.FormTypes)
  },
  jsonSchema: String,
}, {
  timestamps: true 
});


export const DataRequestSchemaModel = model('data_request_schemas', DataRequestSchemas); 


