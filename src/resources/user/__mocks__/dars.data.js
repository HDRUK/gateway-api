export const mockDars = [
    {
        _id: "61f1143078397b350634dac3",
        majorVersion: 1,
        authorIds: [8470291714590256,8470291714590257],
        datasetIds: ["9c457d37-3402-450a-8bbd-32bf51524ded"],
        initialDatasetIds: [],
        datasetTitles: ["Demo v4", "Demo v4-duplicate"],
        applicationStatus: "inProgress",
        applicationType: "initial",
        publisher: "ALLIANCE > SAIL",
        formType: "5 safe",
        isShared: false,
        userId: 7789224198062117,
        isCloneable: true,
        jsonSchema: {
            pages: [{
                description: "Who is going to be accessing the data?\n\nSafe People should have the right motivations for accessing research data and understand the legal and ethical considerations when using data that may be sensitive or confidential. Safe People should also have sufficient skills, knowledge and experience to work with the data effectively.  Researchers may need to undergo specific training or accreditation before accessing certain data or research environments and demonstrate that they are part of a bona fide research organisation.\n\nThe purpose of this section is to ensure that:\n- details of people who will be accessing the data and the people who are responsible for completing the application are identified\n- any individual or organisation that intends to access  the data requested is identified\n- all identified individuals have the necessary accreditation and/or expertise to work with the data effectively.",
                pageId: "safepeople",
                title: "Safe people",
                active: true
            }, {
                title: "Safe project",
                active: false,
                pageId: "safeproject",
                description: "What is the purpose of accessing the data?\n\nSafe projects are those that have a valid research purpose with a defined public benefit. \nFor access to data to be granted the researchers need to demonstrate that their proposal is an appropriate and ethical use of the data, and that it is intended to deliver clear public benefits.  The purpose of this section is to ensure that:\n- the project rationale is explained in lay terms\n- the research purpose has a defined public benefit. This can be new knowledge, new treatments, improved pathways of care, new techniques of training staff. \n- how the data requested will be used to achieve the project objectives is articulated."
            }, {
                active: false,
                title: "Safe data",
                description: "Safe data ensure that researchers have a clear legal basis for accessing the data and do not inadvertently learn something about the data subjects during the course of their analysis, minimising the risks of re-identification.\nThe minimisation of this risk could be achieved by removing direct identifiers, aggregating values, banding variables, or other statistical techniques that may make re-identification more difficult. Sensitive or confidential data could not be considered to be completely safe because of the residual risk to a data subject’s confidentiality.  Hence other limitations on access will need to be applied.\n\nThe purpose of this section is to ensure that: \n- there is a clear legal basis for accessing the requested data\n- the data requested is proportionate to the requirement of the project \n- all data requested is necessary in order to achieve the public benefit declared \n- data subjects cannot be identified by your team by cross-referencing datasets from anywhere else.",
                pageId: "safedata"
            }, {
                description: "Safe settings are analytics environments where researchers can access and analyse the requested datasets in a safe and ethical way. Safe settings encompass the physical environment and procedural arrangements such as the supervision and auditing regimes. For safe settings, the likelihood of both deliberate and accidental disclosure needs to be explicitly considered.\n\nThe purpose of this section is to ensure that:\n\n- researchers access requested data in a secure and controlled setting such as a Trusted Research Environment (TRE) that limits the unauthorised use of the data\n- practical controls and appropriate restrictions are in place if researchers access data though non-TRE environment. There may be requirements that data is held on restricted access servers, encrypted and only decrypted at the point of use.",
                pageId: "safesettings",
                title: "Safe settings",
                active: false
            }, {
                pageId: "safeoutputs",
                description: "Safe outputs ensure that all research outputs cannot be used to identify data subjects. They typically include ‘descriptive statistics’ that have been sufficiently aggregated such that identification is near enough impossible, and modelled outputs which are inherently non-confidential. The purpose of this section is to ensure that:\n\n- controls are in place to minimise risks associated with planned outputs and publications \n- the researchers aim to openly publish their results to enable use, scrutiny and further research.",
                title: "Safe outputs",
                active: false
            }],
            formPanels: [{
                index: 1,
                pageId: "safepeople",
                panelId: "primaryapplicant"
            }, {
                panelId: "safepeople-otherindividuals",
                pageId: "safepeople",
                index: 2
            }, {
                panelId: "safeproject-aboutthisapplication",
                pageId: "safeproject",
                index: 3
            }, {
                pageId: "safeproject",
                index: 4,
                panelId: "safeproject-projectdetails"
            }, {
                panelId: "safeproject-funderinformation",
                pageId: "safeproject",
                index: 5
            }, {
                index: 6,
                pageId: "safeproject",
                panelId: "safeproject-sponsorinformation"
            }, {
                panelId: "safeproject-declarationofinterest",
                pageId: "safeproject",
                index: 7
            }, {
                pageId: "safeproject",
                index: 8,
                panelId: "safeproject-intellectualproperty"
            }, {
                index: 9,
                pageId: "safedata",
                panelId: "safedata-datafields"
            }, {
                panelId: "safedata-analysis",
                index: 10,
                pageId: "safedata"
            }, {
                panelId: "safedata-otherdatasetsintentiontolinkdata",
                pageId: "safedata",
                index: 11
            }, {
                panelId: "safedata-lawfulbasis",
                index: 12,
                pageId: "safedata"
            }, {
                panelId: "safedata-confidentialityavenue",
                index: 13,
                pageId: "safedata"
            }, {
                pageId: "safedata",
                index: 14,
                panelId: "safedata-ethicalapproval"
            }, {
                panelId: "safesettings-storageandprocessing",
                pageId: "safesettings",
                index: 15
            }, {
                pageId: "safesettings",
                index: 16,
                panelId: "safesettings-dataflow"
            }, {
                index: 17,
                pageId: "safeoutputs",
                panelId: "safeoutputs-outputsdisseminationplans"
            }, {
                panelId: "safeoutputs-retention",
                index: 18,
                pageId: "safeoutputs"
            }, {
                index: 19,
                pageId: "safeoutputs",
                panelId: "safeoutputs-archiving"
            }],
            questionPanels: [{
                pageId: "safepeople",
                panelHeader: "Please list the individuals who will have access to the data requested, or are responsible for helping complete this application form. \r\n\r\nThis section should include key contact details for the person who is leading the project; key contact details for the person(s) who (are) leading the project from other organisations. Only one contact from each organisation is needed. \r\n\r\nThe 'Primary applicant' is the person filling out the application form and principal contact for the application. This is usually the person with operational responsibility for the proposal. Each application must have details for at least one person.\r\n\r\nPlease use the file upload function if you're not able to add all individuals via the form.\r",
                questionSets: [{
                    questionSetId: "primaryapplicant",
                    index: 1
                }],
                navHeader: "Primary applicant",
                questionPanelHeaderText: "TODO: We need a description for this panel",
                panelId: "primaryapplicant"
            }, {
                pageId: "safepeople",
                panelHeader: "Please list the individuals who will have access to the data requested, or are responsible for helping complete this application form. \r\n\r\nThis section should include key contact details for the person who is leading the project; key contact details for the person(s) who (are) leading the project from other organisations. Only one contact from each organisation is needed. \r\n\r\nThe 'Primary applicant' is the person filling out the application form and principal contact for the application. This is usually the person with operational responsibility for the proposal. Each application must have details for at least one person.\r\n\r\nPlease use the file upload function if you're not able to add all individuals via the form.\r",
                questionPanelHeaderText: "TODO: We need a description for this panel",
                panelId: "safepeople-otherindividuals",
                questionSets: [{
                    questionSetId: "safepeople-otherindividuals",
                    index: 1
                }, {
                    index: 100,
                    questionSetId: "add-safepeople-otherindividuals"
                }],
                navHeader: "Other individuals"
            }, {
                questionPanelHeaderText: "TODO: We need a description for this panel",
                panelId: "safeproject-aboutthisapplication",
                questionSets: [{
                    index: 1,
                    questionSetId: "safeproject-aboutthisapplication"
                }],
                navHeader: "About this application",
                panelHeader: "",
                pageId: "safeproject"
            }, {
                panelHeader: "",
                pageId: "safeproject",
                panelId: "safeproject-projectdetails",
                questionPanelHeaderText: "TODO: We need a description for this panel",
                navHeader: "Project details",
                questionSets: [{
                    questionSetId: "safeproject-projectdetails",
                    index: 1
                }]
            }, {
                navHeader: "Funder information",
                questionSets: [{
                    index: 1,
                    questionSetId: "safeproject-funderinformation"
                }],
                panelId: "safeproject-funderinformation",
                questionPanelHeaderText: "TODO: We need a description for this panel",
                panelHeader: "A funder is the organisation or body providing the financial resource to make the project possible, and may be different to the organisation detailed in the Safe people section. Please provide details of the main funder organisations supporting this project.\r\n\r\nPlease use the file upload function if you're not able to add all funders via the form.\r",
                pageId: "safeproject"
            }, {
                panelId: "safeproject-sponsorinformation",
                questionPanelHeaderText: "TODO: We need a description for this panel",
                navHeader: "Sponsor information",
                questionSets: [{
                    index: 1,
                    questionSetId: "safeproject-sponsorinformation"
                }],
                panelHeader: "The sponsor is usually, but does not have to be, the main funder of the research. The sponsor takes primary responsibility for ensuring that the design of the project meets appropriate standards and that arrangements are in place to ensure appropriate conduct and reporting.\r\n\r\nPlease use the file upload function if you're not able to add all sponsors via the form.\r\n",
                pageId: "safeproject"
            }, {
                navHeader: "Declaration of interest",
                questionSets: [{
                    questionSetId: "safeproject-declarationofinterest",
                    index: 1
                }],
                panelId: "safeproject-declarationofinterest",
                questionPanelHeaderText: "TODO: We need a description for this panel",
                panelHeader: "All interests that might unduly influence an individual’s judgement and objectivity in the use of the data being requested are of relevance, particularly if it involves payment or financial inducement. \r\n\r\nThese might include any involvement of commercial organisations at arm’s-length to the project, or likely impact on commercial organisations, individually or collectively, that might result from the outcomes or methodology of the project.\r\n\r\nAll individuals named in this application who have an interest this application must declare their interest.\r",
                pageId: "safeproject"
            }, {
                panelHeader: "All interests that might unduly influence an individual’s judgement and objectivity in the use of the data being requested are of relevance, particularly if it involves payment or financial inducement. \r\n\r\nThese might include any involvement of commercial organisations at arm’s-length to the project, or likely impact on commercial organisations, individually or collectively, that might result from the outcomes or methodology of the project.\r\n\r\nAll individuals named in this application who have an interest this application must declare their interest.\r",
                pageId: "safeproject",
                questionSets: [{
                    questionSetId: "safeproject-intellectualproperty",
                    index: 1
                }],
                navHeader: "Intellectual property",
                questionPanelHeaderText: "TODO: We need a description for this panel",
                panelId: "safeproject-intellectualproperty"
            }, {
                questionSets: [{
                    questionSetId: "safedata-datafields",
                    index: 1
                }],
                navHeader: "Data fields",
                questionPanelHeaderText: "TODO: We need a description for this panel",
                panelId: "safedata-datafields",
                pageId: "safedata",
                panelHeader: "These are the Information assets which your proposal seeks to access and use.\r\n\r\nYou should consider this definition to be wide in scope and include any source of information which you propose to access and use. The data may be highly structured or less structured in nature, already existing or to be newly collected or gathered. \r\n\r\nExamples may include national datasets, local data sets, national or local extracts from systems, national or local registries or networks, patient records, or new information to be gathered from patients, families or other cohorts. \r\n\r\nNew data” should only include data that is being specifically gathered for the first time for the purposes of this proposal. i.e. data already held in case notes and transferred to a form is not “new” data, but a survey filled out by clinicians in order to gather information not recorded anywhere else is “new”.\r"
            }, {
                panelId: "safedata-analysis",
                questionPanelHeaderText: "TODO: We need a description for this panel",
                navHeader: "Analysis",
                questionSets: [{
                    questionSetId: "safedata-analysis",
                    index: 1
                }],
                panelHeader: "These are the Information assets which your proposal seeks to access and use.\r\n\r\nYou should consider this definition to be wide in scope and include any source of information which you propose to access and use. The data may be highly structured or less structured in nature, already existing or to be newly collected or gathered. \r\n\r\nExamples may include national datasets, local data sets, national or local extracts from systems, national or local registries or networks, patient records, or new information to be gathered from patients, families or other cohorts. \r\n\r\nNew data” should only include data that is being specifically gathered for the first time for the purposes of this proposal. i.e. data already held in case notes and transferred to a form is not “new” data, but a survey filled out by clinicians in order to gather information not recorded anywhere else is “new”.\r",
                pageId: "safedata"
            }, {
                panelHeader: "This section should include information on the planned use of datasets not already included in this application. The following information is required:\r\n\r\nA descriptive name so that it is clear what the dataset is. \r\n\r\nSufficient information to explain the content of the dataset.  \r\n\r\nWhether the proposal requires linkage of data, the use of matched controls, or the extraction of anonymised data.\r\n\r\nPlease indicate which organisation or body is undertaking these processes and which variables from the data sources requested will be used to achieve the proposed linkage. This should cover every dataset and variable you will require.\r\n",
                pageId: "safedata",
                panelId: "safedata-otherdatasetsintentiontolinkdata",
                questionPanelHeaderText: "TODO: We need a description for this panel",
                navHeader: "Other datasets - Intention to link data",
                questionSets: [{
                    index: 1,
                    questionSetId: "safedata-otherdatasetsintentiontolinkdata"
                }]
            }, {
                questionSets: [{
                    questionSetId: "safedata-lawfulbasis",
                    index: 1
                }],
                navHeader: "Lawful basis",
                questionPanelHeaderText: "TODO: We need a description for this panel",
                panelId: "safedata-lawfulbasis",
                pageId: "safedata",
                panelHeader: "General Data Protection Regulation (GDPR) applies to ‘controllers’ and ‘processors’. \r\n\r\nA controller determines the purposes and means of processing personal data.\r\n\r\nA processor is responsible for processing personal data on behalf of a controller.\r\n \r\nGDPR applies to processing carried out by organisations operating within the EU. It also applies to organisations outside the EU that offer goods or services to individuals in the EU.\r\nGDPR does not apply to certain activities including processing covered by the Law Enforcement Directive, processing for national security purposes and processing carried out by individuals purely for personal/household activities. \r\n \r\nGDPR only applies to information which relates to an identifiable living individual. Information relating to a deceased person does not constitute personal data and therefore is not subject to the GDPR.\r"
            }, {
                questionSets: [{
                    index: 1,
                    questionSetId: "safedata-confidentialityavenue"
                }],
                navHeader: "Confidentiality avenue",
                questionPanelHeaderText: "TODO: We need a description for this panel",
                panelId: "safedata-confidentialityavenue",
                pageId: "safedata",
                panelHeader: "If confidential information is being disclosed, the organisations holding this data (both the organisation disclosing the information and the recipient organisation) must also have a lawful basis to hold and use this information, and if applicable, have a condition to hold and use special categories of confidential information, and be fair and transparent about how they hold and use this data. \r\n\r\nIn England and Wales, if you are using section 251 of the NHS Act 2006 (s251) as a legal basis for identifiable data, you will need to ensure that you have the latest approval letter and application. \r\n\r\nFor Scotland this application will be reviewed by the Public Benefit and Privacy Panel.\r\n\r\nIn Northern Ireland it will be considered by the Privacy Advisory Committee. If you are using patient consent as the legal basis, you will need to provide all relevant consent forms and information leaflets.\r\n"
            }, {
                panelHeader: "This section details the research and ethics approval which you have obtained or sought for your project, or otherwise provides evidence as to why such approval is not necessary. \r\nWhere such approval is not in place, it is important that you demonstrate why this is the case and provide assurances if approval is pending.  If you need advice on whether ethics approval is necessary, you should approach your local ethics services in the first instance. Information about UK research ethics committees and ethical opinions can be found on the Health Research Authority website.\r\n",
                pageId: "safedata",
                navHeader: "Ethical approval",
                questionSets: [{
                    index: 1,
                    questionSetId: "safedata-ethicalapproval"
                }],
                panelId: "safedata-ethicalapproval",
                questionPanelHeaderText: "TODO: We need a description for this panel"
            }, {
                panelHeader: "This section details in what way the proposal aims to store and use data, and controls in place to minimise risks associated with this storage and use. If you have indicated that your proposal seeks to store and use data exclusively through a recognised trusted research environment, then you do not need to complete this section.\r\n \r\nIn relation to personal data, means any operation or set of operations which is performed on personal data or on sets of personal data (whether or not by automated means, such as collection, recording, organisation, structuring, storage, alteration, retrieval, consultation, use, disclosure, dissemination, restriction, erasure or destruction).\r\n \r\nAll Locations where processing will be undertaken, for the avoidance of doubt storage is considered processing. For each separate organisation processing data which is not fully anonymous a separate partner organisation form must also be completed.\r\n \r\n Processing, in relation to information or data means obtaining, recording or holding the information or data or carrying out any operation or set of operations on the information or data, including—\r\n a) organisation, adaptation or alteration of the information or data,\r\n b) retrieval, consultation or use of the information or data,\r\n c) disclosure of the information or data by transmission,\r\n dissemination or otherwise making available, or\r\n d) alignment, combination, blocking, erasure or destruction of the information or data.\r\n\r\nPlease use the file upload function if you're not able to add all organisations via the form.\r",
                pageId: "safesettings",
                questionPanelHeaderText: "TODO: We need a description for this panel",
                panelId: "safesettings-storageandprocessing",
                questionSets: [{
                    questionSetId: "safesettings-storageandprocessing",
                    index: 1
                }],
                navHeader: "Storage and processing"
            }, {
                pageId: "safesettings",
                panelHeader: "",
                panelId: "safesettings-dataflow",
                questionPanelHeaderText: "TODO: We need a description for this panel",
                navHeader: "Dataflow",
                questionSets: [{
                    index: 1,
                    questionSetId: "safesettings-dataflow"
                }]
            }, {
                panelId: "safeoutputs-outputsdisseminationplans",
                questionPanelHeaderText: "TODO: We need a description for this panel",
                navHeader: "Outputs dissemination plans",
                questionSets: [{
                    index: 1,
                    questionSetId: "safeoutputs-outputsdisseminationplans"
                }],
                panelHeader: "Please include any plans for dissemination and publication of the data and results arising from your proposal. Please also specify any controls in place to minimise risks associated with publication. Dissemination can take place in a variety of ways and through many mechanisms, including through electronic media, print media or word of mouth.",
                pageId: "safeoutputs"
            }, {
                navHeader: "Retention",
                questionSets: [{
                    questionSetId: "safeoutputs-retention",
                    index: 1
                }],
                panelId: "safeoutputs-retention",
                questionPanelHeaderText: "TODO: We need a description for this panel",
                pageId: "safeoutputs",
                panelHeader: "This section details how the project will treat data being processed after it has been used for the purpose of the proposal outlined, including governance in place to determine how long it will be retained, and controls to manage its subsequent disposal if required. Please reference any relevant policies and procedures which are in place to govern retention and disposal of data as outlined in the proposal."
            }, {
                panelHeader: "This section details how the project will treat data being processed after it has been used for the purpose of the proposal outlined, including governance in place to determine how long it will be retained, and controls to manage its subsequent disposal if required. Please reference any relevant policies and procedures which are in place to govern retention and disposal of data as outlined in the proposal.",
                pageId: "safeoutputs",
                questionPanelHeaderText: "TODO: We need a description for this panel",
                panelId: "safeoutputs-archiving",
                questionSets: [{
                    questionSetId: "safeoutputs-archiving",
                    index: 1
                }],
                navHeader: "Archiving"
            }],
            questionSets: [{
                questionSetHeader: "Archiving",
                questions: [{
                    validations: [{
                        type: "isLength",
                        message: "Please enter a value",
                        params: [1]
                    }],
                    input: {
                        required: true,
                        type: "textareaInput"
                    },
                    question: "What method of destruction will be used when this period has expired?",
                    guidance: "Please provide details of how the data/files will be disposed of at the end of the period specified above. You might refer to any relevant disposal or destruction policies held by your organisation, by summarising the relevant section from the policy or including a URL and indicating which section is relevant.",
                    questionId: "safeoutputsdataretentiondestructionmethod"
                }, {
                    questionId: "safeoutputsdataretentiondestructionevidence",
                    input: {
                        type: "textareaInput"
                    },
                    guidance: "Please confirm you will notify us when the data have been destroyed. ",
                    question: "What evidence will be provided that destruction has occurred and when?"
                }],
                questionSetId: "safeoutputs-archiving"
            }]
        },
        schemaId: "5fbabae775c2095bdbdc1533",
        files: [],
        amendmentIterations: [],
        createdAt: "2022-01-26T09:28:16.463Z",
        updatedAt: "2022-01-26T09:28:16.586Z",
        __v: 0,
        projectId: "61F1-1430-7839-7B35-0634-DAC3",
        versionTree: {
            1.0: {
                applicationId: "61f1143078397b350634dac3",
                displayTitle: "Version 1.0",
                detailedTitle: "Version 1.0",
                link: "/data-access-request/61f1143078397b350634dac3?version=1.0",
                applicationType: "initial",
                applicationStatus: "inProgress",
                isShared: false
            }
        }
    }
];