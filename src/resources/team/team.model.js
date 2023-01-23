import { model, Schema } from 'mongoose';

import constants from '../utilities/constants.util';

const TeamSchema = new Schema(
	{
		id: {
			type: Number,
			unique: true,
		},
		members: [
			{
				_id: false,
				memberid: { type: Schema.Types.ObjectId, ref: 'User', required: true },
				roles: { 
					type: [String], 
					enum: [
						'reviewer',
						'manager',
						'metadata_editor',
						'custodian.team.admin',
						'custodian.metadata.manager',
						'custodian.dar.manager'
					], 
					required: true 
				},
				dateCreated: Date,
				dateUpdated: Date,
				notifications: [
					{
						notificationType: {
							type: String,
							enum: Object.values(constants.teamNotificationTypes),
						}, // metadataonbarding || dataaccessrequest
						optIn: { type: Boolean, default: true },
						message: String,
					},
				],
			},
		],
		type: String,
		active: {
			type: Boolean,
			default: true,
		},
		notifications: [
			{
				notificationType: {
					type: String, // metadataonbarding || dataaccessrequest
					default: constants.teamNotificationTypes.DATAACCESSREQUEST,
					enum: Object.values(constants.teamNotificationTypes),
				},
				optIn: { type: Boolean, default: false },
				subscribedEmails: [String],
			},
		],
	},
	{
		toJSON: { virtuals: true },
		toObject: { virtuals: true },
		timestamps: true,
	}
);

TeamSchema.virtual('publisher', {
	ref: 'Publisher',
	foreignField: '_id',
	localField: '_id',
	justOne: true,
});

TeamSchema.virtual('users', {
	ref: 'User',
	foreignField: '_id',
	localField: 'members.memberid',
	match: { isServiceAccount: { $ne: true } },
});

export const TeamModel = model('Team', TeamSchema);
