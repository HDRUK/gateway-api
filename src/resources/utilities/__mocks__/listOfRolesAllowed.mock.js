const mockUserRoles = [
    "admin_dataset",
    "admin_data_use",
    "manager",
    "custodian.team.admin",
    "custodian.metadata.manager",
    "custodian.dar.manager"
];
const mockRolesAcceptedByRoles = {
    "custodian.team.admin": [
      "custodian.team.admin",
      "custodian.metadata.manager",
      "custodian.metadata.editor",
      "custodian.developer",
      "custodian.dar.manager",
      "custodian.dar.reviewer",
      "custodian.dur.manager"
    ],
    "custodian.metadata.manager": [
      "custodian.metadata.manager",
      "custodian.metadata.editor"
    ],
    "custodian.metadata.editor": [
      "custodian.metadata.editor"
    ],
    "custodian.developer": [
      "custodian.developer"
    ],
    "custodian.dar.manager": [
      "custodian.dar.manager",
      "custodian.dar.reviewer"
    ],
    "custodian.dar.reviewer": [
      "custodian.dar.reviewer"
    ],
    "custodian.dur.manager": [
      "custodian.dur.manager"
    ]
};
const mockResponse = ["custodian.team.admin","custodian.metadata.manager","custodian.metadata.editor","custodian.developer","custodian.dar.manager","custodian.dar.reviewer","custodian.dur.manager"];

export {
    mockUserRoles,
    mockRolesAcceptedByRoles,
    mockResponse,
}