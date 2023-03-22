export const mockCohorts = [
    {
        _id: "610aabea83eb3f2a4d33ddd1",
        pid: "9a41b63f-5ec5-4966-9d70-f718df24a395",
        description: "a test 1",
        uploaders: [8470291714590256,8470291714590257],
        cohort: {
            stuff: "stuff"
        },
        version: 1,
        changeLog: "",
        testArr: ["test1", "test2"],
        id: 1234,
        name: "Cohort One",
        updatedAt: "2021-10-07T14:43:55.508Z",
        activeflag: "archived_version",
        type: "cohort",
        relatedObjects: [{
            _id: "6141fae77e4d8d8f758e9fb6",
            objectId: "4050303073977839",
            objectType: "project",
            user: "User Name One",
            updated: "21 May 2021"
        }, {
            _id: "6141fb4f7e4d8d8f758e9fb7",
            objectId: "6061998693684476",
            reason: "cohort add via db",
            objectType: "tool",
            user: "User Name One",
            updated: "11 September 2021"
        }, {
            _id: "61431817508c5aa2dce95cdb",
            objectId: "5d76d094-446d-4dcc-baa1-076095f30c23",
            objectType: "dataset",
            pid: "0bb8d80b-4d92-4bcb-84b7-5a1ff1f86a33",
            user: "User Name One",
            updated: "16 September 2021",
            isLocked: true
        }, {
            _id: "614321de508c5aa2dce95cdc",
            objectId: "c6d6bbd3-74ed-46af-841d-ac5e05f4da41",
            objectType: "dataset",
            pid: "f725187f-7352-482b-a43b-64ebc96e66f2",
            user: "User Name One",
            updated: "16 September 2021",
            isLocked: true
        }],
        "publicflag": true,
        "datasetPids": []
    },
    {
        _id: "610aac0683eb3f2a4d33ddd2",
        pid: "abc12a3",
        description: "a test 2",
        uploaders: [8470291714590256,8470291714590257],
        cohort: {
            stuff: "4444"
        },
        version: 1,
        changeLog: "",
        id: 3456,
        name: "Cohort Two",
        updatedAt: "2021-10-20T13:23:09.093Z",
        activeflag: "active",
        type: "cohort",
        publicflag: false,
        relatedObjects: [{
            _id: "614dcb0e1b5e0aa5019aee12",
            objectId: "5d76d094-446d-4dcc-baa1-076095f30c23",
            objectType: "dataset",
            pid: "0bb8d80b-4d92-4bcb-84b7-5a1ff1f86a33",
            user: "User Name One",
            updated: "6 September 2021",
            isLocked: true
        }, {
            _id: "6155ad4116113e65c26a8a4c",
            objectId: "4050303073977839",
            objectType: "project",
            user: "User Name One",
            updated: "28 September 2021"
        }, {
            _id: "6155ada116113e65c26a8a4d",
            reason: "cohort add via db",
            objectType: "tool",
            user: "User Name One",
            updated: "29 September 2021",
            objectId: "6061998693684476"
        }],
        "datasetPids": []
    }
];