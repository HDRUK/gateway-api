<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;

use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EmailTemplate::truncate();
        // Seeds the default email templates used throughout the system

        // Template Example

        EmailTemplate::create([
            'identifier' => 'example_template',
            'subject' => 'Example Template',
            'body' => '<mjml>
            <mj-body>
              <mj-raw>
                <!-- Company Header -->
              </mj-raw>
              <mj-section background-color="#f0f0f0">
                <mj-column>
                  <mj-text font-style="italic" font-size="20px" color="#626262">Health Data Research UK</mj-text>
                </mj-column>
              </mj-section>
              <mj-raw>
                <!-- Image Header -->
              </mj-raw>
              <mj-section background-url="https://place-hold.it/600x100/000000/ffffff/grey.png" background-size="cover" background-repeat="no-repeat">
                <mj-column width="600px">
                  <mj-text align="center" color="#fff" font-size="40px" font-family="Helvetica Neue">[[HEADER_TEXT]]</mj-text>
                </mj-column>
              </mj-section>
              <mj-raw>
                <!-- Intro text -->
              </mj-raw>
              <mj-section background-color="#fafafa">
                <mj-column width="400px">
                  <mj-text font-style="italic" font-size="20px" font-family="Helvetica Neue" color="#626262">[[SUBHEADING_TEXT]]</mj-text>
                  <mj-text color="#525252">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin rutrum enim eget magna efficitur, eu semper augue semper. Aliquam erat volutpat. Cras id dui lectus. Vestibulum sed finibus lectus, sit amet suscipit nibh. Proin nec commodo purus.
                    Sed eget nulla elit. Nulla aliquet mollis faucibus.</mj-text>
                  <mj-button background-color="#F45E43" href="[[BUTTON_1_URL]]">Button 1 Text</mj-button>
                  <mj-button background-color="#F45E43" href="[[BUTTON_2_URL]]">Button 2 Text</mj-button>
                </mj-column>
              </mj-section>
              <mj-raw>
                <!-- Side image -->
              </mj-raw>
              <mj-section background-color="white">
                <mj-raw>
                  <!-- Left image -->
                </mj-raw>
                <mj-column>
                  <mj-image width="200px" src="https://place-hold.it/200x300/000000/ffffff/grey.png"></mj-image>
                </mj-column>
                <mj-raw>
                  <!-- right paragraph -->
                </mj-raw>
                <mj-column>
                  <mj-text font-style="italic" font-size="20px" font-family="Helvetica Neue" color="#626262">[[SUBHEADING_TEXT]]</mj-text>
                  <mj-text color="#525252">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin rutrum enim eget magna efficitur, eu semper augue semper. Aliquam erat volutpat. Cras id dui lectus. Vestibulum sed finibus lectus.</mj-text>
                </mj-column>
              </mj-section>
            </mj-body>
          </mjml>',
          'buttons' => '
          {
            "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "https://test.com/something1"
                    },
                    {
                        "placeholder": "[[BUTTON_2_URL]]",
                        "actual": "https://test.com/something2"
                    }
                ]
            }
          ',
        ]);

        // custodian.team.admin - assign
        EmailTemplate::create([
          'identifier' => 'custodian.team.admin.assign',
          'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Team Admin',
          'body' => '
        <mjml>
            <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                    }
                </mj-style>
            </mj-head>
            <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>

                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                        Congratulations! You’ve been granted the Team Administrator permissions for [[TEAM_NAME]].
                    </mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column width="100%">
                        <mj-text  line-height="20px">
                            Dear [[USER_FIRSTNAME]],
                        </mj-text>
                        <mj-text line-height="20px">
                            [[ASSIGNER_NAME]] has granted you Team Administrator permissions for [[TEAM_NAME]] on the Gateway. 
                        </mj-text>
                        <mj-text>
                                You can now:
                                <ul>
                                    <li style="line-height:20px;height:auto;">
                                        Add, remove and change the roles of other members of [[TEAM_NAME]].
                                    </li>
                                    <li style="line-height:20px;height:auto;">
                                        Manage team notification preferences.
                                    </li>
                                </ul>
                            </mj-text>
                        <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">Manage team</mj-button>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column>
                        <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#525252" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                    </mj-column>
                </mj-section>

            </mj-body>
        </mjml>',
        'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/team-management"
                    }
                ]
            }
        '
        ]);

        // custodian.team.admin - remove
        EmailTemplate::create([
          'identifier' => 'custodian.team.admin.remove',
          'subject' => 'You have been removed as a Team Admin for the [[TEAM_NAME]] team on the Gateway.',
          'body' => '
        <mjml>
            <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                    }
                </mj-style>
            </mj-head>
            <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>

                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                            Your Team Administrator permissions for [[TEAM_NAME]] have been removed.
                         </mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column width="100%">
                        <mj-text  line-height="20px">
                            Dear [[USER_FIRSTNAME]],
                        </mj-text>
                        <mj-text>
                            You have been removed as Team Administrator for [[TEAM_NAME]] on the Gateway.
                        </mj-text>
                        <mj-text>
                            You can no longer:
                            <ul>
                                <li style="line-height:20px;height:auto;">
                                    Add, remove and change the roles of other members of [[TEAM_NAME]]
                                </li>
                                <li style="line-height:20px;height:auto;">
                                    Manage team notification preferences.
                                </li>
                            </ul>
                        </mj-text>
                        <mj-text line-height="20px">
                            For more information, please contact a Team Admin for your team:<br>
                            [[LIST_TEAM_ADMINS]]
                        </mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column>
                        <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#525252" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                    </mj-column>
                </mj-section>

            </mj-body>
        </mjml>'
        ]);

        // custodian.dar.manager - assign
        EmailTemplate::create([
          'identifier' => 'custodian.dar.manager.assign',
          'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Data Access Manager',
          'body' => '
        <mjml>
            <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                    }
                </mj-style>
            </mj-head>
            <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>

                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                            Congratulations! You’ve been granted the Data Access Request Manager permissions for [[TEAM_NAME]].
                        </mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column width="100%">
                        <mj-text line-height="20px">
                            Dear [[USER_FIRSTNAME]],
                        </mj-text>
                        <mj-text>
                            [[ASSIGNER_NAME]] has granted you Data Access Request (DAR) Manager permissions for [[TEAM_NAME]] on the Gateway. 
                        </mj-text>
                        <mj-text>
                            You can now:
                            <ul>
                                <li style="line-height:20px;height:auto;">
                                    Manage enquiries and data access requests through the Gateway.
                                </li>
                                <li style="line-height:20px;height:auto;">
                                    You can create and assign workflows, process applications, and communicate with applicants through the Gateway.
                                </li>
                                <li style="line-height:20px;height:auto;">
                                    You can add and remove Data Access Request Managers and Data Access Request Reviewer permissions to other existing team members.
                                </li>
                            </ul>
                        </mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column>
                        <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#525252" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                    </mj-column>
                </mj-section>

            </mj-body>
        </mjml>',
        ]);

        // custodian.dar.manager - remove
        EmailTemplate::create([
          'identifier' => 'custodian.dar.manager.remove',
          'subject' => 'You have been removed as a Data Access Manager for the [[TEAM_NAME]] team on the Gateway.',
          'body' => '
            <mjml>
                <mj-head>
                    <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                    <mj-style inline="inline">
                        .main-button {
                            padding:10px;
                            width:auto;
                            -webkit-border-radius:5px;
                            -moz-border-radius:5px;
                            border-radius:5px;
                            color:#FFFFFF;
                        }
                    </mj-style>
                </mj-head>
                <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                    <mj-section background-color="#ffffff">
                        <mj-column>
                            <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                        </mj-column>
                    </mj-section>

                    <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                        <mj-column width="100%">
                            <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                                Your Data Access Request Manager permissions for [[TEAM_NAME]] have been removed.
                            </mj-text>
                        </mj-column>
                    </mj-section>

                    <mj-section>
                        <mj-column width="100%">
                            <mj-text line-height="20px">
                                Dear [[USER_FIRSTNAME]],
                            </mj-text>
                            <mj-text>
                                You have been removed as Data Access Request (DAR) Manager for [[TEAM_NAME]] on the Gateway.
                            </mj-text>
                            <mj-text>
                                You can no longer:
                                <ul>
                                    <li style="line-height:20px;height:auto;">
                                        Manage enquiries and data access requests through the Gateway.
                                    </li>
                                    <li style="line-height:20px;height:auto;">
                                        Create and assign workflows, process applications, and communicate with applicants through the Gateway.
                                    </li>
                                    <li style="line-height:20px;height:auto;">
                                       Add and remove Data Access Request Managers and Data Access Request Reviewer permissions to other existing team members.
                                    </li>
                                </ul>
                            </mj-text>
                            <mj-text line-height="20px">
                                For more information, please contact a Team Admin for your team:<br>
                                [[LIST_TEAM_ADMINS]]
                            </mj-text>
                        </mj-column>
                    </mj-section>

                    <mj-section>
                        <mj-column>
                            <mj-text align="center">
                                <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                            </mj-text>
                            <mj-text color="#525252" align="center">
                                @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                            </mj-text>
                        </mj-column>
                    </mj-section>
                </mj-body>
            </mjml>',
          ]);

        // dar.reviewer - assign
        EmailTemplate::create([
            'identifier' => 'dar.reviewer.assign',
            'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Reviewer',
            'body' => '
                <mjml>
                <mj-head>
                    <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                    <mj-style inline="inline">
                        .main-button {
                            padding:10px;
                            width:auto;
                            -webkit-border-radius:5px;
                            -moz-border-radius:5px;
                            border-radius:5px;
                            color:#FFFFFF;
                        }
                    </mj-style>
                </mj-head>
                <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                    <mj-section background-color="#ffffff">
                        <mj-column>
                            <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                        </mj-column>
                    </mj-section>

                    <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                        <mj-column width="100%">
                            <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                                Congratulations! You’ve been granted the Data Access Request Reviewer permissions for [[TEAM_NAME]].
                        </mj-text>
                            
                        </mj-column>
                    </mj-section>

                    <mj-section>
                        <mj-column width="100%">
                            <mj-text line-height="20px">
                                Dear [[USER_FIRSTNAME]],
                            </mj-text>
                            <mj-text>
                            [[ASSIGNER_NAME]] has granted you Data Access Request (DAR) Reviewer permissions for [[TEAM_NAME]] on the Gateway. 

                            </mj-text>
                            <mj-text>
                                You can now:
                                <ul>
                                    <li style="line-height:20px;height:auto;">
                                Review sections of a data access request assigned to you for [[TEAM_NAME]]
                                </li>
                                </ul>
                            </mj-text>
                        </mj-column>
                    </mj-section>

                    <mj-section>
                        <mj-column>
                            <mj-text align="center">
                                <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                            </mj-text>
                            <mj-text color="#525252" align="center">
                                @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                            </mj-text>
                        </mj-column>
                    </mj-section>

                </mj-body>
            </mjml>',
      ]);

        // dar.reviewer - remove
        EmailTemplate::create([
          'identifier' => 'dar.reviewer.remove',
          'subject' => 'You have been removed as a Reviewer for the [[TEAM_NAME]] team on the Gateway.',
          'body' => '
        <mjml>
            <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                    }
                </mj-style>
            </mj-head>
            <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>

                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                            Your Data Access Request Reviewer permissions for [[TEAM_NAME]] have been removed.
                        </mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column width="100%">
                        <mj-text line-height="20px">
                            Dear [[USER_FIRSTNAME]],
                        </mj-text>
                        <mj-text>
                            You have been removed as Data Access Request (DAR) Reviewer Manager for [[TEAM_NAME]] on the Gateway.
                        </mj-text>
                        <mj-text>
                            You can no longer:
                            <ul>
                                <li style="line-height:20px;height:auto;">
                                    Review sections of a data access request assigned to you for [[TEAM_NAME]].
                                </li>
                            </ul>
                        </mj-text>
                        <mj-text line-height="20px">
                            For more information, please contact a Team Admin for your team:<br>
                            [[LIST_TEAM_ADMINS]]
                        </mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column>
                        <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#525252" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                    </mj-column>
                </mj-section>

            </mj-body>
        </mjml>',
        ]);

        // developer - assign
        EmailTemplate::create([
          'identifier' => 'developer.assign',
          'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Developer',
          'body' => '
        <mjml>
            <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                    }
                </mj-style>
            </mj-head>
            <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>

                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                            Congratulations! You’ve been granted the Developer permissions for [[TEAM_NAME]].
                        </mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column width="100%">
                        <mj-text line-height="20px">
                            Dear [[USER_FIRSTNAME]],
                        </mj-text>
                        <mj-text line-height="20px">
                            [[ASSIGNER_NAME]] has granted you Developer permissions for [[TEAM_NAME]] on the Gateway.  
                        </mj-text>
                        <mj-text>
                        You can now:
                        <ul>
                            <li style="line-height:20px;height:auto;">
                                Perform technical functions such as creating and managing api-keys and automated integrations.
                            </li>
                        </ul>
                    </mj-text>
                    <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">Manage Apps</mj-button>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column>
                        <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#525252" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                    </mj-column>
                </mj-section>

            </mj-body>
        </mjml>
          ',
          'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }
          '
        ]);

        // developer - remove
        EmailTemplate::create([
          'identifier' => 'developer.remove',
          'subject' => 'You have been removed as a Developer for the [[TEAM_NAME]] team on the Gateway.',
          'body' => '
        <mjml>
            <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                    }
                </mj-style>
            </mj-head>
            <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>

                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Your Developer permissions for [[TEAM_NAME]] have been removed.</mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column width="100%">
                        <mj-text line-height="20px">
                            Dear [[USER_FIRSTNAME]],
                        </mj-text>
                        <mj-text line-height="20px">
                            You have been removed as Developer for [[TEAM_NAME]] on the Gateway.
                        </mj-text>
                        <mj-text line-height="20px">
                            You can no longer:
                            <ul>
                                <li style="line-height:20px;height:auto;">
                                    Perform technical functions such as creating and managing api-keys and automated integrations.
                                </li>
                            </ul>
                        </mj-text>
                        <mj-text line-height="20px">
                            For more information, please contact a Team Admin for your team:<br>
                            [[LIST_TEAM_ADMINS]]
                        </mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column>
                        <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#525252" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                    </mj-column>
                </mj-section>

            </mj-body>
        </mjml>',
        ]);

        // metadata.editor - assign
        EmailTemplate::create([
          'identifier' => 'metadata.editor.assign',
          'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Metadata Editor',
          'body' => '
        <mjml>
            <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                    }
                </mj-style>
            </mj-head>
            <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>

                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                            Congratulations! You’ve been granted the Metadata Editor permissions for [[TEAM_NAME]].
                        </mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column width="100%">
                        <mj-text line-height="20px">
                            Dear [[USER_FIRSTNAME]],
                        </mj-text>
                        <mj-text>
                            [[ASSIGNER_NAME]] has granted you Metadata Editor permissions for [[TEAM_NAME]] on the Gateway. 
                        </mj-text>
                        <mj-text>
                            You can now:
                            <ul>
                                <li style="line-height:20px;height:auto;">
                                    Manually on-board and manage information about [[TEAM_NAME]] datasets.
                                </li>
                            </ul>
                        </mj-text>
                        <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View datasets</mj-button>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column>
                        <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#525252" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                    </mj-column>
                </mj-section>

            </mj-body>
        </mjml>',
        'buttons' => '
        {
            "replacements": [
                {
                    "placeholder": "[[BUTTON_1_URL]]",
                    "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/datasets"
                }
            ]
        }
        '
        ]);

        // metadata.editor - remove
        EmailTemplate::create([
          'identifier' => 'metadata.editor.remove',
          'subject' => 'You have been removed as a Metadata Editor for the [[TEAM_NAME]] team on the Gateway.',
          'body' => '
        <mjml>
            <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                    }
                </mj-style>
            </mj-head>
            <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="225px" />
                    </mj-column>
                </mj-section>

                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                            Your Metadata Editor permissions for [[TEAM_NAME]] have been removed.
                        </mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column width="100%">
                        <mj-text line-height="20px">
                        Dear [[USER_FIRSTNAME]],
                    </mj-text>
                    <mj-text>
                            You have been removed as a Metadata Editor for [[TEAM_NAME]] on the Gateway.
                        </mj-text>
                        <mj-text line-height="20px">
                            You can no longer:
                            <ul>
                                <li style="line-height:20px;height:auto;">
                                    Manually on-board and manage information about [[TEAM_NAME]] datasets.
                                </li>
                            </ul>
                        </mj-text>
                        <mj-text line-height="20px">
                            For more information, please contact a Team Admin for your team:<br>
                            [[LIST_TEAM_ADMINS]]
                        </mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column>
                        <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#525252" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                    </mj-column>
                </mj-section>

            </mj-body>
        </mjml>',
        ]);

        // custodian.metadata.manager - assign
        EmailTemplate::create([
          'identifier' => 'custodian.metadata.manager.assign',
          'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Metadata Manager',
          'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                            .main-button {
                                padding:10px;
                                width:auto;
                                -webkit-border-radius:5px;
                                -moz-border-radius:5px;
                                border-radius:5px;
                                color:#FFFFFF;
                            }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                        <mj-column>
                            <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                        </mj-column>
                    </mj-section>

                    <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                        <mj-column width="100%">
                            <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                                Congratulations! You’ve been granted the Metadata Manager permissions for [[TEAM_NAME]] 
                            </mj-text>
                        </mj-column>
                    </mj-section>
                    <mj-section>
                        <mj-column width="100%">
                            <mj-text line-height="20px">
                                Dear [[USER_FIRSTNAME]],
                            </mj-text>
                            <mj-text>
                                [[ASSIGNER_NAME]] has granted you Metadata Manager permissions for [[TEAM_NAME]] on the Gateway.
                            </mj-text>
                            <mj-text>
                                You can now:
                                <ul>
                                    <li style="line-height:20px;height:auto;">
                                        Manually on-board and manage information about [[TEAM_NAME]] datasets. 
                                    </li>
                                    <li style="line-height:20px;height:auto;">
                                        Add and remove other team members with metadata editor permissions.
                                    </li>
                                </ul>
                            </mj-text>
                            <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View datasets</mj-button>
                        </mj-column>
                    </mj-section>

                    <mj-section>
                        <mj-column>
                            <mj-text align="center">
                                <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                            </mj-text>
                            <mj-text color="#525252" align="center">
                                @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                            </mj-text>
                        </mj-column>
                    </mj-section>

                    </mj-body>
                </mjml>',
                'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/datasets"
                        }
                    ]
                }
            '
          ]);

        // custodian.metadata.manager - remove
        EmailTemplate::create([
            'identifier' => 'custodian.metadata.manager.remove',
            'subject' => 'You have been removed as a Metadata Manager for the [[TEAM_NAME]] team on the Gateway',
            'body' => '
            <mjml>
                <mj-head>
                    <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                    <mj-style inline="inline">
                        .main-button {
                            padding:10px;
                            width:auto;
                            -webkit-border-radius:5px;
                            -moz-border-radius:5px;
                            border-radius:5px;
                            color:#FFFFFF;
                        }
                    </mj-style>
                </mj-head>
                <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                    <mj-section background-color="#ffffff">
                        <mj-column>
                            <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="225px" />
                        </mj-column>
                    </mj-section>

                    <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                        <mj-column width="100%">
                            <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                                Your Metadata Manager permissions for [[TEAM_NAME]] have been removed.
                            </mj-text>
                        </mj-column>
                    </mj-section>

                    <mj-section>
                        <mj-column width="100%">
                            <mj-text line-height="20px">
                                Dear [[USER_FIRSTNAME]],
                            </mj-text>
                            <mj-text>
                                You have been removed as a Metadata Manager for [[TEAM_NAME]] on the Gateway.
                            </mj-text>
                            <mj-text>
                                You can no longer:
                                <ul>
                                    <li style="line-height:20px;height:auto;">
                                        Manually onboard and manage information about [[TEAM_NAME]] datasets.
                                    </li>
                                    <li style="line-height:20px;height:auto;">
                                        Add and remove other team members with editor permissions.
                                    </li>
                                </ul>
                            </mj-text>
                            <mj-text line-height="20px">
                                For more information, please contact a Team Admin for your team:<br>
                                [[LIST_TEAM_ADMINS]]
                            </mj-text>
                        </mj-column>
                    </mj-section>

                    <mj-section>
                        <mj-column>
                            <mj-text align="center">
                                <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                            </mj-text>
                            <mj-text color="#525252" align="center">
                                @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                            </mj-text>
                        </mj-column>
                    </mj-section>

                </mj-body>
            </mjml>
            ',
        ]);

        // hdruk.superadmin - assign
        EmailTemplate::create([
            'identifier' => 'hdruk.superadmin.assign',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.superadmin.assign',
            'body' => '
                    <mjml>
                        <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                            <mj-section background-color="#ffffff">
                                <mj-column>
                                    <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                                </mj-column>
                            </mj-section>

                            <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                                <mj-column width="100%">
                                    <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Mhdruk.superadmin.assign role has been assigned</mj-text>
                                </mj-column>
                            </mj-section>

                            <mj-section>
                                <mj-column>
                                    <mj-text align="center">
                                        <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                    </mj-text>
                                    <mj-text color="#525252" align="center">
                                        @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                    </mj-text>
                                </mj-column>
                            </mj-section>

                        </mj-body>
                    </mjml>
          ',
        ]);

        // hdruk.superadmin - remove
        EmailTemplate::create([
            'identifier' => 'hdruk.superadmin.remove',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.superadmin.remove',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.superadmin.assign role has been removed</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // hdruk.admin - assign
        EmailTemplate::create([
            'identifier' => 'hdruk.admin.assign',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.admin.assign',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.admin.assign role has been assigned</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // hdruk.admin - remove
        EmailTemplate::create([
            'identifier' => 'hdruk.admin.remove',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.admin.remove',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.admin.assign role has been removed</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // hdruk.metadata - assign
        EmailTemplate::create([
            'identifier' => 'hdruk.metadata.assign',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.metadata.assign',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.metadata.assign role has been assigned</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // hdruk.metadata - remove
        EmailTemplate::create([
            'identifier' => 'hdruk.metadata.remove',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.metadata.remove',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.metadata.assign role has been removed</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // hdruk.dar - assign
        EmailTemplate::create([
            'identifier' => 'hdruk.dar.assign',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.dar.assign',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.dar.assign role has been assigned</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // hdruk.dar - remove
        EmailTemplate::create([
            'identifier' => 'hdruk.dar.remove',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.dar.remove',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.dar.assign role has been removed</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // hdruk.custodian - assign
        EmailTemplate::create([
            'identifier' => 'hdruk.custodian.assign',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.custodian.assign',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.custodian.assign role has been assigned</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // hdruk.custodian - remove
        EmailTemplate::create([
            'identifier' => 'hdruk.custodian.remove',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.custodian.remove',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.custodian.assign role has been removed</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // Cohort Discovery access has expired
        EmailTemplate::create([
            'identifier' => 'cohort.discovery.access.expired',
            'subject' => 'Your Cohort Discovery access has expired',
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                            .main-button {
                                padding:10px;
                                width:auto;
                                -webkit-border-radius:5px;
                                -moz-border-radius:5px;
                                border-radius:5px;
                                color:#FFFFFF;
                            }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                                Your Cohort Discovery access has expired.
                            </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text  line-height="20px">
                                    Dear [[USER_FIRSTNAME]],
                                </mj-text>
                                <mj-text line-height="30px">
                                    Your Cohort Discovery account has expired. Use the button below to renew your access. 
                                </mj-text>
                                <mj-text>
                                    <mj-text>
                                        Regards,<br/>
                                        Gateway Cohort Discovery Admin.
                                    </mj-text>
                                </mj-text>
                                <mj-button css-class="main-section" background-color="#00ACCA" href="[[COHORT_DISCOVERY_RENEW_URL]]">Renew Cohort Discovery access</mj-button>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
          'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[COHORT_DISCOVERY_RENEW_URL]]",
                            "actual": "env(GATEWAY_URL)/en/about/cohort-discovery"
                        }
                    ]
                }
            ',
        ]);

        // Cohort Discovery access will soon expire
        EmailTemplate::create([
            'identifier' => 'cohort.discovery.access.will.expire',
            'subject' => 'Your Cohort Discovery access will soon expire',
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                            .main-button {
                                padding:10px;
                                width:auto;
                                -webkit-border-radius:5px;
                                -moz-border-radius:5px;
                                border-radius:5px;
                                color:#FFFFFF;
                            }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                                Your Cohort Discovery access will soon expire.
                            </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text  line-height="20px">
                                    Dear [[USER_FIRSTNAME]],
                                </mj-text>
                                <mj-text line-height="30px">
                                    Your Cohort Discovery account will expire [[EXPIRE_DATE]]. Use the button below to renew your access.
                                </mj-text>
                                <mj-text>
                                    <mj-text>
                                        Regards,<br/>
                                        Gateway Cohort Discovery Admin.
                                    </mj-text>
                                </mj-text>
                                <mj-button css-class="main-section" background-color="#00ACCA" href="[[COHORT_DISCOVERY_RENEW_URL]]">Renew Cohort Discovery access</mj-button>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
            ',
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[COHORT_DISCOVERY_RENEW_URL]]",
                            "actual": "env(GATEWAY_URL)/en/about/cohort-discovery"
                        }
                    ]
                }
            ',
        ]);

        // Cohort Discovery access approved
        EmailTemplate::create([
            'identifier' => 'cohort.discovery.access.approved',
            'subject' => 'Congratulations! Your Cohort Discovery registration has been approved.',
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                            .main-button {
                                padding:10px;
                                width:auto;
                                -webkit-border-radius:5px;
                                -moz-border-radius:5px;
                                border-radius:5px;
                                color:#FFFFFF;
                            }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                                    Congratulations! Your Cohort Discovery registration has been approved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text  line-height="20px">
                                    Dear [[USER_FIRSTNAME]],
                                </mj-text>
                                <mj-text line-height="30px">
                                    You have been granted access for Cohort Discovery under [[USER_EMAIL]]. Please use buttons below to access Cohort Discovery and watch video on how to use the tool.<br/>Your Cohort Discovery access is valid for a 6-month period after which you will need to re-new your access.<br/>If you require further support raise a support ticket on the HDR UK Innovation Gateway.
                                </mj-text>
                                <mj-text>
                                    <mj-text>
                                        Regards,<br/>
                                        Gateway Cohort Discovery Admin.
                                    </mj-text>
                                </mj-text>
                                <mj-button css-class="main-section" background-color="#00ACCA" href="[[COHORT_DISCOVERY_ACCESS_URL]]">Access Cohort Discovery</mj-button>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
            ',
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[COHORT_DISCOVERY_ACCESS_URL]]",
                            "actual": "env(GATEWAY_URL)/en/about/cohort-discovery"
                        }
                    ]
                }
            ',
        ]);

        // Cohort Discovery access rejected
        EmailTemplate::create([
            'identifier' => 'cohort.discovery.access.rejected',
            'subject' => 'Your Cohort Discovery Registration has been Rejected.',
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                            .main-button {
                                padding:10px;
                                width:auto;
                                -webkit-border-radius:5px;
                                -moz-border-radius:5px;
                                border-radius:5px;
                                color:#FFFFFF;
                            }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                                Your Cohort Discovery Registration has been Rejected.
                            </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text line-height="20px">
                                    Dear [[USER_FIRSTNAME]],
                                </mj-text>
                                <mj-text line-height="30px">
                                    Thank you for your patience whilst we reviewed your request to the Cohort Discovery Tool.<br/>Unfortunately, your registration for access has been rejected at this time. Your request does not meet our access criteria, which includes the following:
                                    <ul>
                                        <li>Registering a user profile on the Gateway</li>
                                        <li>Providing information outlining your role and institution information</li>
                                        <li>Providing justification for using the tool for public benefit</li>
                                    </ul>
                                    If you have any questions on the above decision, raise a support ticket on the HDR UK Innovation Gateway.
                                </mj-text>
                                <mj-text>
                                <mj-text>
                                    Regards,<br/>
                                    Gateway Cohort Discovery Admin.
                                </mj-text>
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
            ',
        ]);

        // Cohort Discovery registration form has been submitted
        EmailTemplate::create([
            'identifier' => 'cohort.discovery.access.submitted',
            'subject' => 'Your Cohort Discovery registration form has been submitted.',
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                            .main-button {
                                padding:10px;
                                width:auto;
                                -webkit-border-radius:5px;
                                -moz-border-radius:5px;
                                border-radius:5px;
                                color:#FFFFFF;
                            }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                                Your Cohort Discovery registration form has been submitted.
                            </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text line-height="20px">
                                    Dear [[USER_FIRSTNAME]],
                                </mj-text>
                                <mj-text line-height="30px">
                                    Your Cohort Discovery registration has been received by Gateway Cohort Discovery admin. This will be reviewed and you will receive a notification via your Gateway email address when a decision is made.<br/>We aim to get back within 5 business days of your original request.
                                </mj-text>
                                <mj-text>
                                <mj-text>
                                    Regards,<br/>
                                    Gateway Cohort Discovery Admin.
                                </mj-text>
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
            ',
        ]);

        // Cohort Discovery access has been banned.
        EmailTemplate::create([
            'identifier' => 'cohort.discovery.access.banned',
            'subject' => 'Your Cohort Discovery access has been banned.',
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                            .main-button {
                                padding:10px;
                                width:auto;
                                -webkit-border-radius:5px;
                                -moz-border-radius:5px;
                                border-radius:5px;
                                color:#FFFFFF;
                            }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                                Your Cohort Discovery access has been banned.
                            </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text line-height="20px">
                                    Dear [[USER_FIRSTNAME]],
                                </mj-text>
                                <mj-text line-height="30px">
                                    This is an automated message to let you know that your access to the Cohort Discovery tool has been permanently removed. If you have any question or would like to discuss this further please raise a support ticket on the HDR UK Innovation Gateway.
                                </mj-text>
                                <mj-text>
                                <mj-text>
                                    Regards,<br/>
                                    Gateway Cohort Discovery Admin.
                                </mj-text>
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
            ',
        ]);

        // Cohort Discovery access has been suspended.
        EmailTemplate::create([
            'identifier' => 'cohort.discovery.access.suspended',
            'subject' => 'Your Cohort Discovery access has been suspended.',
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                            .main-button {
                                padding:10px;
                                width:auto;
                                -webkit-border-radius:5px;
                                -moz-border-radius:5px;
                                border-radius:5px;
                                color:#FFFFFF;
                            }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                                Your Cohort Discovery access has been suspended.
                            </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text line-height="20px">
                                    Dear [[USER_FIRSTNAME]],
                                </mj-text>
                                <mj-text line-height="30px">
                                    This is an automated message to let you know that your access to the Cohort Discovery tool has suspended. If you have any question or would like to discuss this further please raise a support ticket on the HDR UK Innovation Gateway.
                                </mj-text>
                                <mj-text>
                                <mj-text>
                                    Regards,<br/>
                                    Gateway Cohort Discovery Admin.
                                </mj-text>
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
            ',
        ]);

        $apiIntegrationDevCreateBody = '
            <mjml>
                <mj-head>
                    <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                    <mj-style inline="inline">
                    .main-button {
                    padding:10px;
                    width:auto;
                    -webkit-border-radius:5px;
                    -moz-border-radius:5px;
                    border-radius:5px;
                    color:#FFFFFF;
                    }
                    </mj-style>
                </mj-head>
                <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                    <mj-section background-color="#ffffff">
                    <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                    </mj-section>

                    <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Congratulations! A new integration has been created for<br> [[TEAM_NAME]].
                        </mj-text>
                    </mj-column>
                    </mj-section>
                    <mj-section>
                    <mj-column>
                        <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                            Dear [[USER_FIRST_NAME]],<br></br>
                            [[API_NAME]] has been created to enable automated integration with the HDR Innovation Gateway.<br></br>
                            To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                        </mj-text>
                        <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View app integrations</mj-button>
                    </mj-column>
                    </mj-section>
                    <mj-section>
                    <mj-column>
                        <mj-text align="center">
                        <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#3C3C3B" align="center">
                        @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                    </mj-column>
                    </mj-section>
                </mj-body>
            </mjml>
        ';

        // APIintegration - create - developer
        EmailTemplate::create([
            'identifier' => 'apiintegration.developer.create',
            'subject' => '[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.',
            'body' => $apiIntegrationDevCreateBody,
            'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/api-management"
                    }
                ]
            }
            '
        ]);

        // APIintegration - create - team.admin
        EmailTemplate::create([
            'identifier' => 'apiintegration.team.admin.create',
            'subject' => '[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.',
            'body' => $apiIntegrationDevCreateBody,
            'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/api-management"
                    }
                ]
            }
            '
        ]);

        // APIintegration - create - other roles
        EmailTemplate::create([
            'identifier' => 'apiintegration.other.create',
            'subject' => '[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.',
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                        .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                        }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                        <mj-column>
                            <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                        </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                        <mj-column width="100%">
                            <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Congratulations! A new integration has been created for<br> [[TEAM_NAME]].
                            </mj-text>
                        </mj-column>
                        </mj-section>
                        <mj-section>
                        <mj-column>
                            <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                                Dear [[USER_FIRST_NAME]],<br></br>
                                [[API_NAME]] has been created to enable automated integration with the HDR Innovation Gateway.<br></br>
                                To review or edit the integration, contact your Team Administrator(s) or Developer(s):<br></br>
                                [[LIST_TEAM_ADMINS_AND_DEVELOPERS]]
                            </mj-text>
                        </mj-column>
                        </mj-section>
                        <mj-section>
                        <mj-column>
                            <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                            </mj-text>
                            <mj-text color="#3C3C3B" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                            </mj-text>
                        </mj-column>
                        </mj-section>
                    </mj-body>
                </mjml>
            ',
        ]);

        $gmiIntegrationCreateSubject = '[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.';

        $gmiIntegrationDevCreateBody = '
            <mjml>
                <mj-head>
                    <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                    <mj-style inline="inline">
                    .main-button {
                    padding:10px;
                    width:auto;
                    -webkit-border-radius:5px;
                    -moz-border-radius:5px;
                    border-radius:5px;
                    color:#FFFFFF;
                    }
                    </mj-style>
                </mj-head>
                <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                    <mj-section background-color="#ffffff">
                    <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                    </mj-section>

                    <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Congratulations! A new integration has been created for<br> [[TEAM_NAME]].
                        </mj-text>
                    </mj-column>
                    </mj-section>
                    <mj-section>
                    <mj-column>
                        <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                            Dear [[USER_FIRST_NAME]],<br></br>
                            An integration for [[INTEGRATION_TYPE]] has been created to enable automated integration with the HDR Innovation Gateway.<br></br>
                            To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                        </mj-text>
                        <mj-button css-class="main-section" background-color="#00ACCA" href="#">View federated integrations</mj-button>
                    </mj-column>
                    </mj-section>
                    <mj-section>
                    <mj-column>
                        <mj-text align="center">
                        <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#3C3C3B" align="center">
                        @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                    </mj-column>
                    </mj-section>
                </mj-body>
            </mjml>
        ';

        // GMIintegration - create - developer
        EmailTemplate::create([
            'identifier' => 'gmiintegration.developer.create',
            'subject' => $gmiIntegrationCreateSubject,
            'body' => $gmiIntegrationDevCreateBody,
        ]);

        // GMIintegration - create - team.admin
        EmailTemplate::create([
            'identifier' => 'gmiintegration.team.admin.create',
            'subject' => $gmiIntegrationCreateSubject,
            'body' => $gmiIntegrationDevCreateBody,
        ]);

        // GMIintegration - create - other roles
        EmailTemplate::create([
            'identifier' => 'gmiintegration.other.create',
            'subject' => $gmiIntegrationCreateSubject,
            'body' => '
                <mjml>
                <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                    padding:10px;
                    width:auto;
                    -webkit-border-radius:5px;
                    -moz-border-radius:5px;
                    border-radius:5px;
                    color:#FFFFFF;
                    }
                </mj-style>
                </mj-head>
                <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                    <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>
            
                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                    <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Congratulations! A new integration has been created for<br> [[TEAM_NAME]].
                    </mj-text>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                        Dear [[USER_FIRST_NAME]],<br></br>
                        An integration for [[INTEGRATION_TYPE]] has been created to enable automated integration with the HDR Innovation Gateway.<br></br>
                        To review or edit the integration, contact your Team Administrator(s) or Developer(s):<br></br>
                        [[LIST_TEAM_ADMINS_AND_DEVELOPERS]]
                    </mj-text>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="center">
                        <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                    </mj-text>
                    <mj-text color="#3C3C3B" align="center">
                        @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                    </mj-text>
                    </mj-column>
                </mj-section>
                </mj-body>
            </mjml>
            ',
        ]);

        $integrationDisabledSubject = 'An integration has been disabled for the [[TEAM_NAME]] team on the Gateway.';

        $integrationDevDisabledBody = '
            <mjml>
                <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                    padding:10px;
                    width:auto;
                    -webkit-border-radius:5px;
                    -moz-border-radius:5px;
                    border-radius:5px;
                    color:#FFFFFF;
                    }
                </mj-style>
                </mj-head>
                <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                    <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>
            
                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                    <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">An integration was disabled on the Gateway for<br> [[TEAM_NAME]].
                    </mj-text>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                        Dear [[USER_FIRST_NAME]],<br></br>
                        This is an automated notification that [[DISABLER]] disabled an integration on the HDR Innovation Gateway.<br></br>
                            To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                    </mj-text>
                    <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View integrations</mj-button>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="center">
                        <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                    </mj-text>
                    <mj-text color="#3C3C3B" align="center">
                        @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                    </mj-text>
                    </mj-column>
                </mj-section>
                </mj-body>
            </mjml>
        ';

        // Integrations - disabled - developer
        EmailTemplate::create([
            'identifier' => 'gmiintegration.developer.disable',
            'subject' => $integrationDisabledSubject,
            'body' => $integrationDevDisabledBody,
            'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }
            '
        ]);

        // Integrations - disabled - team.admin
        EmailTemplate::create([
            'identifier' => 'gmiintegration.team.admin.disable',
            'subject' => $integrationDisabledSubject,
            'body' => $integrationDevDisabledBody,
            'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }
            '
        ]);

        // Integrations - disabled - other roles
        EmailTemplate::create([
            'identifier' => 'gmiintegration.other.disable',
            'subject' => $integrationDisabledSubject,
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                        .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                        }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                        <mj-column>
                            <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                        </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                        <mj-column width="100%">
                            <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">An integration was disabled on the Gateway for<br> [[TEAM_NAME]].
                            </mj-text>
                        </mj-column>
                        </mj-section>
                        <mj-section>
                        <mj-column>
                            <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                                Dear [[USER_FIRST_NAME]],<br></br>
                                This is an automated notification that [[DISABLER]] disabled an integration on the HDR Innovation Gateway.<br></br>
                                To review or edit the integration, contact your Team Administrator(s) or Developer(s):
                    [[LIST_TEAM_ADMINS_AND_DEVELOPERS]]
                            </mj-text>
                        </mj-column>
                        </mj-section>
                        <mj-section>
                        <mj-column>
                            <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                            </mj-text>
                            <mj-text color="#3C3C3B" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                            </mj-text>
                        </mj-column>
                        </mj-section>
                    </mj-body>
                </mjml>
            ',
        ]);

        $integrationDeletedSubject = 'An integration has been deleted for the [[TEAM_NAME]] team on the Gateway.';

        $integrationDevDeletedBody = '
            <mjml>
                <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                    padding:10px;
                    width:auto;
                    -webkit-border-radius:5px;
                    -moz-border-radius:5px;
                    border-radius:5px;
                    color:#FFFFFF;
                    }
                </mj-style>
                </mj-head>
                <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                    <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>
            
                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                    <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">An integration was deleted on the Gateway for<br> [[TEAM_NAME]].
                    </mj-text>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                        Dear [[USER_FIRST_NAME]],<br></br>
                        This is an automated notification that [[DISABLER]] deleted an integration on the HDR Innovation Gateway. This is not reversible.<br></br>
                        To review or edit integrations, click the link below or visit your account on the Gateway.<br></br>
                    </mj-text>
                    <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View integrations</mj-button>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="center">
                        <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                    </mj-text>
                    <mj-text color="#3C3C3B" align="center">
                        @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                    </mj-text>
                    </mj-column>
                </mj-section>
                </mj-body>
            </mjml>
        ';

        // Integrations - deleted - developer
        EmailTemplate::create([
            'identifier' => 'integration.developer.delete',
            'subject' => $integrationDeletedSubject,
            'body' => $integrationDevDeletedBody,
            'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }
            '
        ]);

        // Integrations - deleted - team.admin
        EmailTemplate::create([
            'identifier' => 'integration.team.admin.delete',
            'subject' => $integrationDeletedSubject,
            'body' => $integrationDevDeletedBody,
            'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }
            '
        ]);

        // Integrations - deleted - other roles
        EmailTemplate::create([
            'identifier' => 'integration.other.delete',
            'subject' => $integrationDeletedSubject,
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                        .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                        }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                        <mj-column>
                            <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                        </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                        <mj-column width="100%">
                            <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">An integration was deleted on the Gateway for<br> [[TEAM_NAME]].
                            </mj-text>
                        </mj-column>
                        </mj-section>
                        <mj-section>
                        <mj-column>
                            <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                                Dear [[USER_FIRST_NAME]],<br></br>
                                This is an automated notification that [[DISABLER]] deleted an integration on the HDR Innovation Gateway. This is not reversible.<br></br>
                                To review or edit integrations, contact your Team Administrator(s) or Developer(s):
                    [[LIST_TEAM_ADMINS_AND_DEVELOPERS]]
                            </mj-text>
                        </mj-column>
                        </mj-section>
                        <mj-section>
                        <mj-column>
                            <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                            </mj-text>
                            <mj-text color="#3C3C3B" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                            </mj-text>
                        </mj-column>
                        </mj-section>
                    </mj-body>
                </mjml>
            ',
        ]);

        $syncErrorSubject = 'An automation error occurred for the [[TEAM_NAME]] team on the Gateway.';

        $syncErrorDevBody = '
            <mjml>
                <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                    padding:10px;
                    width:auto;
                    -webkit-border-radius:5px;
                    -moz-border-radius:5px;
                    border-radius:5px;
                    color:#FFFFFF;
                    }
                </mj-style>
                </mj-head>
                <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                    <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>
            
                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                    <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">An integration was disabled on the Gateway for<br> [[TEAM_NAME]].
                    </mj-text>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                        Dear [[USER_FIRST_NAME]],<br></br>
                        This is an automated notification that on [[DATE_OF_ERROR]]  there was an error during the scheduled cloud run for the [[INTEGRATION_TYPE]] integration. Summary of the synchronisations is below.<br></br>
                        To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                    </mj-text>
                    <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View integrations</mj-button>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="center">
                        <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                    </mj-text>
                    <mj-text color="#3C3C3B" align="center">
                        @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                    </mj-text>
                    </mj-column>
                </mj-section>
                </mj-body>
            </mjml>
        ';

        // Sync Error - developer
        EmailTemplate::create([
            'identifier' => 'integration.developer.error',
            'subject' => $syncErrorSubject,
            'body' => $syncErrorDevBody,
            'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }
            '
        ]);

        // Sync Error - team.admin
        EmailTemplate::create([
            'identifier' => 'integration.team.admin.error',
            'subject' => $syncErrorSubject,
            'body' => $syncErrorDevBody,
            'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }
            '
        ]);

        // Sync Error - other roles
        EmailTemplate::create([
            'identifier' => 'integration.other.error',
            'subject' => $syncErrorSubject,
            'body' => '
                <mjml>
                    <mj-head>
                    <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                    <mj-style inline="inline">
                        .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                        }
                    </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                    <mj-section background-color="#ffffff">
                        <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                        </mj-column>
                    </mj-section>
                
                    <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                        <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">An automation error occurred for an integration on the Gateway for<br> [[TEAM_NAME]].
                        </mj-text>
                        </mj-column>
                    </mj-section>
                    <mj-section>
                        <mj-column>
                        <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                            Dear [[USER_FIRST_NAME]],<br></br>
                            This is an automated notification that on [[DATE_OF_ERROR]]  there was an error during the scheduled cloud run for the [[INTEGRATION_TYPE]] integration. Summary of the synchronisations is below.<br></br>
                            To review or edit the integration, contact your Team Administrator(s) or Developer(s):
                            [[LIST_TEAM_ADMINS_AND_DEVELOPERS]]
                        </mj-text>
                        </mj-column>
                    </mj-section>
                    <mj-section>
                        <mj-column>
                        <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                            Errors:<br></br>
                            [[LIST_OF_ERRORS]]<br></br>
                            Success:<br></br>
                            [[LIST_OF_SUCCESS]]<br></br>
                        </mj-text>
                        </mj-column>
                    </mj-section>
                    <mj-section>
                        <mj-column>
                        <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#3C3C3B" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                        </mj-column>
                    </mj-section>
                    </mj-body>
                </mjml>
            ',
        ]);

        EmailTemplate::create([
            'identifier' => 'feasibilityenquiry.firstmessage',
            'subject' => 'Feasibility Enquiry from the Health Data Research Gateway: [[USER_FIRST_NAME]] [[USER_LAST_NAME]], [[PROJECT_TITLE]]',
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-html-attributes>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="text-color" text-color="#000000"></mj-html-attribute>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="font-family" font-family="-apple-system, BlinkMacSystemFont,
                                \'Segoe UI\', \'Roboto\', \'Oxygen\', \'Ubuntu\', \'Cantarell\', \'Fira Sans\', \'Droid Sans\',\'Helvetica Neue\', sans-serif">
                            </mj-html-attribute>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="font-size" font-size="14px"></mj-html-attribute>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="line-height" line-height="1.7"></mj-html-attribute>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="font-weight" font-weight="400"></mj-html-attribute>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="responsive" responsive="true"></mj-html-attribute>
                        </mj-html-attributes>
                        <mj-style >.main-button {
                            padding:10px;
                            width:auto;
                            -webkit-border-radius:5px;
                            -moz-border-radius:5px;
                            border-radius:5px;
                            color:#FFFFFF;
                            }
                        </mj-style>
                        <mj-breakpoint width="480px" />
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-attributes>
                            <mj-all font-family="-apple-system, BlinkMacSystemFont, 
                                \'Segoe UI\', \'Roboto\', \'Oxygen\', \'Ubuntu\', \'Cantarell\', \'Fira Sans\', \'Droid Sans\',\'Helvetica Neue\', sans-serif" />
                            <mj-text font-size="14px" />
                            <mj-text color="#000000" />
                            <mj-text line-height="1.7" />
                            <mj-text font-weight="400" />
                        </mj-attributes>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" width="600px" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B" >
                        <mj-section padding="20px 0px 20px 0px" border="none" direction="ltr" text-align="center" background-repeat="repeat" background-size="auto" background-position="top center" background-color="#ffffff" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-image align="center" height="auto" src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" width="226px" padding="10px 0px 10px 0px" >
                                </mj-image>
                            </mj-column>
                        </mj-section>
                        <mj-section background-repeat="no-repeat" background-size="cover" background-position="top center" border="none" direction="ltr"
                            text-align="center" background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" width="100%" padding="0px 0px 0px 0px" >
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0px 30px 0px" >Feasibility enquiry received.</mj-text>
                            </mj-column>
                        </mj-section>
                        <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" >Dear [[TEAM_NAME]],<br><br>
                                    You have received a feasibility enquiry from [[USER_FIRST_NAME]], details of which can be found in the thread below. You can respond by using the reply button within your email client.<br><br>
                                    Submitted information
                                    <div>[[MESSAGE_BODY]]</div>
                                </mj-text>
                            </mj-column>
                        </mj-section>
                        <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-text align="center" padding="10px 25px 10px 25px" >
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text align="center" color="#3C3C3B" padding="10px 25px 10px 25px" >@HDR UK [[CURRENT_YEAR]]. All rights reserved.</mj-text>
                            </mj-column>
                        </mj-section>
                    </mj-body>
                </mjml > 
            '
        ]);

        EmailTemplate::create([
            'identifier' => 'dar.notifymessage',
            'subject' => 'Reply notification message',
            'body' => '
                <mjml>
                    <mj-body>
                        <mj-section>
                            <mj-column>
                                <mj-text>
                                    [[DAR_NOTIFY_MESSAGE]]
                                </mj-text>
                            </mj-column>
                        </mj-section>
                    </mj-body>
                </mjml>
            '
        ]);

        EmailTemplate::create([
            'identifier' => 'generalenquiry.firstmessage',
            'subject' => 'General Enquiry from the Health Data Research Gateway: [[USER_FIRST_NAME]] [[USER_LAST_NAME]], [[USER_ORGANISATION]]',
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-html-attributes>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="text-color" text-color="#000000"></mj-html-attribute>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="font-family" font-family="-apple-system, BlinkMacSystemFont,
                                \'Segoe UI\', \'Roboto\', \'Oxygen\', \'Ubuntu\', \'Cantarell\', \'Fira Sans\', \'Droid Sans\',\'Helvetica Neue\', sans-serif">
                            </mj-html-attribute>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="font-size" font-size="14px"></mj-html-attribute>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="line-height" line-height="1.7"></mj-html-attribute>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="font-weight" font-weight="400"></mj-html-attribute>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="responsive" responsive="true"></mj-html-attribute>
                        </mj-html-attributes>
                        <mj-style >.main-button {
                            padding:10px;
                            width:auto;
                            -webkit-border-radius:5px;
                            -moz-border-radius:5px;
                            border-radius:5px;
                            color:#FFFFFF;
                            }
                        </mj-style>
                        <mj-breakpoint width="480px" />
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-attributes>
                            <mj-all font-family="-apple-system, BlinkMacSystemFont, 
                                \'Segoe UI\', \'Roboto\', \'Oxygen\', \'Ubuntu\', \'Cantarell\', \'Fira Sans\', \'Droid Sans\',\'Helvetica Neue\', sans-serif" />
                            <mj-text font-size="14px" />
                            <mj-text color="#000000" />
                            <mj-text line-height="1.7" />
                            <mj-text font-weight="400" />
                        </mj-attributes>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" width="600px" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B" >
                        <mj-section padding="20px 0px 20px 0px" border="none" direction="ltr" text-align="center" background-repeat="repeat" background-size="auto" background-position="top center" background-color="#ffffff" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-image align="center" height="auto" src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" width="226px" padding="10px 0px 10px 0px" >
                                </mj-image>
                            </mj-column>
                        </mj-section>
                        <mj-section background-repeat="no-repeat" background-size="cover" background-position="top center" border="none" direction="ltr"
                            text-align="center" background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" width="100%" padding="0px 0px 0px 0px" >
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0px 30px 0px" >General Enquiry received.</mj-text>
                            </mj-column>
                        </mj-section>
                        <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" >Dear [[TEAM_NAME]],<br><br>
                                    You have received a general enquiry from [[USER_FIRST_NAME]], details of which can be found in the thread below. You can respond by using the reply button within your email client.<br><br>
                                    Submitted information
                                    <div>[[MESSAGE_BODY]]</div>
                                </mj-text>
                            </mj-column>
                        </mj-section>
                        <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-text align="center" padding="10px 25px 10px 25px" >
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text align="center" color="#3C3C3B" padding="10px 25px 10px 25px" >@HDR UK [[CURRENT_YEAR]]. All rights reserved.</mj-text>
                            </mj-column>
                        </mj-section>
                    </mj-body>
                </mjml > 
            '
        ]);

        // private.app.create
        EmailTemplate::updateOrCreate([
            'identifier' => 'private.app.create',
        ], [
            'identifier' => 'private.app.create',
            'subject' => 'Congratulations! A new Private App has been created.',
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                            .main-button {
                                padding:10px;
                                width:auto;
                                -webkit-border-radius:5px;
                                -moz-border-radius:5px;
                                border-radius:5px;
                                color:#FFFFFF;
                            }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                                    Congratulations! A new Private App has been created for [[TEAM_NAME]] 
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text  line-height="20px">
                                    Dear [[USER_FIRSTNAME]],
                                </mj-text>
                                <mj-text line-height="20px">
                                    <b>[[APP_NAME]]</b> has been created to enable automated integration with the Gateway:
                                </mj-text>
                                <mj-text>
                                    Date: [[APP_CREATED_AT_DATE]]
                                </mj-text>
                                <mj-text>
                                    Status: [[APP_STATUS]]
                                </mj-text>
                            </mj-column>
                        </mj-section>
                        
                        <mj-section>
                            <mj-column width="100%">
                                <mj-text  line-height="20px">
                                    To review or edit the integration, click the link below or visit your account on the Gateway.
                                </mj-text>
                            </mj-column>
                        </mj-section>
                        
                        <mj-section>
                            <mj-column>
                                <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View app integrations</mj-button>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>',
        ]);
        EmailTemplate::where('identifier', 'private.app.create')->update([
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }
            ',
        ]);

        // private.app.update
        EmailTemplate::updateOrCreate([
            'identifier' => 'private.app.update',
        ], [
            'identifier' => 'private.app.update',
            'subject' => 'Private App has been updated.',
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                            .main-button {
                                padding:10px;
                                width:auto;
                                -webkit-border-radius:5px;
                                -moz-border-radius:5px;
                                border-radius:5px;
                                color:#FFFFFF;
                            }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                                    Private App has been updated for [[TEAM_NAME]] 
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text line-height="20px">
                                    Dear [[USER_FIRSTNAME]],
                                </mj-text>
                                <mj-text line-height="20px">
                                    <b>[[APP_NAME]]</b> has been updated on the Gateway:
                                </mj-text>
                                <mj-text>
                                    Date: [[APP_UPDATED_AT_DATE]]
                                </mj-text>
                                <mj-text>
                                    Status: [[APP_STATUS]]
                                </mj-text>
                            </mj-column>
                        </mj-section>
                        
                        <mj-section>
                            <mj-column width="100%">
                                <mj-text line-height="20px">
                                    To review or edit the integration, click the link below or visit your account on the Gateway.
                                </mj-text>
                            </mj-column>
                        </mj-section>
                        
                        <mj-section>
                            <mj-column>
                                <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View app integrations</mj-button>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>',
        ]);
        EmailTemplate::where('identifier', 'private.app.update')->update([
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }
            ',
        ]);


        // private.app.delete
        EmailTemplate::updateOrCreate([
            'identifier' => 'private.app.delete',
        ], [
            'identifier' => 'private.app.delete',
            'subject' => 'Private App has been deleted.',
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                            .main-button {
                                padding:10px;
                                width:auto;
                                -webkit-border-radius:5px;
                                -moz-border-radius:5px;
                                border-radius:5px;
                                color:#FFFFFF;
                            }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                                    Private App has been deleted for [[TEAM_NAME]].
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text line-height="20px">
                                    Dear [[USER_FIRSTNAME]],
                                </mj-text>
                                <mj-text line-height="20px">
                                    <b>[[APP_NAME]]</b> has been deleted on the Gateway:
                                </mj-text>
                                <mj-text>
                                    Date: [[APP_DELETED_AT_DATE]]
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text line-height="20px">
                                    To review or edit the integration, click the link below or visit your account on the Gateway.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View app integrations</mj-button>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>',
        ]);
        EmailTemplate::where('identifier', 'private.app.delete')->update([
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }
            ',
        ]);

        // federation.app.create
        EmailTemplate::updateOrCreate([
            'identifier' => 'federation.app.create',
        ], [
            'identifier' => 'federation.app.create',
            'subject' => 'Contratulations! A new Gateway App has been created.',
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                            .main-button {
                                padding:10px;
                                width:auto;
                                -webkit-border-radius:5px;
                                -moz-border-radius:5px;
                                border-radius:5px;
                                color:#FFFFFF;
                            }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                                    Contratulations! A new Gateway App has been created for [[TEAM_NAME]].
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text line-height="20px">
                                    Dear [[USER_FIRSTNAME]],
                                </mj-text>
                                <mj-text line-height="20px">
                                    <b>[[GATEWAY_APP_NAME]]</b> has been created to enable automated integration with the Gateway:
                                </mj-text>
                                <mj-text>
                                    Date: [[GATEWAY_APP_CREATED_AT_DATE]]
                                </mj-text>
                                <mj-text>
                                    Status: [[GATEWAY_APP_STATUS]]
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text line-height="20px">
                                    To review or edit the integration, click the link below or visit your account on the Gateway.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View app integrations</mj-button>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>',
        ]);
        EmailTemplate::where('identifier', 'federation.app.create')->update([
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/integration/list"
                        }
                    ]
                }
            ',
        ]);


        // federation.app.update
        EmailTemplate::updateOrCreate([
            'identifier' => 'federation.app.update',
        ], [
            'identifier' => 'federation.app.update',
            'subject' => 'Gateway App has been updated.',
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                            .main-button {
                                padding:10px;
                                width:auto;
                                -webkit-border-radius:5px;
                                -moz-border-radius:5px;
                                border-radius:5px;
                                color:#FFFFFF;
                            }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">
                                    Gateway App has been updated for [[TEAM_NAME]].
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text line-height="20px">
                                    Dear [[USER_FIRSTNAME]],
                                </mj-text>
                                <mj-text line-height="20px">
                                    <b>[[GATEWAY_APP_NAME]]</b> has been updated on the Gateway:
                                </mj-text>
                                <mj-text>
                                    Date: [[GATEWAY_APP_UPDATED_AT_DATE]]
                                </mj-text>
                                <mj-text>
                                    Status: [[GATEWAY_APP_STATUS]]
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text line-height="20px">
                                    To review or edit the integration, click the link below or visit your account on the Gateway.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View app integrations</mj-button>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>',
        ]);
        EmailTemplate::where('identifier', 'federation.app.update')->update([
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/integration/list"
                        }
                    ]
                }
            ',
        ]);

    }
}
