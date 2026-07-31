<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Team;
use App\Models\Widget;
use App\Models\WidgetAnalytic;
use App\Jobs\WidgetAnalyticsJob;
use Illuminate\Support\Facades\Queue;
use Tests\Traits\MockExternalApis;

class TeamWidgetTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];
    protected Team $team;

    public function setUp(): void
    {
        $this->commonSetUp();

        Widget::flushEventListeners();

        $this->team = Team::first();
    }

    public function test_can_list_widgets_for_a_team(): void
    {
        $widget = Widget::factory()->create(['team_id' => $this->team->id]);

        $response = $this->get("api/v1/teams/{$this->team->id}/widgets", $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    0 => [
                        'id',
                        'widget_name',
                        'size_width',
                        'size_height',
                        'updated_at',
                        'unit',
                        'team_id',
                        'team_name',
                        'include_search_bar',
                        'branding_primary',
                        'branding_secondary',
                        'branding_neutral',
                    ],
                ],
            ]);

        $widget->forceDelete();
    }

    public function test_can_retrieve_a_single_widget(): void
    {
        $widget = Widget::factory()->create([
            'team_id'     => $this->team->id,
            'widget_name' => 'Retrieve Test Widget',
        ]);

        $response = $this->get("api/v1/teams/{$this->team->id}/widgets/{$widget->id}", $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'widget_name',
                    'team_id',
                    'included_datasets',
                    'included_data_uses',
                    'included_scripts',
                    'included_collections',
                    'permitted_domains',
                ],
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals('Retrieve Test Widget', $content['data']['widget_name']);

        $widget->forceDelete();
    }

    public function test_retrieve_returns_404_for_unknown_widget(): void
    {
        $response = $this->get("api/v1/teams/{$this->team->id}/widgets/999999", $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    public function test_can_create_a_widget(): void
    {
        Queue::fake();

        $response = $this->json(
            'POST',
            "api/v1/teams/{$this->team->id}/widgets",
            [
                'widget_name'        => 'New Test Widget',
                'size_width'         => 800,
                'size_height'        => 600,
                'unit'               => 'px',
                'include_search_bar' => true,
                'include_cohort_link' => false,
                'keep_proportions'   => true,
                'permitted_domains'  => ['example.com', 'example.org'],
                'branding_primary'   => '#ff0000',
                'branding_secondary' => '#00ff00',
                'branding_neutral'   => '#0000ff',
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure(['message', 'data']);

        $widgetId = $response->decodeResponseJson()['data'];

        Queue::assertPushed(WidgetAnalyticsJob::class, function ($job) {
            $data = $this->getJobData($job);
            return $data['event_type'] === WidgetAnalytic::EVENT_WIDGET_CREATED;
        });

        Widget::find($widgetId)?->forceDelete();
    }

    public function test_create_widget_requires_widget_name(): void
    {
        $response = $this->json(
            'POST',
            "api/v1/teams/{$this->team->id}/widgets",
            ['size_width' => 800],
            $this->header
        );

        $response->assertStatus(422);
    }

    public function test_can_update_a_widget(): void
    {
        $widget = Widget::factory()->create(['team_id' => $this->team->id]);

        $response = $this->json(
            'PATCH',
            "api/v1/teams/{$this->team->id}/widgets/{$widget->id}",
            ['widget_name' => 'Updated Widget Name', 'size_width' => 1024],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure(['message', 'data']);

        $content = $response->decodeResponseJson();
        $this->assertEquals('Updated Widget Name', $content['data']['widget_name']);
        $this->assertEquals(1024, $content['data']['size_width']);

        $widget->forceDelete();
    }

    public function test_update_returns_404_for_unknown_widget(): void
    {
        $response = $this->json(
            'PATCH',
            "api/v1/teams/{$this->team->id}/widgets/999999",
            ['widget_name' => 'Ghost'],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    public function test_can_delete_a_widget(): void
    {
        $widget = Widget::factory()->create(['team_id' => $this->team->id]);

        $response = $this->json(
            'DELETE',
            "api/v1/teams/{$this->team->id}/widgets/{$widget->id}",
            [],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure(['message']);

        $this->assertSoftDeleted('widgets', ['id' => $widget->id]);
    }

    public function test_delete_returns_404_for_unknown_widget(): void
    {
        $response = $this->json(
            'DELETE',
            "api/v1/teams/{$this->team->id}/widgets/999999",
            [],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    public function test_track_records_valid_frontend_event(): void
    {
        Queue::fake();

        $widget = Widget::factory()->create(['team_id' => $this->team->id]);

        $response = $this->json(
            'POST',
            "api/v1/teams/{$this->team->id}/widgets/{$widget->id}/track",
            [
                'event_type'    => WidgetAnalytic::EVENT_GATEWAY_CLICK,
                'entity_id'     => 42,
                'entity_type'   => 'dataset',
                'source_domain' => 'partner-site.org',
            ]
        );

        $response->assertStatus(204);

        Queue::assertPushed(WidgetAnalyticsJob::class, function ($job) use ($widget) {
            $data = $this->getJobData($job);
            return $data['event_type'] === WidgetAnalytic::EVENT_GATEWAY_CLICK
                && $data['widget_id'] === $widget->id
                && $data['entity_id'] === 42
                && $data['entity_type'] === 'dataset'
                && $data['source_domain'] === 'partner-site.org';
        });

        $widget->forceDelete();
    }

    public function test_track_rejects_invalid_event_type(): void
    {
        $widget = Widget::factory()->create(['team_id' => $this->team->id]);

        $response = $this->json(
            'POST',
            "api/v1/teams/{$this->team->id}/widgets/{$widget->id}/track",
            ['event_type' => 'not_a_real_event']
        );

        $response->assertStatus(422);

        $widget->forceDelete();
    }

    public function test_track_rejects_backend_only_event_types(): void
    {
        $widget = Widget::factory()->create(['team_id' => $this->team->id]);

        // widget_created and widget_load are backend-only; the frontend must not be able to fake them
        foreach ([WidgetAnalytic::EVENT_WIDGET_CREATED, WidgetAnalytic::EVENT_WIDGET_LOAD] as $event) {
            $response = $this->json(
                'POST',
                "api/v1/teams/{$this->team->id}/widgets/{$widget->id}/track",
                ['event_type' => $event]
            );

            $response->assertStatus(422);
        }

        $widget->forceDelete();
    }

    public function test_track_returns_404_for_unknown_widget(): void
    {
        $response = $this->json(
            'POST',
            "api/v1/teams/{$this->team->id}/widgets/999999/track",
            ['event_type' => WidgetAnalytic::EVENT_PAGE_VIEW]
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    public function test_analytics_returns_aggregated_data(): void
    {
        $widget = Widget::factory()->create(['team_id' => $this->team->id]);

        WidgetAnalytic::insert([
            ['widget_id' => $widget->id, 'team_id' => $this->team->id, 'event_type' => WidgetAnalytic::EVENT_WIDGET_LOAD, 'entity_id' => null, 'entity_type' => null, 'source_domain' => 'example.com', 'created_at' => now()->subDays(2)],
            ['widget_id' => $widget->id, 'team_id' => $this->team->id, 'event_type' => WidgetAnalytic::EVENT_WIDGET_LOAD, 'entity_id' => null, 'entity_type' => null, 'source_domain' => 'example.com', 'created_at' => now()->subDays(1)],
            ['widget_id' => $widget->id, 'team_id' => $this->team->id, 'event_type' => WidgetAnalytic::EVENT_GATEWAY_CLICK, 'entity_id' => 1, 'entity_type' => 'dataset', 'source_domain' => 'example.com', 'created_at' => now()],
        ]);

        $response = $this->get("api/v1/teams/{$this->team->id}/widgets/analytics", $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'by_event',
                    'by_widget',
                    'over_time',
                ],
            ]);

        $content = $response->decodeResponseJson();
        $byEvent = collect($content['data']['by_event'])->keyBy('event_type');
        $this->assertGreaterThanOrEqual(2, $byEvent[WidgetAnalytic::EVENT_WIDGET_LOAD]['count']);
        $this->assertGreaterThanOrEqual(1, $byEvent[WidgetAnalytic::EVENT_GATEWAY_CLICK]['count']);

        WidgetAnalytic::where('widget_id', $widget->id)->delete();
        $widget->forceDelete();
    }

    public function test_analytics_respects_date_range_filter(): void
    {
        $widget = Widget::factory()->create(['team_id' => $this->team->id]);

        WidgetAnalytic::insert([
            ['widget_id' => $widget->id, 'team_id' => $this->team->id, 'event_type' => WidgetAnalytic::EVENT_WIDGET_LOAD, 'entity_id' => null, 'entity_type' => null, 'source_domain' => null, 'created_at' => now()->subDays(60)],
            ['widget_id' => $widget->id, 'team_id' => $this->team->id, 'event_type' => WidgetAnalytic::EVENT_WIDGET_LOAD, 'entity_id' => null, 'entity_type' => null, 'source_domain' => null, 'created_at' => now()->subDays(5)],
        ]);

        $from = now()->subDays(10)->format('Y-m-d');
        $to   = now()->format('Y-m-d');

        $response = $this->get(
            "api/v1/teams/{$this->team->id}/widgets/analytics?from={$from}&to={$to}",
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $content  = $response->decodeResponseJson();
        $byEvent  = collect($content['data']['by_event'])->keyBy('event_type');
        // Only the event within the date window should appear
        $this->assertEquals(1, $byEvent[WidgetAnalytic::EVENT_WIDGET_LOAD]['count'] ?? 0);

        WidgetAnalytic::where('widget_id', $widget->id)->delete();
        $widget->forceDelete();
    }

    public function test_analytics_returns_400_with_errors_on_invalid_params(): void
    {
        $response = $this->get(
            "api/v1/teams/{$this->team->id}/widgets/analytics?from=not-a-date&group_by=decade",
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'))
            ->assertJsonStructure(['message', 'errors']);

        $errors = $response->decodeResponseJson()['errors'];
        $this->assertArrayHasKey('from', $errors);
        $this->assertArrayHasKey('group_by', $errors);
    }

    public function test_analytics_supports_monthly_grouping(): void
    {
        $widget = Widget::factory()->create(['team_id' => $this->team->id]);

        WidgetAnalytic::insert([
            // subMonths() overflows on the 29th-31st (e.g. Jul 31 - 1 month = "Jun 31" -> rolls
            // forward to Jul 1, still July), which silently collapsed both rows into one period.
            // subMonthsNoOverflow() clamps to the last valid day instead, so this actually lands
            // in the previous month every time.
            ['widget_id' => $widget->id, 'team_id' => $this->team->id, 'event_type' => WidgetAnalytic::EVENT_PAGE_VIEW, 'entity_id' => null, 'entity_type' => null, 'source_domain' => null, 'created_at' => now()->subMonthsNoOverflow(1)],
            ['widget_id' => $widget->id, 'team_id' => $this->team->id, 'event_type' => WidgetAnalytic::EVENT_PAGE_VIEW, 'entity_id' => null, 'entity_type' => null, 'source_domain' => null, 'created_at' => now()],
        ]);

        $response = $this->get(
            "api/v1/teams/{$this->team->id}/widgets/analytics?group_by=month",
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $content  = $response->decodeResponseJson();
        $periods  = collect($content['data']['over_time'])->pluck('period')->unique()->values()->all();
        $this->assertGreaterThanOrEqual(2, count($periods));

        WidgetAnalytic::where('widget_id', $widget->id)->delete();
        $widget->forceDelete();
    }

    // Extracts the protected $data property from a queued job for assertions
    private function getJobData(WidgetAnalyticsJob $job): array
    {
        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('data');
        $property->setAccessible(true);
        return $property->getValue($job);
    }
}
