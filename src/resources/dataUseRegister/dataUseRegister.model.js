import { model, Schema } from 'mongoose';

import DataUseRegisterClass from './dataUseRegister.entity';
import constants from './../../resources/utilities/constants.util';

const dataUseRegisterSchema = new Schema(
	{
		id: { type: Number, required: true },
		type: { type: String, required: true },
		activeflag: { type: String, required: true, enum: Object.values(constants.dataUseRegisterStatus) },
		updatedon: Date,
		counter: { type: Number, default: 0 },
		discourseTopicId: Number,
		relatedObjects: [
			{
				objectId: String,
				reason: String,
				objectType: String,
				pid: String,
				user: String,
				updated: String,
			},
		],
		keywords: [String],
		manualUpload: Boolean,

		lastActivity: Date,
		projectTitle: { type: String },
		projectId: { type: Schema.Types.ObjectId, ref: 'data_request' },
		projectIdText: String, //Project ID
		datasetTitles: [{ type: String }], //Dataset Name(s)
		datasetIds: [{ type: String }],
		datasetPids: [{ type: String }],
		publisher: { type: Schema.Types.ObjectId, ref: 'Publisher', required: true },
		user: { type: Schema.Types.ObjectId, ref: 'User', required: true },
		organisationName: { type: String }, //Organisation Name
		organisationSector: String, //Organisation Sector
		gatewayApplicants: [
			{
				type: Schema.Types.ObjectId,
				ref: 'User',
			},
		],
		nonGatewayApplicants: [{ type: String }], //Applicant Name(s)
		applicantId: String, //Applicant ID
		fundersAndSponsors: [{ type: String }], // Funders/Sponsors
		accreditedResearcherStatus: String, //Accredited Researcher Status
		sublicenceArrangements: String, //Sub-Licence Arrangements (if any)?
		laySummary: String, //Lay Summary
		publicBenefitStatement: String, //Public Benefit Statement
		requestCategoryType: String, //Request Category Type
		technicalSummary: String, //Technical Summary
		otherApprovalCommittees: [{type: String}], //Other Approval Committees
		projectStartDate: Date, //Project Start Date
		projectEndDate: Date, //Project End Date
		latestApprovalDate: Date, //Latest Approval Date
		dataSensitivityLevel: String, //Data Sensitivity Level
		legalBasisForDataArticle6: String, //Legal Basis For Provision Of Data (changed to 'Legal basis for provision of data under Article 6')
		legalBasisForDataArticle9: String, //Added 'Lawful conditions for provision of data under Article 9'
		dutyOfConfidentiality: String, //Common Law Duty Of Confidentiality
		nationalDataOptOut: String, //National Data Opt-Out Applied
		requestFrequency: String, //Request Frequency
		datasetLinkageDescription: String, //Description Of How The Data Will Be Processed (changed to 'For linked datasets, specify how the linkage will take place')
		confidentialDataDescription: String, //Description Of The Confidential Data Being Used
		accessDate: Date, //Release/Access Date
		dataLocation: String, //TRE Or Any Other Specified Location
		privacyEnhancements: String, //How Has Data Been Processed To Enhance Privacy
		researchOutputs: [{type: String}], //Link To Research Outputs
	},
	{
		timestamps: true,
		toJSON: { virtuals: true },
		toObject: { virtuals: true },
		strict: false
	}
);

// Load entity class
dataUseRegisterSchema.loadClass(DataUseRegisterClass);

export const DataUseRegister = model('DataUseRegister', dataUseRegisterSchema);
