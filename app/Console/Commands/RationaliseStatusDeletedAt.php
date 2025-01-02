<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Collection;
use Illuminate\Console\Command;

class RationaliseStatusDeletedAt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:rationalise-status-deleted-at';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rationalise entity status and deleted at columns';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $collections = Collection::withTrashed()->where('status', Collection::STATUS_ARCHIVED)->get();
            foreach ($collections as $collection) {
                $collection->restore();
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
}
