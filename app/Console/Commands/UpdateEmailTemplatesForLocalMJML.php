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

    public string $mjmlHead = '
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

    public function hdrukLogoHeader()
    {
        return '
<mj-section background-color="#ffffff">
    <mj-column>
        <mj-image src="'.config('filesystems.disks.gcs_media.storage_api_uri').'/'.config('filesystems.disks.gcs_media.bucket').'/email/hdruk_logo_email.png" href="'.config('gateway.gateway_url').'" padding="10px 0" alt="" align="center" width="226px" />
    </mj-column>
</mj-section>';
    }

    public function heroBanner(string $headerText)
    {
        return '
<mj-section background-url="'.config('filesystems.disks.gcs_media.storage_api_uri').'/'.config('filesystems.disks.gcs_media.bucket').'/email/hdruk_header_email.png" background-size="cover" background-repeat="no-repeat">
    <mj-column width="100%">
        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">'
        .$headerText.
    '</mj-text>
    </mj-column>
</mj-section>';
    }

    public function hdrukFooter()
    {
        return '
<mj-section>
    <mj-column>
        <mj-text align="center">
            <a style="text-decoration:none" href="'.config('gateway.gateway_url').'">'.config('gateway.gateway_url').'</a>
        </mj-text>
        <mj-text color="#525252" align="center">
            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
        </mj-text>
    </mj-column>
</mj-section>';
    }

    private function standardFullHeader(string $headerText)
    {
        return '<mjml>'.$this->mjmlHead
                .'<mj-body background-color="#FFFFFF">'
                .$this->hdrukLogoHeader()
                .$this->heroBanner($headerText);
    }

    private function standardFullFooter()
    {
        return $this->hdrukFooter()
            .'</mj-body></mjml>';
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
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
          ',
            ]);

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'custodian.team.admin.assign',
            ],
            [
                'identifier' => 'custodian.team.admin.assign',
                'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Team Admin',
                'body' => $this->standardFullHeader('Congratulations! You’ve been granted the Team Administrator permissions for [[TEAM_NAME]].')
                    .'
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
                    .$this->standardFullFooter(),
                'buttons' => '
    {
        "replacements": [
            {
                "placeholder": "[[BUTTON_1_URL]]",
                "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/team-management"
            }
        ]
    }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'custodian.team.admin.remove',
            ],
            [
                'identifier' => 'custodian.team.admin.remove',
                'subject' => 'You have been removed as a Team Admin for the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('Your Team Administrator permissions for [[TEAM_NAME]] have been removed.')
                    .'
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
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'custodian.dar.manager.assign',
            ],
            [
                'identifier' => 'custodian.dar.manager.assign',
                'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Data Access Manager',
                'body' => $this->standardFullHeader('Congratulations! You’ve been granted the Data Access Request Manager permissions for [[TEAM_NAME]].')
                    .'
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
                    .$this->standardFullFooter(),
            ]
        );
        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'custodian.dar.manager.remove',
            ],
            [
                'identifier' => 'custodian.dar.manager.remove',
                'subject' => 'You have been removed as a Data Access Manager for the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('Your Data Access Request Manager permissions for [[TEAM_NAME]] have been removed.')
                    .'
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
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'dar.reviewer.assign',
            ],
            [
                'identifier' => 'dar.reviewer.assign',
                'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Reviewer',
                'body' => $this->standardFullHeader('Congratulations! You’ve been granted the Data Access Request Reviewer permissions for [[TEAM_NAME]].')
                    .'
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
            </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'dar.reviewer.remove',
            ],
            [
                'identifier' => 'dar.reviewer.remove',
                'subject' => 'You have been removed as a Reviewer for the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('Your Data Access Request Reviewer permissions for [[TEAM_NAME]] have been removed.')
                    .'
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
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'developer.assign',
            ],
            [
                'identifier' => 'developer.assign',
                'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Developer',
                'body' => $this->standardFullHeader('Congratulations! You’ve been granted the Developer permissions for [[TEAM_NAME]].')
                    .'
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
                    .$this->standardFullFooter(),
                'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }',
            ]
        );
        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'developer.remove',
            ],
            [
                'identifier' => 'developer.remove',
                'subject' => 'You have been removed as a Developer for the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('Your Developer permissions for [[TEAM_NAME]] have been removed.')
                    .'
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
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'metadata.editor.assign',
            ],
            [
                'identifier' => 'metadata.editor.assign',
                'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Metadata Editor',
                'body' => $this->standardFullHeader('Congratulations! You’ve been granted the Metadata Editor permissions for [[TEAM_NAME]].')
                    .'
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
                    .$this->standardFullFooter(),
                'buttons' => '
        {
            "replacements": [
                {
                    "placeholder": "[[BUTTON_1_URL]]",
                    "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/datasets"
                }
            ]
        }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'metadata.editor.remove',
            ],
            [
                'identifier' => 'metadata.editor.remove',
                'subject' => 'You have been removed as a Metadata Editor for the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('Your Metadata Editor permissions for [[TEAM_NAME]] have been removed.')
                    .'
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
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'custodian.metadata.manager.assign',
            ],
            [
                'identifier' => 'custodian.metadata.manager.assign',
                'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Metadata Manager',
                'body' => $this->standardFullHeader('Congratulations! You’ve been granted the Metadata Manager permissions for [[TEAM_NAME]].')
                    .'
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
                    .$this->standardFullFooter(),
                'buttons' => '
        {
            "replacements": [
                {
                    "placeholder": "[[BUTTON_1_URL]]",
                    "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/datasets"
                }
            ]
        }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'custodian.metadata.manager.remove',
            ],
            [
                'identifier' => 'custodian.metadata.manager.remove',
                'subject' => 'You have been removed as a Metadata Manager for the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('Your Metadata Manager permissions for [[TEAM_NAME]] have been removed.')
                    .'
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
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.superadmin.assign',
            ],
            [
                'identifier' => 'hdruk.superadmin.assign',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.superadmin.assign',
                'body' => $this->standardFullHeader('hdruk.superadmin.assign role has been assigned.')
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.admin.assign',
            ],
            [
                'identifier' => 'hdruk.admin.assign',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.admin.assign',
                'body' => $this->standardFullHeader('hdruk.admin.assign role has been assigned.')
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.admin.remove',
            ],
            [
                'identifier' => 'hdruk.admin.remove',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.admin.remove',
                'body' => $this->standardFullHeader('hdruk.admin.assign role has been removed.')
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.metadata.assign',
            ],
            [
                'identifier' => 'hdruk.metadata.assign',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.metadata.assign',
                'body' => $this->standardFullHeader('hdruk.metadata.assign role has been assigned.')
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.metadata.remove',
            ],
            [
                'identifier' => 'hdruk.metadata.remove',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.metadata.remove',
                'body' => $this->standardFullHeader('hdruk.metadata.assign role has been removed.')
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.dar.assign',
            ],
            [
                'identifier' => 'hdruk.dar.assign',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.dar.assign',
                'body' => $this->standardFullHeader('hdruk.dar.assign role has been assigned.')
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.dar.remove',
            ],
            [
                'identifier' => 'hdruk.dar.remove',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.dar.remove',
                'body' => $this->standardFullHeader('hdruk.dar.assign role has been removed.')
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.custodian.assign',
            ],
            [
                'identifier' => 'hdruk.custodian.assign',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.custodian.assign',
                'body' => $this->standardFullHeader('hdruk.custodian.assign role has been assigned.')
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'hdruk.custodian.remove',
            ],
            [
                'identifier' => 'hdruk.custodian.remove',
                'subject' => '[[ASSIGNER_NAME]] - hdruk.custodian.remove',
                'body' => $this->standardFullHeader('hdruk.custodian.assign role has been removed.')
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'cohort.discovery.access.expired',
            ],
            [
                'identifier' => 'cohort.discovery.access.expired',
                'subject' => 'Your Cohort Discovery access has expired',
                'body' => $this->standardFullHeader('Your Cohort Discovery access has expired.')
                    .'
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
                    .$this->standardFullFooter(),
                'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[COHORT_DISCOVERY_RENEW_URL]]",
                            "actual": "config(gateway.gateway_url)/en/about/cohort-discovery"
                        }
                    ]
                }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'cohort.discovery.access.will.expire',
            ],
            [
                'identifier' => 'cohort.discovery.access.will.expire',
                'subject' => 'Your Cohort Discovery access will soon expire',
                'body' => $this->standardFullHeader('Your Cohort Discovery access will soon expire.')
                    .'
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
                    .$this->standardFullFooter(),
                'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[COHORT_DISCOVERY_RENEW_URL]]",
                            "actual": "config(gateway.gateway_url)/en/about/cohort-discovery"
                        }
                    ]
                }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'cohort.discovery.access.approved',
            ],
            [
                'identifier' => 'cohort.discovery.access.approved',
                'subject' => 'Congratulations! Your Cohort Discovery registration has been approved.',
                'body' => $this->standardFullHeader('Congratulations! Your Cohort Discovery registration has been approved.')
                    .'
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
                    .$this->standardFullFooter(),
                'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[COHORT_DISCOVERY_ACCESS_URL]]",
                            "actual": "config(gateway.gateway_url)/en/about/cohort-discovery"
                        }
                    ]
                }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'cohort.discovery.access.rejected',
            ],
            [
                'identifier' => 'cohort.discovery.access.rejected',
                'subject' => 'Your Cohort Discovery Registration has been Rejected.',
                'body' => $this->standardFullHeader('Your Cohort Discovery Registration has been Rejected.')
                    .'
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
                        </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'cohort.discovery.access.submitted',
            ],
            [
                'identifier' => 'cohort.discovery.access.submitted',
                'subject' => 'Your Cohort Discovery registration form has been submitted.',
                'body' => $this->standardFullHeader('Your Cohort Discovery registration form has been submitted.')
                    .'
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
                        </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'cohort.discovery.access.banned',
            ],
            [
                'identifier' => 'cohort.discovery.access.banned',
                'subject' => 'Your Cohort Discovery access has been banned.',
                'body' => $this->standardFullHeader('Your Cohort Discovery access has been banned.')
                    .'
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
                        </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'cohort.discovery.access.suspended',
            ],
            [
                'identifier' => 'cohort.discovery.access.suspended',
                'subject' => 'Your Cohort Discovery access has been suspended.',
                'body' => $this->standardFullHeader('Your Cohort Discovery access has been suspended.')
                    .'
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
                </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'apiintegration.developer.create',
            ],
            [
                'identifier' => 'apiintegration.developer.create',
                'subject' => '[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('Congratulations! A new integration has been created for<br> [[TEAM_NAME]].')
                    .'
                    <mj-section>
                    <mj-column>
                        <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                            Dear [[USER_FIRST_NAME]],<br></br>
                            [[API_NAME]] has been created to enable automated integration with the HDR Innovation Gateway.<br></br>
                            To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                        </mj-text>
                        <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View app integrations</mj-button>
                    </mj-column>
                    </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/api-management"
                    }
                ]
            }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'apiintegration.team.admin.create',
            ],
            [
                'identifier' => 'apiintegration.team.admin.create',
                'subject' => '[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('Congratulations! A new integration has been created for<br> [[TEAM_NAME]].')
                    .'
                    <mj-section>
                    <mj-column>
                        <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                            Dear [[USER_FIRST_NAME]],<br></br>
                            [[API_NAME]] has been created to enable automated integration with the HDR Innovation Gateway.<br></br>
                            To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                        </mj-text>
                        <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View app integrations</mj-button>
                    </mj-column>
                    </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/api-management"
                    }
                ]
            }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'apiintegration.other.create',
            ],
            [
                'identifier' => 'apiintegration.other.create',
                'subject' => '[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('Congratulations! A new integration has been created for<br> [[TEAM_NAME]].')
                    .'
                        <mj-section>
                        <mj-column>
                            <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                                Dear [[USER_FIRST_NAME]],<br></br>
                                [[API_NAME]] has been created to enable automated integration with the HDR Innovation Gateway.<br></br>
                                To review or edit the integration, contact your Team Administrator(s) or Developer(s):<br></br>
                                [[LIST_TEAM_ADMINS_AND_DEVELOPERS]]
                            </mj-text>
                        </mj-column>
                        </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'fmaintegration.developer.create',
            ],
            [
                'identifier' => 'fmaintegration.developer.create',
                'subject' => '[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('Congratulations! A new integration has been created for<br> [[TEAM_NAME]].')
                    .'
                    <mj-section>
                    <mj-column>
                        <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                            Dear [[USER_FIRST_NAME]],<br></br>
                            An integration for [[INTEGRATION_TYPE]] has been created to enable automated integration with the HDR Innovation Gateway.<br></br>
                            To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                        </mj-text>
                        <mj-button css-class="main-section" background-color="#00ACCA" href="#">View federated integrations</mj-button>
                    </mj-column>
                    </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'fmaintegration.team.admin.create',
            ],
            [
                'identifier' => 'fmaintegration.team.admin.create',
                'subject' => '[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('Congratulations! A new integration has been created for<br> [[TEAM_NAME]].')
                    .'
            <mj-section>
                <mj-column>
                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                        Dear [[USER_FIRST_NAME]],<br></br>
                        An integration for [[INTEGRATION_TYPE]] has been created to enable automated integration with the HDR Innovation Gateway.<br></br>
                        To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                    </mj-text>
                    <mj-button css-class="main-section" background-color="#00ACCA" href="#">View federated integrations</mj-button>
                </mj-column>
            </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'fmaintegration.other.create',
            ],
            [
                'identifier' => 'fmaintegration.other.create',
                'subject' => '[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('Congratulations! A new integration has been created for<br> [[TEAM_NAME]].')
                    .'
                <mj-section>
                    <mj-column>
                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                        Dear [[USER_FIRST_NAME]],<br></br>
                        An integration for [[INTEGRATION_TYPE]] has been created to enable automated integration with the HDR Innovation Gateway.<br></br>
                        To review or edit the integration, contact your Team Administrator(s) or Developer(s):<br></br>
                        [[LIST_TEAM_ADMINS_AND_DEVELOPERS]]
                    </mj-text>
                    </mj-column>
                </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'fmaintegration.developer.disable',
            ],
            [
                'identifier' => 'fmaintegration.developer.disable',
                'subject' => 'An integration has been disabled for the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('An integration was disabled on the Gateway for<br> [[TEAM_NAME]].')
                    .'
        	
            <mj-section>
                <mj-column>
                <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                    Dear [[USER_FIRST_NAME]],<br></br>
                    This is an automated notification that [[DISABLER]] disabled an integration on the HDR Innovation Gateway.<br></br>
                        To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                </mj-text>
                <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View integrations</mj-button>
                </mj-column>
            </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'fmaintegration.team.admin.disable',
            ],
            [
                'identifier' => 'fmaintegration.team.admin.disable',
                'subject' => 'An integration has been disabled for the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('An integration was disabled on the Gateway for<br> [[TEAM_NAME]].')
                    .'
                <mj-section>
                    <mj-column>
                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                        Dear [[USER_FIRST_NAME]],<br></br>
                        This is an automated notification that [[DISABLER]] disabled an integration on the HDR Innovation Gateway.<br></br>
                            To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                    </mj-text>
                    <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View integrations</mj-button>
                    </mj-column>
                </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'fmaintegration.other.disable',
            ],
            [
                'identifier' => 'fmaintegration.other.disable',
                'subject' => 'An integration has been disabled for the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('An integration was disabled on the Gateway for<br> [[TEAM_NAME]].')
                    .'
                        <mj-section>
                        <mj-column>
                            <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                                Dear [[USER_FIRST_NAME]],<br></br>
                                This is an automated notification that [[DISABLER]] disabled an integration on the HDR Innovation Gateway.<br></br>
                                To review or edit the integration, contact your Team Administrator(s) or Developer(s):
                    [[LIST_TEAM_ADMINS_AND_DEVELOPERS]]
                            </mj-text>
                        </mj-column>
                        </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'integration.developer.delete',
            ],
            [
                'identifier' => 'integration.developer.delete',
                'subject' => 'An integration has been deleted for the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('An integration was deleted on the Gateway for<br> [[TEAM_NAME]].')
                    .'
                <mj-section>
                    <mj-column>
                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                        Dear [[USER_FIRST_NAME]],<br></br>
                        This is an automated notification that [[DISABLER]] deleted an integration on the HDR Innovation Gateway. This is not reversible.<br></br>
                        To review or edit integrations, click the link below or visit your account on the Gateway.<br></br>
                    </mj-text>
                    <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View integrations</mj-button>
                    </mj-column>
                </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'integration.team.admin.delete',
            ],
            [
                'identifier' => 'integration.team.admin.delete',
                'subject' => 'An integration has been deleted for the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('An integration was deleted on the Gateway for<br> [[TEAM_NAME]].')
                    .'
                <mj-section>
                    <mj-column>
                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                        Dear [[USER_FIRST_NAME]],<br></br>
                        This is an automated notification that [[DISABLER]] deleted an integration on the HDR Innovation Gateway. This is not reversible.<br></br>
                        To review or edit integrations, click the link below or visit your account on the Gateway.<br></br>
                    </mj-text>
                    <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View integrations</mj-button>
                    </mj-column>
                </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'integration.other.delete',
            ],
            [
                'identifier' => 'integration.other.delete',
                'subject' => 'An integration has been deleted for the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('An integration was deleted on the Gateway for<br> [[TEAM_NAME]].')
                    .'
                        <mj-section>
                        <mj-column>
                            <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                                Dear [[USER_FIRST_NAME]],<br></br>
                                This is an automated notification that [[DISABLER]] deleted an integration on the HDR Innovation Gateway. This is not reversible.<br></br>
                                To review or edit integrations, contact your Team Administrator(s) or Developer(s):
                    [[LIST_TEAM_ADMINS_AND_DEVELOPERS]]
                            </mj-text>
                        </mj-column>
                        </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'integration.developer.error',
            ],
            [
                'identifier' => 'integration.developer.error',
                'subject' => 'An automation error occurred for the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('An integration was disabled on the Gateway for<br> [[TEAM_NAME]].')
                    .'
                <mj-section>
                    <mj-column>
                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                        Dear [[USER_FIRST_NAME]],<br></br>
                        This is an automated notification that on [[DATE_OF_ERROR]]  there was an error during the scheduled cloud run for the [[INTEGRATION_TYPE]] integration. Summary of the synchronisations is below.<br></br>
                        To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                    </mj-text>
                    <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View integrations</mj-button>
                    </mj-column>
                </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'integration.team.admin.error',
            ],
            [
                'identifier' => 'integration.team.admin.error',
                'subject' => 'An automation error occurred for the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('An integration was disabled on the Gateway for<br> [[TEAM_NAME]].')
                    .'
                <mj-section>
                    <mj-column>
                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                        Dear [[USER_FIRST_NAME]],<br></br>
                        This is an automated notification that on [[DATE_OF_ERROR]]  there was an error during the scheduled cloud run for the [[INTEGRATION_TYPE]] integration. Summary of the synchronisations is below.<br></br>
                        To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                    </mj-text>
                    <mj-button css-class="main-section" background-color="#00ACCA" href="[[BUTTON_1_URL]]">View integrations</mj-button>
                    </mj-column>
                </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/integration"
                    }
                ]
            }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'integration.other.error',
            ],
            [
                'identifier' => 'integration.other.error',
                'subject' => 'An automation error occurred for the [[TEAM_NAME]] team on the Gateway.',
                'body' => $this->standardFullHeader('An automation error occurred for an integration on the Gateway for<br> [[TEAM_NAME]].')
                    .'
                    <mj-section>
                        <mj-column>
                        <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                            Errors:<br></br>
                            [[LIST_OF_ERRORS]]<br></br>
                            Success:<br></br>
                            [[LIST_OF_SUCCESS]]<br></br>
                        </mj-text>
                        </mj-column>
                    </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'feasibilityenquiry.firstmessage',
            ],
            [
                'identifier' => 'feasibilityenquiry.firstmessage',
                'subject' => 'Feasibility Enquiry from the Health Data Research Gateway: [[USER_FIRST_NAME]] [[USER_LAST_NAME]], [[PROJECT_TITLE]]',
                'body' => $this->standardFullHeader('Feasibility enquiry received.')
                    .'
                        <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" >Dear [[TEAM_NAME]],<br><br>
                                    You have received a feasibility enquiry from [[USER_FIRST_NAME]], details of which can be found in the thread below. You can respond by using the reply button within your email client.<br><br>
                                    Submitted information:
                                    <div>[[MESSAGE_BODY]]</div>
                                </mj-text>
                            </mj-column>
                        </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'dar.notifymessage',
            ],
            [
                'identifier' => 'dar.notifymessage',
                'subject' => 'New Data Access Enquiry reply from [[USER_FIRST_NAME]] [[USER_LAST_NAME]]: [[PROJECT_TITLE]]',
                'body' => $this->standardFullHeader('New comment on the Data Access Request for [[PROJECT_TITLE]].')
                    .'
                        <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" >Dear [[RECIPIENT_NAME]],<br><br>
                                    You have received a response to the dataset access enquiry [[PROJECT_TITLE]], details of which can be found in the thread below. You can respond by using the reply button within your email client.<br><br>
                                    Submitted information:
                                    <div>[[MESSAGE_BODY]]</div>
                                </mj-text>
                            </mj-column>
                        </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'generalenquiry.firstmessage',
            ],
            [
                'identifier' => 'generalenquiry.firstmessage',
                'subject' => 'General Enquiry from the Health Data Research Gateway: [[USER_FIRST_NAME]] [[USER_LAST_NAME]], [[USER_ORGANISATION]]',
                'body' => $this->standardFullHeader('General Enquiry received.')
                    .'
                        <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" >Dear [[TEAM_NAME]],<br><br>
                                    You have received a general enquiry from [[USER_FIRST_NAME]], details of which can be found in the thread below. You can respond by using the reply button within your email client.<br><br>
                                    Submitted information:
                                    <div>[[MESSAGE_BODY]]</div>
                                </mj-text>
                            </mj-column>
                        </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'cohort.request.admin.approve',
            ],
            [
                'identifier' => 'cohort.request.admin.approve',
                'subject' => 'You have been assigned the role of Cohort Discovery admin on the Gateway',
                'body' => $this->standardFullHeader('You have been assigned the role of Cohort Discovery admin on the Gateway')
                    .'
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
                        </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'cohort.request.admin.remove',
            ],
            [
                'identifier' => 'cohort.request.admin.remove',
                'subject' => 'Your Cohort Discovery admin permissions has been removed',
                'body' => $this->standardFullHeader('Your Cohort Discovery admin permissions has been removed')
                    .'
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
                        </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'private.app.create',
            ],
            [
                'identifier' => 'private.app.create',
                'subject' => 'Congratulations! A new Private App has been created.',
                'body' => $this->standardFullHeader('Congratulations! A new Private App has been created for [[TEAM_NAME]]')
                    .'
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
                        </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
            {
                "replacements": [
                    {
                        "placeholder": "[[BUTTON_1_URL]]",
                        "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                    }
                ]
            }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'private.app.update',
            ],
            [
                'identifier' => 'private.app.update',
                'subject' => 'Private App has been updated.',
                'body' => $this->standardFullHeader('Private App has been updated for [[TEAM_NAME]]')
                    .'
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
                            </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'private.app.delete',
            ],
            [
                'identifier' => 'private.app.delete',
                'subject' => 'Private App has been deleted.',
                'body' => $this->standardFullHeader('Private App has been deleted for [[TEAM_NAME]].')
                    .'
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
                        </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'federation.app.create',
            ],
            [
                'identifier' => 'federation.app.create',
                'subject' => 'Congratulations! A new Gateway App has been created.',
                'body' => $this->standardFullHeader('Congratulations! A new Gateway App has been created for [[TEAM_NAME]].')
                    .'
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
                        </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'federation.app.update',
            ],
            [
                'identifier' => 'federation.app.update',
                'subject' => 'Gateway App has been updated.',
                'body' => $this->standardFullHeader('Gateway App has been updated for [[TEAM_NAME]].')
                    .'
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
                        </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'update.roles.team.user',
            ],
            [
                'identifier' => 'update.roles.team.user',
                'subject' => 'Congratulations! Your permissions have changed.',
                'body' => $this->standardFullHeader('Congratulations! Your permissions have changed for [[TEAM_NAME]]')
                    .'
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
                            </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
                    {
                        "replacements": [
                            {
                                "placeholder": "[[BUTTON_1_URL]]",
                                "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/team-management"
                            }
                        ]
                    }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'add.new.user.team',
            ],
            [
                'identifier' => 'add.new.user.team',
                'subject' => 'Congratulations! Your have been added to a team.',
                'body' => $this->standardFullHeader('Congratulations! You have been added to [[TEAM_NAME]]')
                    .'
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
                            </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
                    {
                        "replacements": [
                            {
                                "placeholder": "[[BUTTON_1_URL]]",
                                "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/team-management"
                            }
                        ]
                    }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'dar.firstmessage',
            ],
            [
                'identifier' => 'dar.firstmessage',
                'subject' => 'New Data Access Enquiry from [[USER_FIRST_NAME]] [[USER_LAST_NAME]]: [[PROJECT_TITLE]]',
                'body' => $this->standardFullHeader('Dataset Access Enquiry received.')
                    .'
                        <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                            <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" >Dear [[TEAM_NAME]],<br><br>
                                    You have received a dataset access enquiry from [[USER_FIRST_NAME]] [[USER_LAST_NAME]], details of which can be found in the thread below. You can respond by using the reply button within your email client.<br><br>
                                    Submitted information:
                                    <div>[[MESSAGE_BODY]]</div>
                                </mj-text>
                            </mj-column>
                        </mj-section>'
                    .$this->standardFullFooter(),
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'dar.status.researcher',
            ],
            [
                'identifier' => 'dar.status.researcher',
                'subject' => 'DAR Status Update: [[USER_FIRST_NAME]]',
                'body' => $this->standardFullHeader('Status Change for Data Access Request: [[PROJECT_TITLE]].')
                    .'
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
                            </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
                    {
                        "replacements": [
                            {
                                "placeholder": "[[BUTTON_1_URL]]",
                                "actual": "config(gateway.gateway_url)/en/account/data-access-requests/application/[[APPLICATION_ID]]"
                            }
                        ]
                    }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'private.app.update.clientid',
            ],
            [
                'identifier' => 'private.app.update.clientid',
                'subject' => 'Private App has been updated.',
                'body' => $this->standardFullHeader('The Client ID for a Private App on the Gateway has been changed')
                    .'
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
                            </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'dar.review.researcher',
            ],
            [
                'identifier' => 'dar.review.researcher',
                'subject' => 'New comment on DAR: [[PROJECT_TITLE]]',
                'body' => $this->standardFullHeader('New comment on the Data Access Request for [[PROJECT_TITLE]].')
                    .'
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
                        </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/data-access-requests"
                        }
                    ]
                }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'dar.review.custodian',
            ],
            [
                'identifier' => 'dar.review.custodian',
                'subject' => 'New comment on DAR: [[PROJECT_TITLE]]',
                'body' => $this->standardFullHeader('New comment on the Data Access Request for [[PROJECT_TITLE]].')
                    .'
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
                            </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
                    {
                        "replacements": [
                            {
                                "placeholder": "[[BUTTON_1_URL]]",
                                "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/data-access-requests/applications"
                            }
                        ]
                    }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'dar.submission.researcher',
            ],
            [
                'identifier' => 'dar.submission.researcher',
                'subject' => 'DAR Submitted: [[USER_FIRST_NAME]]',
                'body' => $this->standardFullHeader('Your Data Access Request has been submitted.')
                    .'
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
                        </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/data-access-requests/application/[[APPLICATION_ID]]"
                        }
                    ]
                }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'dar.submission.custodian',
            ],
            [
                'identifier' => 'dar.submission.custodian',
                'subject' => 'New DAR Received: [[USER_FIRST_NAME]]',
                'body' => $this->standardFullHeader('A new Data Access Request has been received.')
                    .'
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
                            </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
                    {
                        "replacements": [
                            {
                                "placeholder": "[[BUTTON_1_URL]]",
                                "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/data-access-requests/applications"
                            }
                        ]
                    }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'update.roles.team.notifications',
            ],
            [
                'identifier' => 'update.roles.team.notifications',
                'subject' => 'Congratulations! Permissions have changed for team users.',
                'body' => $this->standardFullHeader('Permissions have changed for [[TEAM_NAME]]')
                    .'
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
                        </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/team-management"
                        }
                    ]
                }',
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'user.email_verification',
            ],
            [
                'identifier' => 'user.email_verification',
                'subject' => 'Verify your email address',
                'body' => $this->standardFullHeader('Email Verification')
                    .'
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
                        </mj-section>'
                    .$this->standardFullFooter(),
                'buttons' => '
                    {
                        "replacements": [
                            {
                                "placeholder": "[[BUTTON_1_URL]]",
                                "actual": "config(gateway.gateway_url)/en/verification/[[UUID]]"
                            }
                        ]
                    }',
            ]
        );
    }
}
