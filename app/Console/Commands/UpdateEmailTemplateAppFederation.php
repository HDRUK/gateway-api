<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use Illuminate\Console\Command;

class UpdateEmailTemplateAppFederation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-email-template-app-federation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
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
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }
            '
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
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }
            '
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
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }
            '
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
                                    [[GATEWAY_APP_NAME]] has been created to enable automated integration with the Gateway:
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
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/integration/list"
                        }
                    ]
                }
            '
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
                                    [[GATEWAY_APP_NAME]] has been updated on the Gateway:
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
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/integration/list"
                        }
                    ]
                }
            '
        ]);

    }
}
