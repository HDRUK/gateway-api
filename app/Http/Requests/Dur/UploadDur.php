<?php

namespace App\Http\Requests\Dur;

use App\Http\Requests\BaseFormRequest;

class UploadDur extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => [ // similar with application id
                'integer',
                'exists:users,id',
            ],
            'team_id' => [ // is required if we create a new dur with jwt token
                'integer',
                'exists:teams,id',
            ],
            'project_title' => [ // projectTitle - Project title
                'string',
                'required',
            ],
            'project_id_text' => [ // projectIdText - Project ID
                'nullable',
                'string',
            ],
            'datasets' => [ // Dataset(s) name*
                'nullable',
                'array',
            ],
            'datasets.*.id'  => [
                'integer',
                'exists:datasets,id',
            ],
            'organisation_name' => [ // organisationName - Organisation name*
                'string',
                'required',
            ],
            'organisation_id' => [ // organisationId - Organisation ID*
                'nullable',
                'string',
            ],
            'organisation_sector' => [ // organisationSector - Organisation sector
                'nullable',
                'string',
            ],
            'non_gateway_applicants' => [ // applicantNames - Applicant name(s) - I guess is about non_gateway_applicants
                'nullable',
                'string',
            ],
            'applicant_id' => [ // applicantId - Applicant ID - I guess is about user_id
                'nullable',
                'integer',
            ],
            'funders_and_sponsors' => [ // fundersAndSponsors - Funders/ Sponsors
                'nullable',
                'string',
            ],
            'accredited_researcher_status' => [ // accreditedResearcherStatus - DEA accredited researcher?
                'nullable',
                'string',
            ],
            'sublicence_arrangements' => [ // sublicenceArrangements - Sub-licence arrangements (if any)?
                'nullable',
                'string',
            ],
            'lay_summary' => [ // laySummary - Lay summary
                'nullable',
                'string',
            ],
            'public_benefit_statement' => [ // publicBenefitStatement - Public benefit statement
                'nullable',
                'string',
            ],
            'request_category_type' => [ // requestCategoryType - Request category type
                'nullable',
                'string',
            ],
            'technical_summary' => [ // technicalSummary - Technical summary
                'nullable',
                'string',
            ],
            'other_approval_committees' => [ // otherApprovalCommittees - Other approval committees
                'nullable',
                'string',
            ],
            'project_start_date' => [ // projectStartDate - Project start date
                'nullable',
                'date',
            ],
            'project_end_date' => [ // projectEndDate - Project end date
                'nullable',
                'date',
            ],
            'latest_approval_date' => [ // latestApprovalDate - Latest approval date
                'nullable',
                'date',
            ],
            'data_sensitivity_level' => [ // dataSensitivityLevel- Data sensitivity level
                'nullable',
                'date',
            ],
            'legal_basis_for_data_article6' => [ // legalBasisForDataArticle6 - Legal basis for provision of data under Article 6
                'nullable',
                'string',
            ],
            'legal_basis_for_data_article9' => [ // legalBasisForDataArticle9 - Lawful conditions for provision of data under Article 9
                'nullable',
                'string',
            ],
            'duty_of_confidentiality' => [ // dutyOfConfidentiality - Common Law Duty of Confidentiality
                'nullable',
                'string',
            ],
            'national_data_optout' => [ // nationalDataOptOut - National data opt-out applied?
                'nullable',
                'string',
            ],
            'request_frequency' => [ // requestFrequency - Request frequency
                'nullable',
                'string',
            ],
            'dataset_linkage_description' => [ // datasetLinkageDescription - For linked datasets, specify how the linkage will take place
                'nullable',
                'string',
            ],
            'confidential_data_description' => [ // confidentialDataDescription - Description of the confidential data being used
                'nullable',
                'string',
            ],
            'access_date' => [ // accessDate - Release/Access date
                'nullable',
                'date',
            ],
            'access_type' => [ // accessType - Access type
                'nullable',
                'string',
            ],
            'privacy_enhancements' => [ // privacyEnhancements - How has data been processed to enhance privacy?
                'nullable',
                'string',
            ],
            // 'researchOutputs' => [ // Link to research outputs - seems like we don't use or we don't use in mk1
            //     'nullable',
            //     'string',
            // ],
        ];
    }
}
