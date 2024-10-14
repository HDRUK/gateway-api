<?php

use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('email_templates')
            ->where('identifier', 'feasibilityenquiry.firstmessage')
            ->update(['subject' => 'Feasibility Enquiry from the Health Data Research Gateway: [[USER_FIRST_NAME]] [[USER_LAST_NAME]], [[PROJECT_TITLE]]']);
        DB::table('email_templates')
            ->where('identifier', 'generalenquiry.firstmessage')
            ->update(['subject' => 'General Enquiry from the Health Data Research Gateway: [[USER_FIRST_NAME]] [[USER_LAST_NAME]], [[USER_ORGANISATION]]']);
        // This template had a missing space between two words, no other changes
        DB::table('email_templates')
            ->where('identifier', 'custodian.metadata.manager.assign')
            ->update(['body' => '
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
                </mjml>']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('email_templates')
            ->where('identifier', 'feasibilityenquiry.firstmessage')
            ->update(['subject' => '[[USER_FIRST_NAME]] [[PROJECT_TITLE]]']);
        DB::table('email_templates')
            ->where('identifier', 'generalenquiry.firstmessage')
            ->update(['subject' => '[[USER_FIRST_NAME]] Enquiry']);
        DB::table('email_templates')
        ->where('identifier', 'custodian.metadata.manager.assign')
        ->update(['body' => '
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
                                [[ASSIGNER_NAME]]has granted you Metadata Manager permissions for [[TEAM_NAME]] on the Gateway. 
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
                </mjml>']);

    }
};
