<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use Illuminate\Console\Command;

class UpdateEmailCohortButtonsGat6183 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-email-cohort-buttons-gat6183';

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
        // cohort.discovery.access.rejected
        EmailTemplate::where('identifier', 'cohort.discovery.access.rejected')->update([
            'identifier' => 'cohort.discovery.access.rejected',
            'subject' => 'Your Cohort Discovery Registration has been Rejected.',
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
                                Your Cohort Discovery Registration has been Rejected.
                            </mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column width="100%">
                                <mj-text line-height="20px">
                                    Dear [[USER_FIRSTNAME]],
                                </mj-text>
                                <mj-text line-height="30px">
                                    Thank you for your patience whilst we reviewed your request to use Cohort Discovery.<br/>Unfortunately, your registration for access has been unsuccessful at this time.<br/>Your request does not meet our access criteria, which includes the following:
                                    <ul>
                                        <li>Registering a user profile on the Gateway.</li>
                                        <li>Your Gateway profile must include an organisational or institutional email address (either as primary or secondary emails).</li>
                                        <li>Please also fill in as much information in your Gateway profile as you can to help establish yourself as a bona fide researcher, NHS analyst or equivalent, such as your organisation or institutional name, a short bio, your domain of work and any links to social media (LinkedIn, ResearchGate etc.) or ORCID.</li>
                                    </ul>                    
                                    This information is required to help establish your status as a &lsquo;Safe Person&rsquo; under the Five Safes principles.<br/>If you have any questions on the above decision, please raise a support ticket on the Health Data Research Gateway.
                                </mj-text>
                                <mj-text>
                                <mj-text>
                                    Regards,<br/>
                                    Gateway Cohort Discovery Administrator
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
        ]);
    }
}
