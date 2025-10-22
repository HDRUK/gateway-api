<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use Illuminate\Console\Command;

class UpdateTeamEmailTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-team-email-templates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GAT-4968 :: When a users permissions change, email updates are sent for ALL permissions, not the change';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        EmailTemplate::where(['identifier' => 'custodian.team.admin.assign'])->delete();
        EmailTemplate::where(['identifier' => 'custodian.team.admin.remove'])->delete();

        EmailTemplate::where(['identifier' => 'custodian.dar.manager.assign'])->delete();
        EmailTemplate::where(['identifier' => 'custodian.dar.manager.remove'])->delete();

        EmailTemplate::where(['identifier' => 'dar.reviewer.assign'])->delete();
        EmailTemplate::where(['identifier' => 'dar.reviewer.remove'])->delete();

        EmailTemplate::where(['identifier' => 'developer.assign'])->delete();
        EmailTemplate::where(['identifier' => 'developer.remove'])->delete();

        EmailTemplate::where(['identifier' => 'metadata.editor.assign'])->delete();
        EmailTemplate::where(['identifier' => 'metadata.editor.remove'])->delete();

        EmailTemplate::where(['identifier' => 'custodian.metadata.manager.assign'])->delete();
        EmailTemplate::where(['identifier' => 'custodian.metadata.manager.remove'])->delete();

        EmailTemplate::where(['identifier' => 'hdruk.superadmin.assign'])->delete();
        EmailTemplate::where(['identifier' => 'hdruk.superadmin.remove'])->delete();

        EmailTemplate::where(['identifier' => 'hdruk.admin.assign'])->delete();
        EmailTemplate::where(['identifier' => 'hdruk.admin.remove'])->delete();

        EmailTemplate::where(['identifier' => 'hdruk.metadata.assign'])->delete();
        EmailTemplate::where(['identifier' => 'hdruk.metadata.remove'])->delete();

        EmailTemplate::where(['identifier' => 'hdruk.dar.assign'])->delete();
        EmailTemplate::where(['identifier' => 'hdruk.dar.remove'])->delete();

        EmailTemplate::where(['identifier' => 'hdruk.custodian.assign'])->delete();
        EmailTemplate::where(['identifier' => 'hdruk.custodian.remove'])->delete();

        $checkEmailUpdate = EmailTemplate::where([
            'identifier' => 'update.roles.team.user',
        ])->first();

        if (is_null($checkEmailUpdate)) {
            EmailTemplate::create([
                'identifier' => 'update.roles.team.user',
                'subject' => 'Congratulations! Your permissions have changed.',
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
                    </mjml>',
                'buttons' => '
                    {
                        "replacements": [
                            {
                                "placeholder": "[[BUTTON_1_URL]]",
                                "actual": "config(\'gateway.gateway_url\')/en/account/team/[[TEAM_ID]]/team-management"
                            }
                        ]
                    }
                '
            ]);

            $this->info('Email template "update.roles.team.user" has been added successfully!');
        } else {
            $this->warn('The email template "update.roles.team.user" already exists!');
        }

        $checkEmailAdd = EmailTemplate::where([
            'identifier' => 'add.new.user.team',
        ])->first();

        if (is_null($checkEmailAdd)) {
            EmailTemplate::create([
                'identifier' => 'add.new.user.team',
                'subject' => 'Congratulations! Your have been added to a team.',
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
                    </mjml>',
                'buttons' => '
                    {
                        "replacements": [
                            {
                                "placeholder": "[[BUTTON_1_URL]]",
                                "actual": "config(\'gateway.gateway_url\')/en/account/team/[[TEAM_ID]]/team-management"
                            }
                        ]
                    }
                '
            ]);

            $this->info('Email template "add.new.user.team" has been added successfully');
        } else {
            $this->warn('The email template "add.new.user.team" already exists!');
        }

        $this->info('The command has been executed successfully!');
    }
}
