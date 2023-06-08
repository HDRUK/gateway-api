<?php

namespace App\Console\Commands;

use Exception;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;

class MauroConduit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mauro-conduit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interface between Gateway API and Mauro data mapper instance';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Mauro::createFolder('Loki', 'Loki testing folder creation');
    }
}
