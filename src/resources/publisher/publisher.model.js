import { model, Schema } from 'mongoose';

const PublisherSchema = new Schema(
	{
		id: {
			type: Number,
			unique: true,
		},
		name: String,
		active: {
			type: Boolean,
			default: true,
		},
		imageURL: String,
		allowsMessaging: {
			type: Boolean,
			default: false,
		},
		dataRequestModalContent: {
			header: String,
			body: String,
			footer: String,
		},
		dataRequestModalContentUpdatedOn: Date,
		dataRequestModalContentUpdatedBy: Number,
		applicationFormUpdatedOn: Date,
		applicationFormUpdatedBy: Number,
		workflowEnabled: {
			type: Boolean,
			default: false,
		},
		publisherDetails: {
			name: String,
			logo: String,
			description: String,
			contactPoint: String,
			memberOf: String,
			accessRights: [String],
			deliveryLeadTime: String,
			accessService: String,
			accessRequestCost: String,
			dataUseLimitation: [String],
			dataUseRequirements: [String],
			questionBank: {
				enabled: { type: Boolean, default: false },
			},
			dataUse: {
				widget: {
					accepted: { type: Boolean, default: false },
					acceptedByUserId: String,
					acceptedDate: Date,
				},
			},
		},
		mdcFolderId: String,
		rorOrgId: String,
		gridAcId: String,
		allowAccessRequestManagement: { type: Boolean, default: false },
		uses5Safes: { type: Boolean, default: false },
		wordTemplate: String,
	},
	{
		toJSON: { virtuals: true },
		toObject: { virtuals: true },
	}
);

PublisherSchema.virtual('team', {
	ref: 'Team',
	foreignField: '_id',
	localField: '_id',
	justOne: true,
});

export const PublisherModel = model('Publisher', PublisherSchema);
