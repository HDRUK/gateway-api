<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use Illuminate\Console\Command;

class UpdateEmailTemplateDARStatusGat5857 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-email-template-dar-gat5857';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update email templates for dar application submission notifications (GAT-5857)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // dar.submission.researcher
        EmailTemplate::updateOrCreate(
            [
            'identifier' => 'dar.submission.researcher',
        ],
            [
            'identifier' => 'dar.submission.researcher',
            'subject' => 'DAR Submitted: [[USER_FIRST_NAME]]',
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
            ',
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/data-access-requests/application/[[APPLICATION_ID]]"
                        }
                    ]
                }
            '
        ]
        );

        // dar.submission.custodian
        EmailTemplate::updateOrCreate(
            [
                'identifier' => 'dar.submission.custodian',
            ],
            [
                'identifier' => 'dar.submission.custodian',
                'subject' => 'New DAR Received: [[USER_FIRST_NAME]]',
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
