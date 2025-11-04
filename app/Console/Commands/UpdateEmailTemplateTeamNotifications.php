<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use Illuminate\Console\Command;

class UpdateEmailTemplateTeamNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-email-template-team-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Team email address not receiving notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        EmailTemplate::create([
            'identifier' => 'update.roles.team.notifications',
            'subject' => 'Congratulations! Permissions have changed for team users.',
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
                </mjml>',
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[BUTTON_1_URL]]",
                            "actual": "config(gateway.gateway_url)/en/account/team/[[TEAM_ID]]/team-management"
                        }
                    ]
                }
            '
        ]);
    }
}
