<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Laravel\Pennant\Feature;

class FeatureSet extends Command
{
    protected $signature = 'feature:set
        {name : The feature flag name}
        {--user= : Apply to a specific user id}
        {--on : Activate the flag}
        {--off : Deactivate the flag}
        {--forget : Remove the scoped override (falls back to global)}';

    protected $description = 'Activate/deactivate a Pennant feature globally or for a specific user';

    public function handle(): int
    {
        $name = (string) $this->argument('name');

        $userId = $this->option('user');

        $on = (bool) $this->option('on');
        $off = (bool) $this->option('off');
        $forget = (bool) $this->option('forget');

        if (count(array_filter([$on, $off, $forget])) !== 1) {
            $this->error('Choose exactly one of: --on, --off, --forget');

            return self::FAILURE;
        }

        $store = Feature::for(null);
        $scopeLabel = 'global';
        if ($userId) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("User {$userId} not found.");

                return self::FAILURE;
            }
            $store = Feature::for($user);
            $scopeLabel = "user {$userId}";
        }

        if ($forget) {
            $store->forget($name);
            Feature::flushCache();
            $this->info("Removed override: {$name} for {$scopeLabel}");

            return self::SUCCESS;
        }

        $on ? $store->activate($name) : $store->deactivate($name);
        Feature::flushCache();

        $state = $store->active($name) ? 'ON' : 'OFF';
        $this->info("{$name} is now {$state} for {$scopeLabel}");

        return self::SUCCESS;
    }
}
