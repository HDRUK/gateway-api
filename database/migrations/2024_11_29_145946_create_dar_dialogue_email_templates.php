<?php

use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('email_templates')
            ->insert([
                'identifier' => 'dar.firstmessage',
                'subject' => '[[USER_FIRST_NAME]] [[PROJECT_TITLE]]',
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
                                    <mj-text align="center" color="#fff" font-size="24px" padding="30px 0px 30px 0px" >Dataset Access Enquiry received.</mj-text>
                                </mj-column>
                            </mj-section>
                            <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                                <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" >Dear [[TEAM_NAME]],<br><br>
                                        You have received a dataset access enquiry from [[USER_FIRST_NAME]], details of which can be found in the thread below. You can respond by using the reply button within your email client.<br><br>
                                        Submitted information
                                        <div>[[MESSAGE_BODY]]</div>
                                    </mj-text>
                                </mj-column>
                            </mj-section>
                            <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                                <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                    <mj-button align="center" background-color="#00ACCA" color="#ffffff" font-weight="normal" border-radius="4px" padding="10px 25px 10px 25px" inner-padding="10px 25px 10px 25px" line-height="120%" target="_blank" vertical-align="middle" border="none" text-align="center" href="#" >View message on the Gateway</mj-button>
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
                '
            ]);

        DB::table('email_templates')
            ->where('identifier', 'dar.notifymessage')
            ->update([
                'subject' => '[[USER_FIRST_NAME]] [[PROJECT_TITLE]]',
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
                                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif" padding="10px 25px 10px 25px" >Dear [[RECIPIENT_NAME]],<br><br>
                                        You have received a response to the dataset access enquiry [[PROJECT_TITLE]], details of which can be found in the thread below. You can respond by using the reply button at the top right.<br><br>
                                        Submitted information
                                        <div>[[MESSAGE_BODY]]</div>
                                    </mj-text>
                                </mj-column>
                            </mj-section>
                            <mj-section background-repeat="repeat" background-size="auto" background-position="top center" border="none" direction="ltr" text-align="center" padding="20px 0px 20px 0px" >
                                <mj-column border="none" vertical-align="top" padding="0px 0px 0px 0px" >
                                    <mj-button align="center" background-color="#00ACCA" color="#ffffff" font-weight="normal" border-radius="4px" padding="10px 25px 10px 25px" inner-padding="10px 25px 10px 25px" line-height="120%" target="_blank" vertical-align="middle" border="none" text-align="center" href="#" >View message on the Gateway</mj-button>
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
                '
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('email_templates')
            ->where(['identifier' => 'dar.firstmessage'])
            ->delete();

        DB::table('email_templates')
            ->where('identifier', 'dar.notifymessage')
            ->update([
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
    }
};
