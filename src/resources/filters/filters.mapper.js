export const datasetFilters = [
    {
        "id": 1,
        "label": "Coverage",
        "key": "coverage",
        "tooltip": null,
        "closed": true, 
        "isSearchable": false,
        "selectedCount": 0,
        "filters": [
            {
                "id": 2,
                "label": "Spatial",
                "key": "spatial",
                "tooltip": "Geo location",
                "closed": true, 
                "isSearchable": true,
                "selectedCount": 0,
                "filters": [],
                "highlighted": ["England"]
            },
            {
                "id": 3,
                "label": "Physical sample availability",
                "key": "physicalSampleAvailability",
                "tooltip": null,
                "closed": true, 
                "isSearchable": false,
                "selectedCount": 0,
                "filters": [{"id": 26, "label": "VALUE", "value": "Value", "checked": false}],
                "highlighted":["Value"] 
            },
            {
                "id": 4,
                "label": "Follow up",
                "key": "followUp",
                "tooltip": "Follow up details",
                "closed": true, 
                "isSearchable": false,
                "selectedCount": 0,
                "filters": [
                    {"id": 27, "label": "0-6 months", "value": "0-6 months", "checked": false}, 
                    {"id": 28, "label": "6-12 months", "value": "6-12 months", "checked": false}],
                "highlighted": ["0-6 months"]
            }
        ]
    },
    {
        "id": 5,
        "label": "Access",
        "key": "access",
        "tooltip": null,
        "active": false,
        "closed": true,
        "isSearchable": false,
        "selectedCount": 0, 
        "filters": [
            {
                "id": 6,
                "label": "Vocabulary encoding scheme",
                "key": "vocabularyEncodingScheme",
                "tooltip": "tooltip voca encoding",
                "active": false,
                "closed": true,
                "isSearchable": false, 
                "selectedCount": 0,
                "filters": [
                    {"id": 38,"label": "LOCAL", "value": "local", "checked": false},
                    {"id": 39,"label": "OPCS4", "value": "OPCS4", "checked": false},
                    {"id": 40,"label": "READ", "value": "READ", "checked": false},
                    {"id": 41,"label": "SNOMED CT", "value": "SNOMED CT", "checked": false},
                    {"id": 42,"label": "SNOMED RT", "value": "SNOMED RT", "checked": false},
                    {"id": 43,"label": "DM + D", "value": "DM + D", "checked": false},
                    {"id": 44,"label": "NHS NATIONAL CODES", "value": "NHS NATIONAL CODES", "checked": false},
                    {"id": 45,"label": "ODS", "value": "ODS", "checked": false},
                    {"id": 46,"label": "OKM", "value": "OKM", "checked": false},
                    {"id": 47,"label": "WRITE", "value": "WRITE", "checked": false}
                ],
                "highlighted":[] 
            },
            {
                "id": 7,
                "label": "Conforms to",
                "key": "conformsTo",
                "tooltip": "tooltip conforms to",
                "closed": true,
                "isSearchable": false, 
                "selectedCount": 0,
                "filters": [
                    {"id": 50, "label": "HL7 FHIR", "value": "HL7 FHIR", "checked": false},
                    {"id": 51, "label": "HL7 V2", "value": "HL7 V2", "checked": false},
                    {"id": 52, "label": "HL7 CDA", "value": "HL7 CDA", "checked": false},
                    {"id": 53, "label": "HL7 CCOW", "value": "HL7 CCOW", "checked": false},
                    {"id": 54, "label": "DICOM", "value": "DICOM", "checked": false},
                    {"id": 55, "label": "I2B2", "value": "I2B2", "checked": false},
                    {"id": 56, "label": "IHE", "value": "IHE", "checked": false},
                    {"id": 57, "label": "HSE", "value": "HSE", "checked": false},
                    {"id": 58, "label": "OMOP", "value": "OMPO", "checked": false}
                ],
                "highlighted":[] 
            },
            {
                "id": 8,
                "label": "Language",
                "key": "language",
                "tooltip": "tooltip lang",
                "closed": true, 
                "isSearchable": false,
                "selectedCount": 0,
                "filters": [
                    {"id": 60, "label": "Afar", "value": "Afar", "checked": false},
                    {"id": 61, "label": "Abkhazian", "value": "Abkhazian", "checked": false},
                    {"id": 62, "label": "Afrikaans", "value": "Afrikaans", "checked": false},
                    {"id": 63, "label": "Akan", "value": "Akan", "checked": false},
                    {"id": 64, "label": "Albanian", "value": "Albanian", "checked": false},
                    {"id": 65, "label": "Amharic", "value": "Amharic", "checked": false},
                    {"id": 66, "label": "Arabic", "value": "Arabic", "checked": false},
                    {"id": 67, "label": "Aragonese", "value": "Aragonese", "checked": false},
                    {"id": 68, "label": "English", "value": "English", "checked": false},
                    {"id": 69, "label": "French", "value": "French", "checked": false},
                    {"id": 70, "label": "Spanish", "value": "Spanish", "checked": false},
                ],
                "highlighted":[] 
            }
        ]
    },
    {
        "id": 9,
        "label": "Data utility",
        "key": "dataUtility",
        "tooltip": null,
        "closed": true,
        "isSearchable": false, 
        "selectedCount": 0,
        "filters": [
            {
                "id": 10,
                "label": "Documentation",
                "key": "documentation",
                "tooltip": null,
                "closed": true,
                "isSearchable": false, 
                "selectedCount": 0,
                "filters": [{ "id": 32, "label": "English", "value": "English", "checked": false}],
                "highlighted":[]
            },
            {
                "id": 11,
                "label": "Technical quality",
                "key": "technicalQuality",
                "tooltip": null,
                "closed": true,
                "isSearchable": false,
                "selectedCount": 0,
                "filters": [{"id": 33, "label": "English", "value": "English", "checked": false}],
                "highlighted":[]
            },
            {
                "id": 12,
                "label": "Value and interest",
                "key": "valueAndInterest",
                "tooltip": null,
                "closed": true,
                "isSearchable": false,
                "selectedCount": 0, 
                "filters": [{"id": 34, "label": "English", "value": "English", "checked": false}],
                "highlighted":[]
            },
            {
                "id": 13,
                "label": "Access and provision",
                "key": "accessAndProvision",
                "tooltip": null,
                "closed": true,
                "isSearchable": false,
                "selectedCount": 0, 
                "filters": [{"id": 35, "label": "English", "value": "English", "checked": false}],
                "highlighted":[]
            },
            {
                "id": 14,
                "label": "Coverage",
                "tooltip": null,
                "key": "coverage",
                "closed": true,
                "isSearchable": false, 
                "selectedCount": 0,
                "filters": [
        {
          "id": 15,
          "label": "Pathway coverage",
                        "key": "pathWayCoverage",
          "tooltip": null,
                        "closed": true,
                        "isSearchable": false,
                        "selectedCount": 0,
          "filters": [
                            {"id": 71, "label": "Contains data from a single speciality or area", "value": "Contains data from a single speciality or area", "checked": false},
                            {"id": 72, "label": "Contains data from multiple specialties or services within a single tier of care", "value": "Contains data from multiple specialties or services within a single tier of care", "checked": false},
                            {"id": 73, "label": "Contains multimodal data or data that is linked across two tiers (e.g. primary and secondary care)", "value": "Contains multimodal data or data that is linked across two tiers (e.g. primary and secondary care)", "checked": false},
                            {"id": 74, "label": "Contains data across the whole pathway of care", "value": "Contains data across the whole pathway of care", "checked": false},
                            {"id": 75, "label": "Contains data...", "value": "Contains data ...", "checked": false},
                            {"id": 76, "label": "Contains data across ...", "value": "Contains data across ...", "checked": false},
                            {"id": 77, "label": "NHS data across the whole pathway of care", "value": "NHS data across the whole pathway of care", "checked": false},
                            {"id": 78, "label": "COVID data across the whole pathway", "value": "COVID data across the whole pathway", "checked": false},
                        ],
                        "highlighted":[]
        },
        {
          "id": 16,
          "label": "Length of follow-up",
                        "key": "lengthOfFollowUp",
          "tooltip": null,
                        "closed": true,
                        "isSearchable": false,
                        "selectedCount": 0, 
          "filters": [{"id": 37, "label": "English", "value": "English", "checked": false}],
                        "highlighted":[]
        }
      ]
            }
        ]
    }
];