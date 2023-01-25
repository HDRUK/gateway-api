const currUserId = '61f91d232e175937b960e213';
const permission = 'manager';

const mockTeam = {
    "active": true,
    "_id": "63bbebf8ec565a91c474cd1b",
    "members": [
      {
        "roles": [
          "manager",
          "custodian.team.admin",
          "custodian.metadata.manager",
          "custodian.dar.manager"
        ],
        "memberid": "6308bfd1d2ff69e6c13427e7",
        "notifications": []
      },
      {
        "roles": [
          "reviewer"
        ],
        "memberid": "5e8c3823e63e5d83ac27c347",
        "notifications": []
      }
    ],
    "notifications": [],
    "type": "publisher",
    "createdAt": "2023-01-09T10:27:04.376Z",
    "updatedAt": "2023-01-23T13:59:48.253Z",
    "__v": 25,
    "users": [
      {
        "feedback": false,
        "news": false,
        "isServiceAccount": false,
        "advancedSearchRoles": [],
        "_id": "6308bfd1d2ff69e6c13427e7",
        "id": 6531262197049297,
        "providerId": "102868775144293907483",
        "provider": "google",
        "firstname": "kymme",
        "lastname": "hayley",
        "email": "kymme@hdruk.dev",
        "role": "Admin",
        "createdAt": "2022-08-26T12:42:57.116Z",
        "updatedAt": "2023-01-06T16:08:52.920Z",
        "__v": 0,
        "redirectURL": "/search?search=&tab=Datasets",
        "discourseKey": "fa596dcd0486d6919c9ee98db5eb00429341d266e275c3c5d8b95b21ff27b89f",
        "discourseUsername": "kymme.hayley",
        "additionalInfo": {
          "emailNotifications": true,
          "showOrganisation": true,
          "id": 6531262197049297,
          "bio": "",
          "link": "",
          "orcid": "https://orcid.org/",
          "activeflag": "active",
          "terms": true,
          "organisation": "HDR UK",
          "showBio": true
        }
      },
      {
        "feedback": false,
        "news": false,
        "isServiceAccount": false,
        "advancedSearchRoles": [],
        "_id": "5e8c3823e63e5d83ac27c347",
        "id": 5890232553870074,
        "providerId": "102167422686846649659",
        "provider": "google",
        "firstname": "Ciara",
        "lastname": "Ward",
        "email": "ciara.ward@paconsulting.com",
        "password": null,
        "role": "Creator",
        "__v": 0,
        "discourseKey": "a23c62c2f9b06f0873a567522ac585a288af9fa8ec7b62eeec68baebef1cdf10",
        "discourseUsername": "ciara.ward",
        "updatedAt": "2021-05-12T09:51:49.573Z",
        "createdAt": "2020-09-04T00:00:00.000Z",
        "additionalInfo": {
          "emailNotifications": true,
          "showOrganisation": false,
          "id": 5890232553870074,
          "activeflag": "active",
          "bio": "",
          "link": "",
          "orcid": "https://orcid.org/undefined",
          "organisation": "",
          "terms": true,
          "showBio": true
        }
      }
    ]
};
const mockUsers = {
    "feedback": false,
    "news": false,
    "isServiceAccount": false,
    "advancedSearchRoles": [],
    "_id": "61f91d232e175937b960e213",
    "id": 42412943236984630,
    "providerId": "100742128864395249791",
    "provider": "google",
    "firstname": "Dan",
    "lastname": "Nita",
    "email": "dan.nita.hdruk@gmail.com",
    "role": "Admin",
    "createdAt": "2022-02-01T11:44:35.584Z",
    "updatedAt": "2022-11-29T10:26:06.318Z",
    "__v": 0,
    "redirectURL": "/search?search=&tab=Datasets",
    "discourseKey": "12e3760a22942a100ea08036b93837a01cf615988fc7d312e50d733d9367d861",
    "discourseUsername": "dan.nita",
    "teams": [
      {
        "members": [
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "623483baff441ae7fec9fd43",
            "notifications": [
              {
                "optIn": true,
                "_id": "606ddfb9130e8f3f3d431801",
                "notificationType": "dataAccessRequest",
                "message": ""
              }
            ]
          },
          {
            "roles": [
              "manager",
              "metadata_editor",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "notifications": [],
            "memberid": "61825367cefce1bfe5c9ba7c"
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61f91d232e175937b960e213",
            "notifications": [
              {
                "optIn": true,
                "_id": "62837ba8a35a2205b0f0a0b6",
                "notificationType": "dataAccessRequest"
              }
            ]
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62028ae4d62405c442fd383f",
            "notifications": [
              {
                "optIn": true,
                "_id": "628cc003cc879f3d43852b8f",
                "notificationType": "dataAccessRequest"
              }
            ]
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62d945d9fb5b536d1520c618",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "5ec2a116b293e07eb48afe14",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62384f08e5c7245adf17f0fd",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61ddbbcdd05e7f703fc3190d",
            "notifications": []
          }
        ],
        "type": "publisher",
        "publisher": {
          "_id": "5f8992a97150a1b050be0712",
          "name": "ALLIANCE > PUBLIC HEALTH SCOTLAND"
        }
      },
      {
        "members": [
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61f91d232e175937b960e213",
            "notifications": [
              {
                "optIn": true,
                "_id": "606ddfb9130e8f3f3d431801",
                "notificationType": "dataAccessRequest",
                "message": ""
              }
            ]
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "613f2c40e8592f8d3add7add",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "metadata_editor",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "notifications": [],
            "memberid": "61825367cefce1bfe5c9ba7c"
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61d489c85a45342ce44b0bc9",
            "notifications": []
          },
          {
            "roles": [
              "metadata_editor"
            ],
            "memberid": "61d489c85a45342ce44b0bc9",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "623483baff441ae7fec9fd43",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62d945d9fb5b536d1520c618",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "623467f6ce42aab1cfc29021",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "5ec2a116b293e07eb48afe14",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62384f08e5c7245adf17f0fd",
            "notifications": []
          },
          {
            "roles": [
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62028ae4d62405c442fd383f",
            "notifications": []
          },
          {
            "roles": [
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61ddbbcdd05e7f703fc3190d",
            "notifications": []
          }
        ],
        "type": "publisher",
        "publisher": {
          "_id": "5f3f98068af2ef61552e1d75",
          "name": "ALLIANCE > SAIL"
        }
      },
      {
        "members": [
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61128e7ef7ff9cee652532b4",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "613f2c40e8592f8d3add7add",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "reviewer",
              "metadata_editor",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61825367cefce1bfe5c9ba7c",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61f91d232e175937b960e213",
            "notifications": [
              {
                "optIn": true,
                "_id": "623c81f5aa033e0e643d6c6a",
                "notificationType": "dataAccessRequest"
              }
            ]
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "618008d49566b41988d1be02",
            "notifications": [
              {
                "optIn": true,
                "_id": "62c40c6ab1079d7d4ee1c608",
                "notificationType": "dataAccessRequest"
              }
            ]
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "notifications": [],
            "memberid": "61b9c46dfbd7e9f3aa270ac1"
          },
          {
            "roles": [
              "manager",
              "reviewer",
              "metadata_editor",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "6193e832536f42aa8f976fdc",
            "notifications": [
              {
                "optIn": true,
                "_id": "623c515eaa033e6d0d3d6473",
                "notificationType": "dataAccessRequest"
              }
            ]
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62349f5f767db5d3408b4007",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62028ae4d62405c442fd383f",
            "notifications": [
              {
                "optIn": true,
                "_id": "62839a8883f55d40d3d20cf4",
                "notificationType": "dataAccessRequest"
              }
            ]
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "623483baff441ae7fec9fd43",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62e79d3a892f2fbc28bc233b",
            "notifications": [
              {
                "optIn": true,
                "_id": "62ebe58bd7595507ba81d689",
                "notificationType": "dataAccessRequest"
              }
            ]
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62d691a49901cef16d8da801",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62d5e29ad375f198868e4dc7",
            "notifications": [
              {
                "optIn": true,
                "_id": "62ed1f7e7d1af36e6d280b8e",
                "notificationType": "dataAccessRequest"
              }
            ]
          },
          {
            "roles": [
              "reviewer"
            ],
            "memberid": "632c325de4e074719a8c13de",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "5ec2a116b293e07eb48afe14",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62384f08e5c7245adf17f0fd",
            "notifications": []
          },
          {
            "roles": [
              "reviewer"
            ],
            "memberid": "62f502413e9bf5e82256d63b",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "reviewer",
              "metadata_editor",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "5ef9c4ebb9796854174cbf94",
            "notifications": []
          },
          {
            "roles": [
              "reviewer",
              "metadata_editor",
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61dd491e4d274e7eac3b848e",
            "notifications": []
          },
          {
            "roles": [
              "reviewer",
              "metadata_editor",
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61f91d232e175937b960e213",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61ddbbcdd05e7f703fc3190d",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "6308bfd1d2ff69e6c13427e7",
            "notifications": []
          }
        ],
        "type": "publisher",
        "publisher": {
          "_id": "5f7b1a2bce9f65e6ed83e7da",
          "name": "OTHER > HEALTH DATA RESEARCH UK"
        }
      },
      {
        "members": [
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61f91d232e175937b960e213",
            "notifications": [
              {
                "optIn": true,
                "_id": "606ddfb9130e8f3f3d431801",
                "notificationType": "dataAccessRequest",
                "message": ""
              }
            ]
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "5f8563c6fa9a256698a9fafb",
            "notifications": [
              {
                "optIn": true,
                "_id": "606ddfb9130e8f3f3d431801",
                "notificationType": "dataAccessRequest",
                "message": ""
              }
            ]
          },
          {
            "roles": [
              "manager",
              "metadata_editor",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "notifications": [],
            "memberid": "61825367cefce1bfe5c9ba7c"
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61d489c85a45342ce44b0bc9",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "notifications": [],
            "memberid": "61854c1e46f52ed51754bb24"
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "60191005c13ca825e4c6cadd",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "623483baff441ae7fec9fd43",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62028ae4d62405c442fd383f",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62d945d9fb5b536d1520c618",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "5ec2a116b293e07eb48afe14",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62384f08e5c7245adf17f0fd",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61ddbbcdd05e7f703fc3190d",
            "notifications": []
          }
        ],
        "type": "publisher",
        "publisher": {
          "_id": "5f89662f7150a1b050be0710",
          "name": "ALLIANCE > HEALTH AND SOCIAL CARE NORTHERN IRELAND"
        }
      },
      {
        "members": [
          {
            "roles": [
              "admin_dataset"
            ],
            "memberid": "5e725692c9a581a0dd2bd84b",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset"
            ],
            "memberid": "5e726049c9a58131cd2bd874",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset",
              "admin_data_use"
            ],
            "memberid": "611a58bb9f17737532ad25c0",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset"
            ],
            "memberid": "6139c0d1e6b81112ad5e0312",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset",
              "admin_data_use"
            ],
            "memberid": "618008d49566b41988d1be02",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset"
            ],
            "memberid": "61825367cefce1bfe5c9ba7c",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset"
            ],
            "memberid": "6167edbd5306ac30d5b1da14",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset"
            ],
            "memberid": "619261978f832546e0b0edfd",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset"
            ],
            "memberid": "619501876b8724ad01077af6",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset"
            ],
            "memberid": "61978834a87688d4e67770fa",
            "notifications": []
          },
          {
            "roles": [
              "admin_data_use"
            ],
            "memberid": "619ba1e87d2fd8db23885ce9",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset",
              "admin_data_use"
            ],
            "memberid": "61d489c85a45342ce44b0bc9",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset",
              "admin_data_use"
            ],
            "memberid": "61b9c46dfbd7e9f3aa270ac1",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset",
              "admin_data_use"
            ],
            "memberid": "613a0399e6b8113f0a5e0ccc",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset",
              "admin_data_use"
            ],
            "memberid": "61a5fda2b2d8db3617d85b4b",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset",
              "admin_data_use"
            ],
            "memberid": "61854c1e46f52ed51754bb24",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset",
              "admin_data_use"
            ],
            "memberid": "62349a67767db5d3408b4001",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset",
              "admin_data_use"
            ],
            "memberid": "623483baff441ae7fec9fd43",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset"
            ],
            "memberid": "61e93915826d995980cca3dc",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset",
              "admin_data_use"
            ],
            "memberid": "62e79d3a892f2fbc28bc233b",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset",
              "admin_data_use"
            ],
            "memberid": "6308bfd1d2ff69e6c13427e7",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "5ec2a116b293e07eb48afe14",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62384f08e5c7245adf17f0fd",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62028ae4d62405c442fd383f",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "626a72ba4524adcf224b769b",
            "notifications": []
          },
          {
            "roles": [
              "admin_dataset",
              "admin_data_use"
            ],
            "memberid": "61f91d232e175937b960e213",
            "notifications": []
          }
        ],
        "type": "admin",
        "publisher": null
      },
      {
        "members": [
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61825367cefce1bfe5c9ba7c",
            "notifications": []
          },
          {
            "roles": [
              "reviewer"
            ],
            "memberid": "61d489c85a45342ce44b0bc9",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "623483baff441ae7fec9fd43",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "611a58bb9f17737532ad25c0",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "618008d49566b41988d1be02",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61f91d232e175937b960e213",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62028ae4d62405c442fd383f",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "613f2c40e8592f8d3add7add",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62349f5f767db5d3408b4007",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61ddbbcdd05e7f703fc3190d",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62e79d3a892f2fbc28bc233b",
            "notifications": [
              {
                "optIn": true,
                "_id": "62f2335e062fcd05b9e8d2cf",
                "notificationType": "dataAccessRequest"
              }
            ]
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62d691a49901cef16d8da801",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62d5e29ad375f198868e4dc7",
            "notifications": [
              {
                "optIn": true,
                "_id": "62f241e8f15b29ce1c24fd96",
                "notificationType": "dataAccessRequest"
              }
            ]
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62f502413e9bf5e82256d63b",
            "notifications": []
          },
          {
            "roles": [
              "reviewer"
            ],
            "memberid": "62c80ec7970e8a07797c23c7",
            "notifications": []
          },
          {
            "roles": [
              "metadata_editor"
            ],
            "memberid": "6318aab2f051c6375ec53b7e",
            "notifications": []
          },
          {
            "roles": [
              "reviewer"
            ],
            "memberid": "632c325de4e074719a8c13de",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61ddbd0628f427fb9473287b",
            "notifications": []
          },
          {
            "roles": [
              "reviewer"
            ],
            "memberid": "5fc8e2b7c386587231140f85",
            "notifications": []
          },
          {
            "roles": [
              "metadata_editor"
            ],
            "memberid": "5f7c2bcd504d5e7cda30b3ea",
            "notifications": []
          },
          {
            "roles": [
              "metadata_editor"
            ],
            "memberid": "5e851759b9bbd5ecd9f65a39",
            "notifications": []
          },
          {
            "roles": [
              "reviewer"
            ],
            "memberid": "634c518998e119341680d558",
            "notifications": []
          },
          {
            "roles": [
              "metadata_editor"
            ],
            "memberid": "61a5fda2b2d8db3617d85b4b",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "5ec2a116b293e07eb48afe14",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62384f08e5c7245adf17f0fd",
            "notifications": []
          }
        ],
        "type": "publisher",
        "publisher": {
          "_id": "61e57fd3012bda94e0e8b9c6",
          "name": "OTHER > Priti Test Team"
        }
      },
      {
        "members": [
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "611a58bb9f17737532ad25c0",
            "notifications": [
              {
                "optIn": true,
                "_id": "62389e82e5c72465f718013f",
                "notificationType": "dataAccessRequest"
              }
            ]
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "623483baff441ae7fec9fd43",
            "notifications": []
          },
          {
            "roles": [
              "reviewer"
            ],
            "memberid": "61ddbd0628f427fb9473287b",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "618008d49566b41988d1be02",
            "notifications": []
          },
          {
            "roles": [
              "metadata_editor"
            ],
            "memberid": "613a0399e6b8113f0a5e0ccc",
            "notifications": []
          },
          {
            "roles": [
              "reviewer"
            ],
            "memberid": "6139c0d1e6b81112ad5e0312",
            "notifications": []
          },
          {
            "roles": [
              "reviewer"
            ],
            "memberid": "61cc3966928507fe0f1b9325",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "61f91d232e175937b960e213",
            "notifications": [
              {
                "optIn": true,
                "_id": "6255a87db588c01f5250a3ce",
                "notificationType": "dataAccessRequest"
              }
            ]
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "613f2c40e8592f8d3add7add",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "5ec2a116b293e07eb48afe14",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62384f08e5c7245adf17f0fd",
            "notifications": []
          },
          {
            "roles": [
              "manager",
              "custodian.team.admin",
              "custodian.metadata.manager",
              "custodian.dar.manager"
            ],
            "memberid": "62028ae4d62405c442fd383f",
            "notifications": []
          }
        ],
        "type": "publisher",
        "publisher": {
          "_id": "62024195f0e15612a4e16979",
          "name": "OTHER > Test1"
        }
      }
    ]
};

export {
    currUserId,
    permission,
    mockTeam,
    mockUsers,
}