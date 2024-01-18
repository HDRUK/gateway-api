<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\EnquiryMessages;
use App\Models\EnquiryThread;

use App\Models\Team;

use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;

use Webklex\PHPIMAP\ClientManager;
use App\Http\Traits\TeamTransformation;

use EmailScanningService AS ESS;


class EmailScanningService extends Command
{
    use TeamTransformation;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:email-scanning-service';

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
        $messages = ESS::getNewNessages();
        $this->info($messages);
        
    }


}