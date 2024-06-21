const mockTeam = {
    "active": true,
    "_id": "5f7b1a2bce9f65e6ed83e7da",
    "members": [
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
        "memberid": "62d691a49901cef16d8da801",
        "notifications": []
      },
      {
        "roles": [
          "reviewer",
          "custodian.dar.manager",
          "custodian.team.admin",
          "metadata_editor"
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
        "memberid": "62384f08e5c7245adf17f0fd",
        "notifications": []
      },
      {
        "roles": [
          "reviewer",
          "metadata_editor"
        ],
        "memberid": "62f502413e9bf5e82256d63b",
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
          "custodian.team.admin",
          "admin_dataset"
        ],
        "memberid": "63d2bfcbd4663faea8745ae6",
        "notifications": []
      },
      {
        "roles": [
          "custodian.team.admin",
          "custodian.dar.manager"
        ],
        "memberid": "6308bfd1d2ff69e6c13427e7",
        "notifications": []
      },
      {
        "roles": [
          "custodian.dar.manager",
          "reviewer",
          "custodian.team.admin"
        ],
        "memberid": "63d27a30a5959c9bfc72caa2",
        "notifications": []
      },
      {
        "roles": [
          "custodian.metadata.manager",
          "custodian.dar.manager",
          "reviewer"
        ],
        "memberid": "63d3cb845487686dad9552ea",
        "notifications": []
      },
      {
        "roles": [
          "manager"
        ],
        "memberid": "634c518998e119341680d558",
        "notifications": []
      },
      {
        "roles": [
          "reviewer",
          "custodian.dar.manager",
          "custodian.team.admin",
          "custodian.metadata.manager"
        ],
        "memberid": "63dd1225a87ca70692fcddcf",
        "notifications": []
      },
      {
        "roles": [
          "reviewer"
        ],
        "memberid": "61f91d232e175937b960e213",
        "notifications": []
      }
    ],
    "type": "publisher",
    "notifications": [
      {
        "notificationType": "dataAccessRequest",
        "optIn": true,
        "subscribedEmails": [
          "vijisrisan@gmail.com",
          "hello@gmail.com"
        ],
        "_id": "6384dcce285c42274308a947"
      }
    ],
    "__v": 369,
    "createdAt": "2020-12-11T10:46:22.406Z",
    "updatedAt": "2023-02-13T13:19:02.636Z"
};
const mockResponse = [
    {
      "notificationType": "dataAccessRequest",
      "optIn": true,
      "subscribedEmails": [
        {
          "value": "vijisrisan@gmail.com",
          "error": ""
        },
        {
          "value": "hello@gmail.com",
          "error": ""
        }
      ]
    }
];

export {
    mockTeam,
    mockResponse,
}