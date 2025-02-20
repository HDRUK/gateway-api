<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use Illuminate\Console\Command;

class UpdateEmailTemplatePrivateAppGat3907 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-email-template-private-app-gat3907';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GAT-3907 :: Update API client ID generated each time permissions are changed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // private.app.update
        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'private.app.update',
            ],
            [
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
                    </mjml>',
                'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }',
            ]
        );

        // private.app.update.clientid
        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'private.app.update.clientid',
            ],
            [
                'identifier' => 'private.app.update.clientid',
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
                    </mjml>',
                'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "env(GATEWAY_URL)/en/account/team/[[TEAM_ID]]/integrations/api-management/list"
                        }
                    ]
                }',
            ]
        );
    }
}
