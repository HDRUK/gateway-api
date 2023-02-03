const mockArrayCheckRolesEmptyRole = [];
const mockArrayCheckRolesOneRole = ["custodian.dar.manager"];
const mockArrayCheckRolesMultiRole = ["custodian.dar.manager", "reviewer"];
const mockArrayCheckRolesManagerRole = ["manager"];
const mockTeam = {
    "_id": "5f3f98068af2ef61552e1d75",
    "active": true,
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
    "notifications": [
      {
        "notificationType": "dataAccessRequest",
        "optIn": false,
        "subscribedEmails": [
          "nita.dan2@gmail.com"
        ],
        "_id": "62e79fb6892f2f85d9bc2555"
      }
    ],
    "__v": 11,
    "updatedAt": "2022-12-09T15:05:33.512Z"
};
const mockUserId = "61f91d232e175937b960e213";

export {
    mockArrayCheckRolesEmptyRole,
    mockArrayCheckRolesOneRole,
    mockArrayCheckRolesMultiRole,
    mockArrayCheckRolesManagerRole,
    mockTeam,
    mockUserId,
}