const mockTeamWithPublisher = {
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
      }
    ],
    "notifications": [],
    "type": "publisher",
    "createdAt": "2023-01-09T10:27:04.376Z",
    "updatedAt": "2023-01-23T13:59:48.253Z",
    "__v": 25,
    "publisher": {
      "_id": "63bbebf8ec565a91c474cd1b",
      "name": "ALLIANCE > Test40"
    },
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
        "discourseUsername": "kymme.hayley"
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
        "createdAt": "2020-09-04T00:00:00.000Z"
      }
    ]
};

const mockTeamWithoutPublisher = {
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
        "discourseUsername": "kymme.hayley"
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
        "createdAt": "2020-09-04T00:00:00.000Z"
      }
    ]
};

export {
    mockTeamWithPublisher,
    mockTeamWithoutPublisher,
}