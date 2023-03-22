import HttpExceptions from "../../../../exceptions/HttpExceptions";
import constants from "../../../utilities/constants.util";

const subjectEmail = (teamName = '', userName = '', role, status) => {
    let subject = '';
    let publisherName = '';

    if (teamName.search('>') === -1) {
        publisherName = teamName.trim();
    } else {
        publisherName = teamName.split('>')[1].trim();
    }

    switch (role) {
        case 'custodian.team.admin':
            if (status) {
                subject = `${userName} has added you to the ${publisherName} publishing team on the Gateway as a Team Admin`;
            } else {
                subject = `You have been removed as a Team Admin for the ${publisherName} team on the Gateway.`;
            }
            break;
        case 'custodian.metadata.manager':
            if (status) {
                subject = `${userName} has added you to the ${publisherName} publishing team on the Gateway as a Metadata Manager`;
            } else {
                subject = `You have been removed as a Metadata Manager for the ${publisherName} team on the Gateway`;
            }
            break;
        case 'metadata_editor':
            if (status) {
                subject = `${userName} has added you to the ${publisherName} publishing team on the Gateway as a Metadata Editor`;
            } else {
                subject = `You have been removed as a Metadata Editor for the ${publisherName} team on the Gateway.`;
            }
            break;
        case 'custodian.dar.manager':
            if (status) {
                subject = `${userName} has added you to the ${publisherName} publishing team on the Gateway as a Data Access Manager`;
            } else {
                subject = `You have been removed as a Data Access Manager for the ${publisherName} team on the Gateway.`;
            }
            break;
        case 'reviewer':
            if (status) {
                subject = `${userName} has added you to the ${publisherName} publishing team on the Gateway as a Reviewer`;
            } else {
                subject = `You have been removed as a Reviewer for the ${publisherName} team on the Gateway.`;
            }
            break;
        default:            
            break;
    }

    return subject;
}

const bodyEmail = (teamName = '', currentUserName = '', userName = '', role, status, teamId, team) => {
    const urlHdrukLogoEmail = 'https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg';
    const urlHdrukHeaderEmail = 'https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg';

    let topBodyEmail = '';
    let middleBodyEmail = ''; 
    let footerBodyEmail = ''; 
    let bodyEmail = '';

    let publisherName = '';

    if (teamName.search('>') === -1) {
        publisherName = teamName.trim();
    } else {
        publisherName = teamName.split('>')[1].trim();
    }
    const urlDatasetsTeam = `${process.env.homeURL}/search?search=&datasetpublisher=${publisherName}&datasetSort=latest&tab=Datasets`;
    const urlDataAccessRequestTeam = `${process.env.homeURL}/account?tab=dataaccessrequests&teamType=team&teamId=${teamId}`;
    const urlManageTeam = `${process.env.homeURL}/account?tab=teamManagement&teamType=team&teamId=${teamId}&subTab=members`;

    const teamAdmin = _generateTeamAdmin(team);

    topBodyEmail = `
        <style>
            @import url('https://fonts.cdnfonts.com/css/museo-sans-rounded');
            a:link { text-decoration: none; }
            a:visited { text-decoration: none; }
            a:hover { text-decoration: none; }
            a:active { text-decoration: none; }
            a.button {
                padding:10px;
                width:auto;
                -webkit-border-radius:5px;
                -moz-border-radius:5px;
                border-radius:5px;
                background-color:#00ACCA;
                color:#FFFFFF;
            }
            a.button:hover {
                -webkit-border-radius:5px;
                -moz-border-radius:5px;
                border-radius:5px;
                background-color:#3b4c93;
                color:#FFFFFF;
            }
        </style>

        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="font-family:'Museo Sans Rounded', sans-serif;font-size:14px; color:#3C3C3B">
        <tr>
            <td align="center">
                <table width="836px" cellspacing="0" cellpadding="0">
                    <tr>
                        <td align="center">
                            <img 
                                src="${urlHdrukLogoEmail}" 
                                alt="Health Data Gateway" 
                                style="width:226px;height:80px;margin:40px 0;">
                        </td>
                    </tr>
    `;

    switch (role) {
        case 'custodian.team.admin':
            if (status) {
                middleBodyEmail = `
                    <tr>
                        <td style="background-image:url(${urlHdrukHeaderEmail});background-repeat:no-repeat;height:160px;font-size:32px;color:white;text-align:center;">
                            Custodian Team Admin has been assigned
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 10px 10px 10px;">
                            ${currentUserName} has added you to the ${publisherName} publishing team on the Gateway as a Team Admin.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;line-height:1.5em;">
                            You can now add, remove, and change the roles of other members of the ${publisherName} team.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px;text-align:center">
                            <a href='${urlManageTeam}' class='button'>Manage team</a>
                        </td>
                    </tr>
                `;
            } else {
                middleBodyEmail = `
                    <tr>
                        <td style="background-image:url(${urlHdrukHeaderEmail});background-repeat:no-repeat;height:160px;font-size:32px;color:white;text-align:center;">
                            Custodian Team Admin has been removed
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 10px 10px 10px;">
                            You have been removed as a Team Admin for the ${publisherName} team on the Gateway.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;">
                            You can no longer:<br/>
                            <ul>
                                <li style="line-height:20px;height:auto;">Add roles of other members of the ${publisherName} team.</li>
                                <li style="line-height:20px;height:auto;">Remove roles of other members of the ${publisherName} team.</li>
                                <li style="line-height:20px;height:auto;">Change the roles of other members of the ${publisherName} team.</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;">
                            You can no longer:<br/>
                            <ul>
                                <li style="line-height:20px;height:auto;">Onboard and manage information about datasets uploaded by the ${publisherName} team.</li>
                                <li style="line-height:20px;height:auto;">Add and remove other team members with editor permissions.</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;line-height:1.5em;">
                            For more information, please contact a Team Admin for your team:<br/>
                            ${teamAdmin}
                        </td>
                    </tr>
                `;
            }
            break;
        case 'custodian.metadata.manager':
            if (status) {
                middleBodyEmail = `
                    <tr>
                        <td style="background-image:url(${urlHdrukHeaderEmail});background-repeat:no-repeat;height:160px;font-size:32px;color:white;text-align:center;">
                            Metadata Manager has been assigned
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 10px 10px 10px;">
                            ${currentUserName} has added you the ${publisherName} publishing team on the Gateway as a Metadata Manager.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;">
                            You can now:<br/>
                            <ul>
                                <li style="line-height:20px;height:auto;">Onboard and manage information about datasets uploaded by the ${publisherName} team.</li>
                                <li style="line-height:20px;height:auto;">Add and remove other team members with editor permissions.</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px;text-align:center">
                            <a href='${urlDatasetsTeam}' class='button'>View Datasets</a>
                        </td>
                    </tr>
                `;
            } else {
                middleBodyEmail = `
                    <tr>
                        <td style="background-image:url(${urlHdrukHeaderEmail});background-repeat:no-repeat;height:160px;font-size:32px;color:white;text-align:center;">
                            Metadata Manager has been removed
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 10px 10px 10px;">
                            You have been removed as a Metadata Manager for the ${publisherName} team on the Gateway.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;">
                            You can no longer:<br/>
                            <ul>
                                <li style="line-height:20px;height:auto;">Onboard and manage information about datasets uploaded by the ${publisherName} team.</li>
                                <li style="line-height:20px;height:auto;">Add and remove other team members with editor permissions.</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;line-height:1.5em;">
                            For more information, please contact a Team Admin for your team:<br/>
                            ${teamAdmin}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px;text-align:center">
                            <a href='${urlDatasetsTeam}' class='button'>View Datasets</a>
                        </td>
                    </tr>
                `;
            }
            break;
        case 'metadata_editor':
            if (status) {
                middleBodyEmail = `
                    <tr>
                        <td style="background-image:url(${urlHdrukHeaderEmail});background-repeat:no-repeat;height:160px;font-size:32px;color:white;text-align:center;">
                            Metadata Editor has been assigned
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 10px 10px 10px;">
                            ${currentUserName} has added you the ${publisherName} publishing team on the Gateway as a Metadata Editor.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;">
                            You can now:<br/>
                            <ul>
                                <li style="line-height:20px;height:auto;">Onboard information about datasets uploaded by the ${publisherName} team.</li>
                                <li style="line-height:20px;height:auto;">Manage information about datasets uploaded by the ${publisherName} team.</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px;text-align:center">
                            <a href='${urlDatasetsTeam}' class='button'>View Datasets</a>
                        </td>
                    </tr>
                `;
            } else {
                middleBodyEmail = `
                    <tr>
                        <td style="background-image:url(${urlHdrukHeaderEmail});background-repeat:no-repeat;height:160px;font-size:32px;color:white;text-align:center;">
                            Metadata Editor has been removed
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 10px 10px 10px;">
                            You have been removed as a Metadata Editor for the ${publisherName} team on the Gateway.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;">
                            You can no longer Onboard and manage information about datasets uploaded by the ${publisherName} team.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;line-height:1.5em;">
                            For more information, please contact a Team Admin for your team:<br/>
                            ${teamAdmin}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px;text-align:center">
                            <a href='${urlDatasetsTeam}' class='button'>View Datasets</a>
                        </td>
                    </tr>
                `;
            }
            break;
        case 'custodian.dar.manager':
            if (status) {
                middleBodyEmail = `
                    <tr>
                        <td style="background-image:url(${urlHdrukHeaderEmail});background-repeat:no-repeat;height:160px;font-size:32px;color:white;text-align:center;">
                            DAR Manager has been assigned
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 10px 10px 10px;">
                            ${currentUserName} has added you the ${publisherName} publishing team on the Gateway as a Data Access Manager.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;">
                            You can now:<br/>
                            <ul>
                                <li style="line-height:20px;height:auto;">Manage data access requests through the Gateway for the ${publisherName} team.</li>
                                <li style="line-height:20px;height:auto;">You can create and assign workflows, process applications, and communicate with applicants through the Gateway.</li>
                                <li style="line-height:20px;height:auto;">You can also add and remove other team members, and assign sections of the data access review workflow to them.</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px;text-align:center">
                            <a href='${urlDataAccessRequestTeam}' class='button'>View data access requests</a>
                        </td>
                    </tr>
                `;
            } else {
                middleBodyEmail = `
                    <tr>
                        <td style="background-image:url(${urlHdrukHeaderEmail});background-repeat:no-repeat;height:160px;font-size:32px;color:white;text-align:center;">
                            DAR Manager has been removed
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 10px 10px 10px;">
                            You have been removed as a Data Access Manager for the ${publisherName} team on the Gateway.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;">
                            You can no longer:<br/>
                            <ul>
                                <li style="line-height:20px;height:auto;">Manage data access requests through the Gateway for the ${publisherName} team.</li>
                                <li style="line-height:20px;height:auto;">Create and assign workflows, process applications, and communicate with applicants through the Gateway.</li>
                                <li style="line-height:20px;height:auto;">Add and remove other team members.</li>
                                <li style="line-height:20px;height:auto;">Assign sections of the data access review workflow to them.</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;line-height:1.5em;">
                            For more information, please contact a Team Admin for your team:<br/>
                            ${teamAdmin}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px;text-align:center">
                            <a href='${urlDatasetsTeam}' class='button'>View Datasets</a>
                        </td>
                    </tr>
                `;
            }
            break;
        case 'reviewer':
            if (status) {
                middleBodyEmail = `
                    <tr>
                        <td style="background-image:url(${urlHdrukHeaderEmail});background-repeat:no-repeat;height:160px;font-size:32px;color:white;text-align:center;">
                            DAR Reviewer has been assigned
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 10px 10px 10px;">
                            ${currentUserName} has added you to the ${publisherName} publishing team on the Gateway as a Reviewer.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;">
                            You can now:<br/>
                            <ul>
                                <li style="line-height:20px;height:auto;">Review sections of a data access request that have been assigned to you by a Data Access Manager for the ${publisherName} team.</li>
                                <li style="line-height:20px;height:auto;">You can process applications and communicate with applicants through the Gateway.</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px;text-align:center">
                            <a href='${urlDataAccessRequestTeam}' class='button'>View data access requests</a>
                        </td>
                    </tr>
                `;
            } else {
                middleBodyEmail = `
                    <tr>
                        <td style="background-image:url(${urlHdrukHeaderEmail});background-repeat:no-repeat;height:160px;font-size:32px;color:white;text-align:center;">
                            DAR Reviewer has been removed
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 10px 10px 10px;">
                            You have been removed as a Reviewer Manager for the ${publisherName} team on the Gateway.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;">
                            You can no longer:<br/>
                            <ul>
                                <li style="line-height:20px;height:auto;">Review sections of a data access request that have been assigned to you by a Data Access Manager for the ${publisherName} team.</li>
                                <li style="line-height:20px;height:auto;">Process applications and communicate with applicants through the Gateway.</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px;line-height:1.5em;">
                            For more information, please contact a Team Admin for your team:<br/>
                            ${teamAdmin}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px;text-align:center">
                            <a href='${urlDatasetsTeam}' class='button'>View Datasets</a>
                        </td>
                    </tr>
                `;
            }
            break;
        default:
            break;
    }

    let currentYear = new Date().getFullYear();
    footerBodyEmail = `
            <tr>
                <td align="center" style="padding:40px 5px 5px 5px;">
                    <a href='https://www.healthdatagateway.org/'>www.healthdatagateway.org</a>
                </td>
            </tr>
            <tr>
                <td align="center" style="padding:5px;">
                    ©HDR UK ${currentYear}. All rights reserved.
                </td>
            </tr>
        </table>
        </td>
        </tr>
        </table>
    `;

    bodyEmail = `${topBodyEmail}${middleBodyEmail}${footerBodyEmail}`;

    return bodyEmail;
}

const _generateTeamAdmin = (team) => {
    const { members, users } = team;
    const adminRole = [
        constants.roleMemberTeam.CUST_TEAM_ADMIN,
    ];
    let adminMemberIds = [];
    let adminMemberNames = [];

    members.map(member => {
        if ( member.roles.some(mem => adminRole.includes(mem)) ) {
            return adminMemberIds.push(member.memberid.toString());
        }
    });

    users.map(user => {
        let userId = user._id.toString();
        if (adminMemberIds.includes(userId)) {
            return adminMemberNames.push(`${user.firstname} ${user.lastname}`);
        }
    });

    return _generateTableTeamAdmin(adminMemberNames);
}

const _generateTableTeamAdmin = (teamNames) => {
    if (teamNames.length === 0) {
        return '';
    }

    let top = `<table>`;
    let body = ``;
    let tmp = ``;

    teamNames.map(team => {
        tmp = `
            <tr><td>${team}</td></tr>
        `;
        body = body + tmp;
    });

    let bottom = `</table>`;

    return `${top}${body}${bottom}`;
}

export default {
    subjectEmail,
    bodyEmail,
}