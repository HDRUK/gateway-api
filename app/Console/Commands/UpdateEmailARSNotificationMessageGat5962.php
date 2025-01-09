<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use Illuminate\Console\Command;

class UpdateEmailARSNotificationMessageGat5962 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-email-a-r-s-notification-message-gat5962';

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
        EmailTemplate::where(['identifier' => 'dar.notifymessage'])
            ->update([
                'identifier' => 'dar.notifymessage',
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
}
