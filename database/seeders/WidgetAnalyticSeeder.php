<?php

namespace Database\Seeders;

use App\Models\Widget;
use App\Models\WidgetAnalytic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WidgetAnalyticSeeder extends Seeder
{
    // Approximate event distribution for realistic demo data
    private array $eventWeights = [
        WidgetAnalytic::EVENT_WIDGET_LOAD    => 40,
        WidgetAnalytic::EVENT_PAGE_VIEW      => 20,
        WidgetAnalytic::EVENT_GATEWAY_CLICK  => 20,
        WidgetAnalytic::EVENT_SEARCH         => 10,
        WidgetAnalytic::EVENT_CODE_COPIED    => 5,
        WidgetAnalytic::EVENT_WIDGET_CREATED => 5,
    ];

    private array $sampleDomains = [
        'partner-health.org',
        'university-research.ac.uk',
        'nhs-data-portal.nhs.uk',
        'genomics-england.co.uk',
        'clinical-trials.net',
    ];

    private array $entityTypes = ['dataset', 'tool', 'collection', 'dur'];

    public function run(): void
    {
        $widgets = Widget::all();

        if ($widgets->isEmpty()) {
            $this->command->warn('No widgets found — run WidgetSeeder first.');
            return;
        }

        $weightedEvents = $this->buildWeightedEventPool();
        $rows = [];

        foreach ($widgets as $widget) {
            // Spread ~90 days of history, more recent days get more events
            for ($daysAgo = 90; $daysAgo >= 0; $daysAgo--) {
                $dailyVolume = (int) round(fake()->numberBetween(0, 8) * (1 + (90 - $daysAgo) / 90));

                for ($i = 0; $i < $dailyVolume; $i++) {
                    $eventType  = $weightedEvents[array_rand($weightedEvents)];
                    $createdAt  = now()->subDays($daysAgo)->subSeconds(fake()->numberBetween(0, 86399));

                    $row = [
                        'widget_id'     => $widget->id,
                        'team_id'       => $widget->team_id,
                        'event_type'    => $eventType,
                        'entity_id'     => null,
                        'entity_type'   => null,
                        'source_domain' => null,
                        'created_at'    => $createdAt,
                    ];

                    if (in_array($eventType, [WidgetAnalytic::EVENT_WIDGET_LOAD, WidgetAnalytic::EVENT_GATEWAY_CLICK, WidgetAnalytic::EVENT_SEARCH])) {
                        $row['source_domain'] = fake()->randomElement($this->sampleDomains);
                    }

                    if ($eventType === WidgetAnalytic::EVENT_GATEWAY_CLICK) {
                        $row['entity_type'] = fake()->randomElement($this->entityTypes);
                        $row['entity_id']   = fake()->numberBetween(1, 100);
                    }

                    $rows[] = $row;

                    if (count($rows) >= 500) {
                        DB::table('widget_analytics')->insert($rows);
                        $rows = [];
                    }
                }
            }
        }

        if (!empty($rows)) {
            DB::table('widget_analytics')->insert($rows);
        }
    }

    private function buildWeightedEventPool(): array
    {
        $pool = [];
        foreach ($this->eventWeights as $event => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $pool[] = $event;
            }
        }
        return $pool;
    }
}
