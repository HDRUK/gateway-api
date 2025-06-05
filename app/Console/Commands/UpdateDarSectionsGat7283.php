<?php

namespace App\Console\Commands;

use App\Models\DataAccessSection;
use Illuminate\Console\Command;

class UpdateDarSectionsGat7283 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-dar-sections-gat7283';

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
        $arraySectionTitles = [
            'Other individuals',
            'Funder information',
            'Sponsor information',
            'Declaration of interest'
        ];

        $sections = DataAccessSection::select(['id', 'name', 'is_array_section'])->get();

        foreach ($sections as $section) {
            if (in_array($section->name, $arraySectionTitles)) {
                $section->is_array_section = true;
                $section->save();
            }
        }
    }
}
