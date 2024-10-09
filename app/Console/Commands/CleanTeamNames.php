<?php

namespace App\Console\Commands;

use App\Models\Team;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use Illuminate\Console\Command;
use App\Http\Traits\IndexElastic;

class CleanTeamNames extends Command
{
    use IndexElastic;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-team-names {reindex?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CLI command to change all team names to Title Case, with exceptions made for specified acronyms, and clean up encoded characters.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reindex = $this->argument('reindex');
        $reindexEnabled = $reindex !== null;

        $teams = Team::all();

        $progressbar = $this->output->createProgressBar(count($teams));
        $progressbar->start();

        foreach ($teams as $team) {
            $team->name = $this->applyAcronyms(ucwords(strtolower($team->name)));
            $team->save();

            $publisher = [
                'gatewayId' => $team->pid,
                'name' => $team->name,
            ];

            $datasets = Dataset::where('team_id', $team->id)->get();

            foreach ($datasets as $d) {
                $latestVersion = $d->latestVersion();
                $datasetVersionId = $latestVersion->id;
                $metadata = $latestVersion->metadata;
                $metadata['metadata']['summary']['publisher'] = $publisher;

                DatasetVersion::findOrFail($datasetVersionId)->update([
                    "metadata" => $metadata
                ]);

            }

            if ($reindexEnabled) {
                $this->reindexElasticDataProvider($team->id);
                sleep(1);
            }

            $progressbar->advance();
        }

        $progressbar->finish();

        echo 'completed cleaning of team names';
    }

    private $acronyms = [
        'Bhf' => 'BHF',
        'Bioresource' => 'BioResource',
        'Breathe' => 'BREATHE',
        'Cogstack' => 'CogStack',
        'Covid-19' => 'COVID-19',
        'Cprd' => 'CPRD',
        '(dash)' => '(DaSH)',
        'Data-can' => 'DATA-CAN',
        'Dataloch' => 'DataLoch',
        'Datamind' => 'DATAMIND',
        'Discover-now' => 'Discover-NOW',
        'Dpuk' => 'DPUK',
        'Elixir' => 'eLIXIR',
        'Enewborn' => 'eNewborn',
        'Epic-oxford' => 'EPIC-Oxford',
        'Godarts' => 'GoDARTS',
        'Gstt' => 'GSTT',
        'Hdr' => 'HDR',
        'Hqip' => 'HQIP',
        'Insight' => 'INSIGHT',
        '(icnarc)' => '(ICNARC)',
        '(icoda)' => '(ICODA)',
        'Isaric' => 'ISARIC',
        'Kms' => 'KMS',
        'Llc' => 'LLC',
        'Llc)' => 'LLC)',
        'Mireda' => 'MIREDA',
        'Mrc' => 'MRC',
        'Nihr' => 'NIHR',
        'Nhs' => 'NHS',
        'Nhsx' => 'NHSX',
        'Now' => 'NOW',
        '(nshd)' => '(NSHD)',
        'Of' => 'of',
        'Pathlake' => 'PathLAKE',
        'Pioneer' => 'PIONEER',
        'Qresearch' => 'QResearch',
        'Sail' => 'SAIL',
        'Sde' => 'SDE',
        'Slam' => 'SLaM',
        'Ucl' => 'UCL',
        'Ucleb' => 'UCLEB',
        'Uk' => 'UK',
        '(uk' => '(UK',
        '4c' => '4C',
        '&amp;' => '&',
        'King&rsquo;s' => 'King\'s',
        '&ndash;' => '-'
    ];

    private function applyAcronyms(string $str): string
    {
        // Split string into words
        $words = explode(' ', $str);

        // Loop over each word. If it matches a known acronym, apply the correct formatting.
        $convertedWords = [];
        foreach ($words as $word) {
            if (array_key_exists($word, $this->acronyms)) {
                $convertedWords[] = $this->acronyms[$word];
            } else {
                $convertedWords[] = $word;
            }
        }
        // Rejoin all words
        $result = implode(' ', $convertedWords);

        return $result;
    }
}
