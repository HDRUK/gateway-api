<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use Illuminate\Console\Command;

class UpdateEmailTemplatesForLocalMJML extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-email-templates-for-local-mjml';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update email templates to be suitable for use with local MJML';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        function heroBanner(str $headerText)
        {
            return '
<mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
    <mj-column width="100%">
        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">' 
        . $headerText . 
    '</mj-text>
    </mj-column>
</mj-section>';
        }

        $mjmlHead = '
<mj-head>
    <mj-html-attributes>
        <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="text-color" text-color="#000000"></mj-html-attribute>
        <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="font-family" font-family="-apple-system, BlinkMacSystemFont,
            Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif">
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
            Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif" />
        <mj-text font-size="14px" />
        <mj-text color="#3C3C3B" />
        <mj-text line-height="1.7" />
        <mj-text font-weight="400" />
    </mj-attributes>
</mj-head>';

        $hdrukLogoHeader = '
<mj-section background-color="#ffffff">
    <mj-column>
        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
    </mj-column>
</mj-section>';
        
        $hdrukFooter = '
<mj-section>
    <mj-column>
        <mj-text align="center">
            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
        </mj-text>
        <mj-text color="#525252" align="center">
            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
        </mj-text>
    </mj-column>
</mj-section>';

        function standardFullHeader(str $headerText)
        {
            return '<mjml>' . $mjmlHead
                    . '<mj-body background-color="#FFFFFF">' 
                    . $hdrukLogoHeader 
                    . heroBanner($headerText);
        }

	    EmailTemplate::updateOrCreate(
            [
            	'identifier' => 'example_template',
	        ],
            [
				'identifier' => 'example_template',
				'subject' => 'Example Template',
				'body' => '
<mjml>
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
          '
          ]);
        }}

        EmailTemplate::updateOrCreate(
            [
            	'identifier' => 'custodian.team.admin.assign',
	        ],
            [
				'identifier' => 'custodian.team.admin.assign',
				'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Team Admin',
				'body' => standardFullHeader('Congratulations! You’ve been granted the Team Administrator permissions for [[TEAM_NAME]].')
                    . '
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
        </mj-section>' 
        . $hdrukFooter 
        . '</mj-body>
        </mjml>	',
        'buttons' => '
    {
        "replacements": [
            {
                "placeholder": "[[BUTTON_1_URL]]",
                "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/team-management"
            }
        ]
    }'
            ]
        );
        
        EmailTemplate::updateOrCreate(
            [
            	'identifier' => 'custodian.team.admin.remove'
            ],
            [	
                'identifier' => 'custodian.team.admin.remove',
            	'subject' => 'You have been removed as a Team Admin for the [[TEAM_NAME]] team on the Gateway.',
                'body' => standardFullHeader('Your Team Administrator permissions for [[TEAM_NAME]] have been removed.')
                    . '
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
        </mj-section>' 
        . $hdrukFooter 
        . '</mj-body>
        </mjml>	'
            ]
        );

        EmailTemplate::updateOrCreate(
            [
            	'identifier' => 'custodian.dar.manager.assign'
            ],
            [ 
                'identifier' => 'custodian.dar.manager.assign',
                'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Data Access Manager',	
                'body' => standardFullHeader('Congratulations! You’ve been granted the Data Access Request Manager permissions for [[TEAM_NAME]].')
                    . '
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
            </mj-section>' 
        . $hdrukFooter 
        . '</mj-body>
        </mjml>	'
            ]
        );
        EmailTemplate::updateOrCreate(
            [
            	'identifier' => 'custodian.dar.manager.remove'
            ],
            [	
                'identifier' => 'custodian.dar.manager.remove',
            	'subject' => 'You have been removed as a Data Access Manager for the [[TEAM_NAME]] team on the Gateway.',	
                'body' => standardFullHeader('Your Data Access Request Manager permissions for [[TEAM_NAME]] have been removed.')
                    . '
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
            </mj-section>'
        . $hdrukFooter
        . '</mj-body>
        </mjml>	'
            ]
        );


        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'dar.reviewer.assign',
            ],
            [
                'identifier' => 'dar.reviewer.assign',
                'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Reviewer',
                'body' => standardFullHeader('Congratulations! You’ve been granted the Data Access Request Reviewer permissions for [[TEAM_NAME]].')
                    . '
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
            </mj-section>'
            . $hdrukFooter
            . '</mj-body>
            </mjml>	'
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'dar.reviewer.remove',
            ],
            [
                'identifier' => 'dar.reviewer.remove',
                'subject' => 'You have been removed as a Reviewer for the [[TEAM_NAME]] team on the Gateway.',
                'body' => standardFullHeader('Your Data Access Request Reviewer permissions for [[TEAM_NAME]] have been removed.')
                    . '
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
            </mj-section>'
            . $hdrukFooter
            . '</mj-body>
            </mjml>	'
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'developer.assign',
            ],
            [
                'identifier' => 'developer.assign',
                'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Developer',
                'body' => standardFullHeader('Congratulations! You’ve been granted the Developer permissions for [[TEAM_NAME]].')
                    . '
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
            </mj-section>'
                . $hdrukFooter
                . '</mj-body>
            </mjml>	',
                'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }'
            ]
        );
        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'developer.remove',
            ],
            [
                'identifier' => 'developer.remove',
                'subject' => 'You have been removed as a Developer for the [[TEAM_NAME]] team on the Gateway.',
                'body' => standardFullHeader('Your Developer permissions for [[TEAM_NAME]] have been removed.')
                    . '
            <mj-section>
                <mj-column width="100%">
                    <mj-text line-height="20px">
                        Dear [[USER_FIRSTNAME]],
                    </mj-text>
                    <mj-text>
                        You have been removed as Developer for [[TEAM_NAME]] on the Gateway.
                    </mj-text>
                    <mj-text>
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
            </mj-section>'
            . $hdrukFooter
            . '</mj-body>
            </mjml>	'
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'metadata.editor.assign',
            ],
            [
                'identifier' => 'metadata.editor.assign',
                'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Metadata Editor',
                'body' => standardFullHeader('Congratulations! You’ve been granted the Metadata Editor permissions for [[TEAM_NAME]].')
                    . '
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
            </mj-section>'
            . $hdrukFooter
            . '</mj-body>
            </mjml>	',
                'buttons' => '
        {
            "replacements": [
                {
                    "placeholder": "[[BUTTON_1_URL]]",
                    "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/datasets"
                }
            ]
        }'
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'metadata.editor.remove',
            ],
            [
                'identifier' => 'metadata.editor.remove',
                'subject' => 'You have been removed as a Metadata Editor for the [[TEAM_NAME]] team on the Gateway.',
                'body' => standardFullHeader('Your Metadata Editor permissions for [[TEAM_NAME]] have been removed.')
                    . '
            <mj-section>
                <mj-column width="100%">
                    <mj-text line-height="20px">
                        Dear [[USER_FIRSTNAME]],
                    </mj-text>
                    <mj-text>
                        You have been removed as a Metadata Editor for [[TEAM_NAME]] on the Gateway.
                    </mj-text>
                    <mj-text>
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
            </mj-section>'
            . $hdrukFooter
            . '</mj-body>
            </mjml>	'
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'custodian.metadata.manager.assign',
            ],
            [
                'identifier' => 'custodian.metadata.manager.assign',
                'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Metadata Manager',
                'body' => standardFullHeader('Congratulations! You’ve been granted the Metadata Manager permissions for [[TEAM_NAME]].')
                    . '
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
            </mj-section>'
                . $hdrukFooter
                . '</mj-body>
            </mjml>	',
                'buttons' => '
        {
            "replacements": [
                {
                    "placeholder": "[[BUTTON_1_URL]]",
                    "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/datasets"
                }
            ]
        }'
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'custodian.metadata.manager.remove',
            ],
            [
                'identifier' => 'custodian.metadata.manager.remove',
                'subject' => 'You have been removed as a Metadata Manager for the [[TEAM_NAME]] team on the Gateway.',
                'body' => standardFullHeader('Your Metadata Manager permissions for [[TEAM_NAME]] have been removed.')
                    . '
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
            </mj-section>'
                . $hdrukFooter
                . '</mj-body>
            </mjml>	'
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.superadmin.assign',
            ],
            [
                'identifier' => 'hdruk.superadmin.assign',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.superadmin.assign',
                'body' => standardFullHeader('hdruk.superadmin.assign role has been assigned.')
                . $hdrukFooter
                . '</mj-body>
            </mjml>	'
            ]
        );
        
        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.admin.assign',
            ],
            [
                'identifier' => 'hdruk.admin.assign',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.admin.assign',
                'body' => standardFullHeader('hdruk.admin.assign role has been assigned.')
                . $hdrukFooter
                . '</mj-body>
            </mjml>	'
            ]
            );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.admin.remove',
            ],
            [
                'identifier' => 'hdruk.admin.remove',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.admin.remove',
                'body' => standardFullHeader('hdruk.admin.assign role has been removed.')
                . $hdrukFooter
                . '</mj-body>
            </mjml>	'
            ]
        );
        
        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.metadata.assign',
            ],
            [
                'identifier' => 'hdruk.metadata.assign',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.metadata.assign',
                'body' => standardFullHeader('hdruk.metadata.assign role has been assigned.')
                . $hdrukFooter
                . '</mj-body>
            </mjml>	'
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.metadata.remove',
            ],
            [
                'identifier' => 'hdruk.metadata.remove',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.metadata.remove',
                'body' => standardFullHeader('hdruk.metadata.assign role has been removed.')
                . $hdrukFooter
                . '</mj-body>
            </mjml>	'
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.dar.assign',
            ],
            [
                'identifier' => 'hdruk.dar.assign',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.dar.assign',
                'body' => standardFullHeader('hdruk.dar.assign role has been assigned.')
                . $hdrukFooter
                . '</mj-body>
            </mjml>	'
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.dar.remove',
            ],
            [
                'identifier' => 'hdruk.dar.remove',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.dar.remove',
                'body' => standardFullHeader('hdruk.dar.assign role has been removed.')
                . $hdrukFooter
                . '</mj-body>
            </mjml>	'
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.custodian.assign',
            ],
            [
                'identifier' => 'hdruk.custodian.assign',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.custodian.assign',
                'body' => standardFullHeader('hdruk.custodian.assign role has been assigned.')
                . $hdrukFooter
                . '</mj-body>
            </mjml>	'
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.custodian.remove',
            ],
            [
                'identifier' => 'hdruk.custodian.remove',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.custodian.remove',
                'body' => standardFullHeader('hdruk.custodian.assign role has been removed.')
                . $hdrukFooter
                . '</mj-body>
            </mjml>	'
            ]
        );
	
        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'cohort.discovery.access.expired',
            ],
            [
                'identifier' => 'cohort.discovery.access.expired',
                'subject' => 'Your Cohort Discovery access has expired',
                'body' => standardFullHeader('Your Cohort Discovery access has expired.')
                    . '
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
                        </mj-section>'
                        . $hdrukFooter
                        . '</mj-body>
                </mjml>	',
                'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[COHORT_DISCOVERY_RENEW_URL]]",
                            "actual": "config(gateway.gateway_url)/en/about/cohort-discovery"
                        }
                    ]
                }'
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'cohort.discovery.access.will.expire',
            ],
            [
                'identifier' => 'cohort.discovery.access.will.expire',
                'subject' => 'Your Cohort Discovery access will soon expire',
                'body' => standardFullHeader('Your Cohort Discovery access will soon expire.')
                    . '
                        <mj-section>
                            <mj-column width="100%">
                                <mj-text  line-height="20px">
                                    Dear [[USER_FIRSTNAME]],
                                </mj-text>
                                <mj-text line-height="30px">
                                    Your Cohort Discovery account will expire [[EXPIRE_DATE]]. After it expires, please login to the Gateway and visit the <a style="text-decoration:none" href="[[COHORT_DISCOVERY_ACCESS_URL]]">Cohort Discovery information page</a> to review the current terms and conditions, and request renewal.
                                </mj-text>
                                <mj-text>
                                    <mj-text>
                                        Regards,<br/>
                                        Gateway Cohort Discovery Admin.
                                    </mj-text>
                                </mj-text>
                            </mj-column>
                        </mj-section>'
                        . $hdrukFooter
                        . '</mj-body>
                </mjml>	',
                'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[COHORT_DISCOVERY_RENEW_URL]]",
                            "actual": "config(gateway.gateway_url)/en/about/cohort-discovery"
                        }
                    ]
                }'
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'cohort.discovery.access.approved',
            ],
            [
                'identifier' => 'cohort.discovery.access.approved',
                'subject' => 'Congratulations! Your Cohort Discovery registration has been approved.',
                'body' => standardFullHeader('Congratulations! Your Cohort Discovery registration has been approved.')
                    . '
                <mj-section>
                    <mj-column width="100%">
                        <mj-text  line-height="20px">
                            Dear [[USER_FIRSTNAME]],
                        </mj-text>
                        <mj-text line-height="30px">
                            You have been granted access for Cohort Discovery under [[USER_EMAIL]]. Please use the button below to access Cohort Discovery, you can also watch our helpful video on how to use the tool.<br/>Your Cohort Discovery access is valid for a 6-month period after which you will need to re-new your access.<br/>If you require further support, you can raise a support ticket via the Health Data Research Gateway.
                        </mj-text>
                        <mj-text>
                            <mj-text>
                                Regards,<br/>
                                Gateway Cohort Discovery Admin.
                            </mj-text>
                        </mj-text>
                        <mj-button css-class="main-section" background-color="#00ACCA" href="[[COHORT_DISCOVERY_ACCESS_URL]]">Access Cohort Discovery</mj-button>
                    </mj-column>
                </mj-section>'
                . $hdrukFooter
                . '</mj-body>
                </mjml>	',
                'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[COHORT_DISCOVERY_ACCESS_URL]]",
                            "actual": "config(gateway.gateway_url)/en/about/cohort-discovery"
                        }
                    ]
                }'
            ]
        );
            
cohort.discovery.access.rejected	cohort.discovery.access.rejected	Your Cohort Discovery Registration has been Rejected.	
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
                                    Thank you for your patience whilst we reviewed your request to use Cohort Discovery.<br/>Unfortunately, your registration for access has been unsuccessful at this time.<br/>Your request does not meet our access criteria, which includes the following:
                                    <ul>
                                        <li>Registering a user profile on the Gateway.</li>
                                        <li>Your Gateway profile must include an organisational or institutional email address (either as primary or secondary emails).</li>
                                        <li>Please also fill in as much information in your Gateway profile as you can to help establish yourself as a bona fide researcher, NHS analyst or equivalent, such as your organisation or institutional name, a short bio, your domain of work and any links to social media (LinkedIn, ResearchGate etc.) or ORCID.</li>
                                    </ul>                    
                                    This information is required to help establish your status as a &lsquo;Safe Person&rsquo; under the Five Safes principles.<br/>If you have any questions on the above decision, please raise a support ticket on the Health Data Research Gateway.
                                </mj-text>
                                <mj-text>
                                <mj-text>
                                    Regards,<br/>
                                    Gateway Cohort Discovery Administrator
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
            	
cohort.discovery.access.submitted	cohort.discovery.access.submitted	Your Cohort Discovery registration form has been submitted.	
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
            	
cohort.discovery.access.banned	cohort.discovery.access.banned	Your Cohort Discovery access has been banned.	
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
                                    This is an automated message to let you know that your access to the Cohort Discovery tool has been permanently removed. If you have any question or would like to discuss this further please raise a support ticket on the Health Data Research Gateway.
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
            	
cohort.discovery.access.suspended	cohort.discovery.access.suspended	Your Cohort Discovery access has been suspended.	
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
                                    This is an automated message to let you know that your access to the Cohort Discovery tool has been suspended. If you have any questions or would like to discuss this further please raise a support ticket on the Health Data Research Gateway.
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
            	
apiintegration.developer.create	apiintegration.developer.create	[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.	
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
        	
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/api-management"
                    }
                ]
            }
            
apiintegration.team.admin.create	apiintegration.team.admin.create	[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.	
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
        	
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/api-management"
                    }
                ]
            }
            
apiintegration.other.create	apiintegration.other.create	[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.	
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
            	
fmaintegration.developer.create	fmaintegration.developer.create	[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.	
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
        	
fmaintegration.team.admin.create	fmaintegration.team.admin.create	[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.	
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
        	
fmaintegration.other.create	fmaintegration.other.create	[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.	
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
            	
fmaintegration.developer.disable	fmaintegration.developer.disable	An integration has been disabled for the [[TEAM_NAME]] team on the Gateway.	
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
        	
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }
            
fmaintegration.team.admin.disable	fmaintegration.team.admin.disable	An integration has been disabled for the [[TEAM_NAME]] team on the Gateway.	
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
        	
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }
            
fmaintegration.other.disable	fmaintegration.other.disable	An integration has been disabled for the [[TEAM_NAME]] team on the Gateway.	
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
            	
integration.developer.delete	integration.developer.delete	An integration has been deleted for the [[TEAM_NAME]] team on the Gateway.	
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
        	
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }
            
integration.team.admin.delete	integration.team.admin.delete	An integration has been deleted for the [[TEAM_NAME]] team on the Gateway.	
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
        	
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }
            
integration.other.delete	integration.other.delete	An integration has been deleted for the [[TEAM_NAME]] team on the Gateway.	
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
            	
integration.developer.error	integration.developer.error	An automation error occurred for the [[TEAM_NAME]] team on the Gateway.	
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
        	
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }
            
integration.team.admin.error	integration.team.admin.error	An automation error occurred for the [[TEAM_NAME]] team on the Gateway.	
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
        	
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }
            
integration.other.error	integration.other.error	An automation error occurred for the [[TEAM_NAME]] team on the Gateway.	
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
            	
feasibilityenquiry.firstmessage	feasibilityenquiry.firstmessage	Feasibility Enquiry from the Health Data Research Gateway: [[USER_FIRST_NAME]] [[USER_LAST_NAME]], [[PROJECT_TITLE]]	
                <mjml>
                    <mj-head>
                        <mj-html-attributes>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="text-color" text-color="#000000"></mj-html-attribute>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="font-family" font-family="-apple-system, BlinkMacSystemFont,
                                'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans','Helvetica Neue', sans-serif">
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
                                'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans','Helvetica Neue', sans-serif" />
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
                                    Submitted information:
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
            	
dar.notifymessage	dar.notifymessage	New Data Access Enquiry reply from [[USER_FIRST_NAME]] [[USER_LAST_NAME]]: [[PROJECT_TITLE]]	
                <mjml>
                    <mj-head>
                        <mj-html-attributes>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="text-color" text-color="#000000"></mj-html-attribute>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="font-family" font-family="-apple-system, BlinkMacSystemFont,
                                Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif">
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
                            <mj-all font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif" />
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
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0px 30px 0px" >New comment on the Data Access Request for [[PROJECT_TITLE]].</mj-text>
                            </mj-column>
                        </mj-section>
                        <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" >Dear [[RECIPIENT_NAME]],<br><br>
                                    You have received a response to the dataset access enquiry [[PROJECT_TITLE]], details of which can be found in the thread below. You can respond by using the reply button within your email client.<br><br>
                                    Submitted information:
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
                </mjml>
            	
generalenquiry.firstmessage	generalenquiry.firstmessage	General Enquiry from the Health Data Research Gateway: [[USER_FIRST_NAME]] [[USER_LAST_NAME]], [[USER_ORGANISATION]]	
                <mjml>
                    <mj-head>
                        <mj-html-attributes>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="text-color" text-color="#000000"></mj-html-attribute>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="font-family" font-family="-apple-system, BlinkMacSystemFont,
                                'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans','Helvetica Neue', sans-serif">
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
                                'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans','Helvetica Neue', sans-serif" />
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
                                    Submitted information:
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
            	
cohort.request.admin.approve	cohort.request.admin.approve	You have been assigned the role of Cohort Discovery admin on the Gateway	
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
                                    You have been assigned the role of Cohort Discovery admin on the Gateway
                                </mj-text>
                            </mj-column>
                        </mj-section>
        
                        <mj-section>
                            <mj-column width="100%">
                                <mj-text  line-height="20px">
                                    Dear [[USER_FIRSTNAME]],
                                </mj-text>
                                <mj-text line-height="20px">
                                    [[ASSIGNER_NAME]] has granted you Cohort Discovery admin permission on the Gateway.
                                </mj-text>
                                <mj-text>
                                    You can now:
                                    <ul>
                                        <li style="line-height:20px;height:auto;">
                                            Review Cohort Discovery registration request.
                                        </li>
                                        <li style="line-height:20px;height:auto;">
                                            Manage users Cohort Discovery status.
                                        </li>
                                        <li style="line-height:20px;height:auto;">
                                            Remove users Cohort Discovery access.
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
                </mjml>	
cohort.request.admin.remove	cohort.request.admin.remove	Your Cohort Discovery admin permissions has been removed	
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
                                    Your Cohort Discovery admin permissions has been removed
                                </mj-text>
                            </mj-column>
                        </mj-section>
        
                        <mj-section>
                            <mj-column width="100%">
                                <mj-text  line-height="20px">
                                    Dear [[USER_FIRSTNAME]],
                                </mj-text>
                                <mj-text line-height="20px">
                                    You have been removed as a Cohort Discovery admin on the Gateway
                                </mj-text>

                                <mj-text>
                                    You can no longer:
                                    <ul>
                                        <li style="line-height:20px;height:auto;">
                                            Review Cohort Discovery user registration request.
                                        </li>
                                        <li style="line-height:20px;height:auto;">
                                            Manage Cohort Discovery users access status.
                                        </li>
                                    </ul>
                                </mj-text>

                                <mj-text line-height="20px">
                                    For more information, please raise a support ticket on the HDR UK Innovation Gateway.
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
private.app.create	private.app.create	Congratulations! A new Private App has been created.	
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
                </mjml>	
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }
            
private.app.update	private.app.update	Private App has been updated.	
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
                                        A member of your team has updated permissions for <b>[[APP_NAME]]</b> on the HDR Innovation Gateway. If you have not already done so, we strongly recommend you generate a new <b>Client ID</b> and update your software to maintain integrity of your integration with the Gateway.
                                    </mj-text>
                                    <mj-text>
                                        Date: [[APP_UPDATED_AT_DATE]]
                                    </mj-text>
                                    <mj-text>
                                        Status: [[APP_STATUS]]
                                    </mj-text>
                                    <mj-text line-height="20px">
                                        The permissions for <b>[[APP_NAME]]</b> are now as follows:<br>
                                        [[APP_PERMISSIONS_LIST]]
                                    </mj-text>
                                    <mj-text line-height="20px">
                                        [[OTHER_USER_MESSAGE]] 
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
                    </mjml>	
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }
private.app.delete	private.app.delete	Private App has been deleted.	
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
                </mjml>	
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }
            
federation.app.create	federation.app.create	Congratulations! A new Gateway App has been created.	
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
                                    Congratulations! A new Gateway App has been created for [[TEAM_NAME]].
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text line-height="20px">
                                    Dear [[USER_FIRSTNAME]],
                                </mj-text>
                                <mj-text line-height="20px">
                                    <b>[[FEDERATION_NAME]]</b> has been created to enable automated integration with the Gateway:
                                </mj-text>
                                <mj-text>
                                    Date: [[FEDERATION_CREATED_AT_DATE]]
                                </mj-text>
                                <mj-text>
                                    Status: [[FEDERATION_STATUS]]
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
                                    <a style="text-decoration:none" href="env(GATEWAY_URL)">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>
                    </mj-body>
                </mjml>	
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }
            
federation.app.update	federation.app.update	Gateway App has been updated.	
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
                                    <b>[[FEDERATION_NAME]]</b> has been updated on the Gateway:
                                </mj-text>
                                <mj-text>
                                    Date: [[FEDERATION_UPDATED_AT_DATE]]
                                </mj-text>
                                <mj-text>
                                    Status: [[FEDERATION_STATUS]]
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
                                    <a style="text-decoration:none" href="env(GATEWAY_URL)">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>
                    </mj-body>
                </mjml>	
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }
            
update.roles.team.user	update.roles.team.user	Congratulations! Your permissions have changed.	
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
                                        Congratulations! Your permissions have changed for [[TEAM_NAME]] 
                                    </mj-text>
                                </mj-column>
                            </mj-section>
            
                            <mj-section>
                                <mj-column width="100%">
                                    <mj-text  line-height="20px">
                                        Dear [[USER_FIRSTNAME]],
                                    </mj-text>
                                    <mj-text line-height="20px">
                                        Your roles within [[TEAM_NAME]] have been updated:
                                    </mj-text>
                                    <mj-text>
                                        Current Roles:<br/>
                                        [[CURRENT_ROLES]]
                                    </mj-text>
                                    <mj-text>
                                        Added Roles:<br/>
                                        [[ADDED_ROLES]]
                                    </mj-text>
                                    <mj-text>
                                        Removed Roles:<br/>
                                        [[REMOVED_ROLES]]
                                    </mj-text>
                                </mj-column>
                            </mj-section>
                          
                              <mj-section>
                                <mj-column>
                                    <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View your Team</mj-button>
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
                    {
                        "replacements": [
                            {
                                "placeholder": "[[BUTTON_1_URL]]",
                                "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/team-management"
                            }
                        ]
                    }
                
add.new.user.team	add.new.user.team	Congratulations! Your have been added to a team.	
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
                                        Congratulations! You have been added to [[TEAM_NAME]]
                                    </mj-text>
                                </mj-column>
                            </mj-section>
            
                            <mj-section>
                                <mj-column width="100%">
                                    <mj-text  line-height="20px">
                                        Dear [[USER_FIRSTNAME]],
                                    </mj-text>
                                    <mj-text line-height="20px">
                                        Your role(s) within [[TEAM_NAME]]:
                                    </mj-text>
                                    <mj-text>
                                        Role(s):<br/>
                                        [[CURRENT_ROLES]]
                                    </mj-text>
                                </mj-column>
                            </mj-section>
                          
                              <mj-section>
                                <mj-column>
                                    <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View your Team</mj-button>
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
                    {
                        "replacements": [
                            {
                                "placeholder": "[[BUTTON_1_URL]]",
                                "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/team-management"
                            }
                        ]
                    }
                
dar.firstmessage	dar.firstmessage	New Data Access Enquiry from [[USER_FIRST_NAME]] [[USER_LAST_NAME]]: [[PROJECT_TITLE]]	
                <mjml>
                    <mj-head>
                        <mj-html-attributes>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="text-color" text-color="#000000"></mj-html-attribute>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="font-family" font-family="-apple-system, BlinkMacSystemFont,
                                Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif">
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
                            <mj-all font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif" />
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
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0px 30px 0px" >Dataset Access Enquiry received.</mj-text>
                            </mj-column>
                        </mj-section>
                        <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" >Dear [[TEAM_NAME]],<br><br>
                                    You have received a dataset access enquiry from [[USER_FIRST_NAME]] [[USER_LAST_NAME]], details of which can be found in the thread below. You can respond by using the reply button within your email client.<br><br>
                                    Submitted information:
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
                </mjml>
            	
dar.status.researcher	dar.status.researcher	DAR Status Update: [[USER_FIRST_NAME]]	
                    <mjml>
                        <mj-head>
                            <mj-html-attributes>
                                <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="text-color" text-color="#000000"></mj-html-attribute>
                                <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="font-family" font-family="-apple-system, BlinkMacSystemFont,
                                    Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif">
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
                                    Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif" />
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
                                    <mj-text align="center" color="#fff" font-size="24px" padding="30px 0px 30px 0px" >Status Change for Data Access Request: [[PROJECT_TITLE]].</mj-text>
                                </mj-column>
                            </mj-section>
                            <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                                <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" >Dear [[USER_FIRST_NAME]],<br><br>
                                        The status of your Data Access Request for [[PROJECT_TITLE]] has been updated.<br><br>
                                        Your Data Access Request status is now: [[STATUS]]<br><br>
                                        You can review the status of your Data Access Request via your profile on the Gateway.
                                    </mj-text>
                                </mj-column>
                            </mj-section>
                            <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                                <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                    <mj-button align="center" background-color="#00ACCA" color="#ffffff" font-weight="normal" border-radius="4px" padding="10px 25px 10px 25px" inner-padding="10px 25px 10px 25px" line-height="120%" target="_blank" vertical-align="middle" border="none" text-align="center" href="[[BUTTON_1_URL]]">View Data Access Request Status Page</mj-button>
                                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" ><br><br>
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
                	
                    {
                        "replacements": [
                            {
                                "placeholder": "[[BUTTON_1_URL]]",
                                "actual": "config(gateway.gateway_url)/en/account/data-access-requests/application/[[APPLICATION_ID]]"
                            }
                        ]
                    }
                
private.app.update.clientid	private.app.update.clientid	Private App has been updated.	
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
                                        The Client ID for a Private App on the Gateway has been changed
                                    </mj-text>
                                </mj-column>
                            </mj-section>

                            <mj-section>
                                <mj-column width="100%">
                                    <mj-text line-height="20px">
                                        Dear [[USER_FIRSTNAME]],
                                    </mj-text>
                                    <mj-text line-height="20px">
                                        A member of your team has initiated the generation of a new <b>Client ID</b> for <b>[[APP_NAME]]</b> on the HDR Innovation Gateway. We strongly recommend you update your software as soon as possible to avoid any disruption with your integration with the Gateway.
                                    </mj-text>
                                    <mj-text>
                                        Date: [[APP_UPDATED_AT_DATE]]
                                    </mj-text>
                                    <mj-text>
                                        Status: [[APP_STATUS]]
                                    </mj-text>
                                    <mj-text line-height="20px">
                                        [[OTHER_USER_MESSAGE]] 
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
                    </mjml>	
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }
dar.review.researcher	dar.review.researcher	New comment on DAR: [[PROJECT_TITLE]]	
                <mjml>
                    <mj-head>
                        <mj-html-attributes>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="text-color" text-color="#000000"></mj-html-attribute>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="font-family" font-family="-apple-system, BlinkMacSystemFont,
                                Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif">
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
                                Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif" />
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
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0px 30px 0px" >New comment on the Data Access Request for [[PROJECT_TITLE]].</mj-text>
                            </mj-column>
                        </mj-section>
                        <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" >Dear [[USER_FIRST_NAME]],<br><br>
                                    You have received a comment from [[CUSTODIAN_NAME]] on your data access request for [[PROJECT_TITLE]], a preview of which can be found in the thread below. 
                                    You can respond via the Gateway by opening the data access request for the project. 
                                    Use the button below to navigate to the Data Access Request management page.<br><br>
                                    [[THREAD]]
                                </mj-text>
                            </mj-column>
                        </mj-section>
                        <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-button align="center" background-color="#00ACCA" color="#ffffff" font-weight="normal" border-radius="4px" padding="10px 25px 10px 25px" inner-padding="10px 25px 10px 25px" line-height="120%" target="_blank" vertical-align="middle" border="none" text-align="center" href="[[BUTTON_1_URL]]">View Data Access Request Management Page</mj-button>
                                <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" ><br><br>
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
            	
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/data-access-requests"
                        }
                    ]
                }
            
dar.review.custodian	dar.review.custodian	New comment on DAR: [[PROJECT_TITLE]]	
                    <mjml>
                        <mj-head>
                            <mj-html-attributes>
                                <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="text-color" text-color="#000000"></mj-html-attribute>
                                <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="font-family" font-family="-apple-system, BlinkMacSystemFont,
                                    Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif">
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
                                    Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif" />
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
                                    <mj-text align="center" color="#fff" font-size="24px" padding="30px 0px 30px 0px" >New comment on the Data Access Request for [[PROJECT_TITLE]].</mj-text>
                                </mj-column>
                            </mj-section>
                            <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                                <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" >Dear [[DAR_MANAGER_FIRST_NAME]],<br><br>
                                        You have received a comment in response to your query on [[PROJECT_TITLE]], a preview of which can be found in the thread below. 
                                        You can respond via the Gateway by opening the data access request for this project. 
                                        Use the button below to navigate to the Data Access Request management page.<br><br>
                                        [[THREAD]]
                                    </mj-text>
                                </mj-column>
                            </mj-section>
                            <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                                <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                    <mj-button align="center" background-color="#00ACCA" color="#ffffff" font-weight="normal" border-radius="4px" padding="10px 25px 10px 25px" inner-padding="10px 25px 10px 25px" line-height="120%" target="_blank" vertical-align="middle" border="none" text-align="center" href="[[BUTTON_1_URL]]">View Data Access Request Management Page</mj-button>
                                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" ><br><br>
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
                	
                    {
                        "replacements": [
                            {
                                "placeholder": "[[BUTTON_1_URL]]",
                                "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/data-access-requests/applications"
                            }
                        ]
                    }
                
dar.submission.researcher	dar.submission.researcher	DAR Submitted: [[USER_FIRST_NAME]]	
                <mjml>
                    <mj-head>
                        <mj-html-attributes>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="text-color" text-color="#000000"></mj-html-attribute>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="font-family" font-family="-apple-system, BlinkMacSystemFont,
                                Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif">
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
                                Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif" />
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
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0px 30px 0px" >Your Data Access Request has been submitted.</mj-text>
                            </mj-column>
                        </mj-section>
                        <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" >Dear [[USER_FIRST_NAME]],<br><br>
                                    Your Data Access Request for [[PROJECT_TITLE]] has been submitted to the following Data Custodian(s):<br><br>
                                    <div>[[CUSTODIANS]]</div>
                                    You will be automatically notified via email of any comments or status changes regarding your data access request.<br><br>
                                    Visit the Gateway to check the status of your Data Access Request.
                                </mj-text>
                            </mj-column>
                        </mj-section>
                        <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-button align="center" background-color="#00ACCA" color="#ffffff" font-weight="normal" border-radius="4px" padding="10px 25px 10px 25px" inner-padding="10px 25px 10px 25px" line-height="120%" target="_blank" vertical-align="middle" border="none" text-align="center" href="[[BUTTON_1_URL]]">View Data Access Request Status Page</mj-button>
                                <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" ><br><br>
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
            	
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/data-access-requests/application/[[APPLICATION_ID]]"
                        }
                    ]
                }
            
dar.submission.custodian	dar.submission.custodian	New DAR Received: [[USER_FIRST_NAME]]	
                    <mjml>
                        <mj-head>
                            <mj-html-attributes>
                                <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="text-color" text-color="#000000"></mj-html-attribute>
                                <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="font-family" font-family="-apple-system, BlinkMacSystemFont,
                                    Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif">
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
                                    Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif" />
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
                                    <mj-text align="center" color="#fff" font-size="24px" padding="30px 0px 30px 0px" >A new Data Access Request has been received.</mj-text>
                                </mj-column>
                            </mj-section>
                            <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                                <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" >Dear [[RECIPIENT_NAME]],<br><br>
                                        A new Data Access Request has been received on [[DATE_OF_APPLICATION]] from [[RESEARCHER_NAME]].
                                        This Data Access Request includes Datasets & Biosamples from the following Data Custodian(s):<br><br>
                                        <div>[[CUSTODIANS]]</div>
                                        Visit you Data Access Request management page on the Gateway, to review and update the Data Access Request status.<br><br>
                                        Comments and status changes will be emailed to the applicant, and any replies will be sent to this email address.<br><br>
                                    </mj-text>
                                </mj-column>
                            </mj-section>
                            <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                                <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                    <mj-button align="center" background-color="#00ACCA" color="#ffffff" font-weight="normal" border-radius="4px" padding="10px 25px 10px 25px" inner-padding="10px 25px 10px 25px" line-height="120%" target="_blank" vertical-align="middle" border="none" text-align="center" href="[[BUTTON_1_URL]]">View Data Access Request Management Page</mj-button>
                                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" ><br><br>
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
                	
                    {
                        "replacements": [
                            {
                                "placeholder": "[[BUTTON_1_URL]]",
                                "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/data-access-requests/applications"
                            }
                        ]
                    }
                
update.roles.team.notifications	update.roles.team.notifications	Congratulations! Permissions have changed for team users.	
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
                                    Permissions have changed for [[TEAM_NAME]] 
                                </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text  line-height="20px">
                                    Dear [[TEAM_NAME]],
                                </mj-text>
                                <mj-text  line-height="20px">
                                    Roles within [[TEAM_NAME]] have been updated:
                                </mj-text>
                                <mj-text line-height="20px">
                                    [[USER_CHANGES]]
                                </mj-text>
                            </mj-column>
                        </mj-section>
                      
                        <mj-section>
                            <mj-column>
                                <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View your Team</mj-button>
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
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/team-management"
                        }
                    ]
                }
            
user.email_verification	user.email_verification	Verify your email address	
                    <mjml>
                    <mj-head>
                        <mj-html-attributes>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="text-color" text-color="#000000"></mj-html-attribute>
                            <mj-html-attribute class="easy-email" multiple-attributes="false" attribute-name="font-family" font-family="-apple-system, BlinkMacSystemFont,
                                Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif">
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
                                Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans,Helvetica Neue, sans-serif" />
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
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0px 30px 0px" >Email Verification</mj-text>
                            </mj-column>
                        </mj-section>
                        <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" >Dear [[USER_FIRST_NAME]],<br><br>
                                      Please verify your email address by clicking the button below. This link will expire in 24 hours.
                                </mj-text>
                            </mj-column>
                        </mj-section>
                        <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-button align="center" background-color="#00ACCA" color="#ffffff" font-weight="normal" border-radius="4px" padding="10px 25px 10px 25px" inner-padding="10px 25px 10px 25px" line-height="120%" target="_blank" vertical-align="middle" border="none" text-align="center" href="[[BUTTON_1_URL]]">Verify Email Address</mj-button>
                                <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" ><br><br>
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
                	
                    {
                        "replacements": [
                            {
                                "placeholder": "[[BUTTON_1_URL]]",
                                "actual": "config(gateway.gateway_url)/en/verification/[[UUID]]"
                            }
                        ]
                    }
                
