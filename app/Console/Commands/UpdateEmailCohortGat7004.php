<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use Illuminate\Console\Command;

class UpdateEmailCohortGat7004 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-email-cohort-gat7004';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cohort Discovery update email will.expire template to remove renewal button';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // cohort.discovery.access.will.expire
        EmailTemplate::where('identifier', 'cohort.discovery.access.will.expire')->update([
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
                                Your Cohort Discovery access will soon expire.
                            </mj-text>
                            </mj-column>
                        </mj-section>

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
                </mjml>
            ',
            'buttons' => '
                {
                    "replacements": [
                        {
                            "placeholder": "[[COHORT_DISCOVERY_RENEW_URL]]",
                            "actual": "env(gateway.gateway_url)/en/about/cohort-discovery"
                        }
                    ]
                }
            ',
        ]);
    }
}
