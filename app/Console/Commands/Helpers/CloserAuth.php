<?php

namespace App\Console\Commands\Helpers;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CloserAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:closer-auth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Helper to check a CLOSER Discovery (Colectica) token can be created';

    /**
     * The token endpoint sits at the site root; data endpoints live under /api/v1.
     */
    private const TOKEN_PATH = '/token/createtoken';

    public function handle(): int
    {
        $config = config('services.closer');

        if (empty($config['username']) || empty($config['password'])) {
            $this->error('CLOSER_API_USERNAME and CLOSER_API_PASSWORD must be set.');

            return self::FAILURE;
        }

        $response = Http::acceptJson()->post(rtrim($config['base_url'], '/').self::TOKEN_PATH, [
            'username' => $config['username'],
            'password' => $config['password'],
        ]);

        // Never print the body of a successful response: it contains the token.
        $this->info('Status: '.$response->status());
        $this->info('Server: '.($response->header('server') ?: '-')
            .' | cf-mitigated: '.($response->header('cf-mitigated') ?: '-'));

        if ($response->successful()) {
            $this->info('Keys: '.implode(', ', array_keys($response->json() ?? [])));

            return self::SUCCESS;
        }

        $this->line(Str::limit(trim(strip_tags($response->body())), 300) ?: '(empty body)');

        return self::FAILURE;
    }
}
