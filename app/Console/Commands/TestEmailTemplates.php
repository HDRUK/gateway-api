<?php

namespace App\Console\Commands;

use DB;
use Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Console\Command;

class TestEmailTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email-templates';

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
        $this->info('Running command...');

        $emailTemplates = DB::select('SELECT * FROM email_templates');

        foreach ($emailTemplates as $emailTemplate) {
            $mjmlTest = Config::get('services.mjml.render_url');
            $response = Http::post($mjmlTest, [
                'mjml' => $emailTemplate->body,
            ]);

            if ($response->successful()) {
                $this->info(PHP_EOL . 'MJML to HTML conversion for identifier "' . $emailTemplate->identifier . '" - success' . PHP_EOL);
            } else {
                $this->error(PHP_EOL . 'MJML to HTML conversion for identifier "' . $emailTemplate->identifier . '" - failed');

                $body = json_decode($response->body(), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $pretty = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                    $this->line('<fg=red>'.$pretty.'</>');
                } else {
                    $this->error($response->body());
                }
            }
        }

        $this->info('done!');
    }
}
