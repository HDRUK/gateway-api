<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use Illuminate\Console\Command;

class UpdateEmailTemplateDARReviewGat6448 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-email-template-dar-gat6448';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update email templates for dar review comment notifications (GAT-6448)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // dar.review.researcher
        EmailTemplate::updateOrCreate(
            [
            'identifier' => 'dar.review.researcher',
        ],
            [
            'identifier' => 'dar.review.researcher',
            'subject' => 'New comment on DAR: [[PROJECT_TITLE]]',
            'body' => '
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
            ',
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/data-access-requests"
                        }
                    ]
                }
            '
        ]
        );

        // dar.review.custodian
        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'dar.review.custodian',
            ],
            [
                'identifier' => 'dar.review.custodian',
                'subject' => 'New comment on DAR: [[PROJECT_TITLE]]',
                'body' => '
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
                ',
                'buttons' => '
                    {
                        "replacements": [
                            {
                                "placeholder": "[[BUTTON_1_URL]]",
                                "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/data-access-requests/applications"
                            }
                        ]
                    }
                '
            ]
        );
    }
}
