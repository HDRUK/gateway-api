<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ConversionStatusCommand extends Command
{
    protected $signature = 'conversion:status';
    protected $description = 'Show the status of each conversion';

    public function handle()
    {
        $this->ensureConversionsTableExists();

        $executed = DB::table('conversions')->pluck('conversion')->toArray();
        $files = File::glob(app_path('Conversions') . '/*.php');

        $statuses = [];

        foreach ($files as $file) {
            $class = $this->getClassName($file);
            $status = in_array($class, $executed) ? 'Executed' : 'Pending';
            $statuses[] = ['Conversion' => $class, 'Status' => $status];
        }

        $this->table(['Conversion', 'Status'], $statuses);
    }

    protected function ensureConversionsTableExists()
    {
        if (!DB::schema()->hasTable('conversions')) {
            $this->error('Conversions table does not exist. Run "php artisan conversion" first.');
            exit;
        }
    }

    protected function getClassName($filePath)
    {
        $content = file_get_contents($filePath);

        $namespace = Str::between($content, 'namespace ', ';');
        $class = Str::between($content, 'class ', ' ');

        return "{$namespace}\\{$class}";
    }
}
