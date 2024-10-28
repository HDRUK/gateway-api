<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class CohortRequestEmailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // cohort.request.admin.approve
        EmailTemplate::create([
            'identifier' => 'cohort.request.admin.approve',
            'subject' => 'You have been assigned the role of Cohort Discovery admin on the Gateway',
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
                </mjml>',
        ]);

        // cohort.request.admin.remove
        EmailTemplate::create([
            'identifier' => 'cohort.request.admin.remove',
            'subject' => 'Your Cohort Discovery admin permissions has been removed',
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
                </mjml>',
        ]);
    }
}