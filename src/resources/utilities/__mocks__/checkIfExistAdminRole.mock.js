const mockMembersTrue = [
    {
      "roles": [
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
    }
];
const mockMembersFalse = [
    {
      "roles": [
        "editor"
      ],
      "memberid": "61128e7ef7ff9cee652532b4",
      "notifications": []
    }
];
const mockRolesAuth = ["custodian.team.admin","custodian.dar.manager","custodian.metadata.manager"];

export {
    mockMembersTrue,
    mockMembersFalse,
    mockRolesAuth
}
