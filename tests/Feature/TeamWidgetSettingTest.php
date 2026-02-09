<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Team;
use App\Models\WidgetSetting;
use Tests\Traits\MockExternalApis;

class TeamWidgetSettingTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];
    protected $metadata;

    public function setUp(): void
    {
        $this->commonSetUp();

        Team::flushEventListeners();
    }

    public function test_the_application_cannot_list_widget_settings()
    {
        $latestTeam = Team::query()->orderBy('id', 'desc')->first();
        $teamIdTest = $latestTeam ? $latestTeam->id + 1 : 1;

        $response = $this->get("api/v1/teams/{$teamIdTest}/widget_settings", $this->header);

        $response->assertStatus(400);
        $message = $response->decodeResponseJson()['message'];
        $this->assertEquals('Invalid argument(s)', $message);
    }

    public function test_the_application_can_list_widget_settings()
    {
        $randomTeam = Team::inRandomOrder()->first();
        $teamId = $randomTeam->id;

        WidgetSetting::where('team_id', $teamId)->delete();
        WidgetSetting::create([
            'team_id' => $teamId,
            'colours' => [
                    'primary' => '#005EB8',
                    'seconday' => '#006747',
                    'neutral' => '#E8EDEE',
                ],
        ]);


        $response = $this->get("api/v1/teams/{$teamId}/widget_settings", $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'team_id',
                        'colours',
                        'team',
                    ],
                ],
            ]);
    }

    public function test_the_application_can_create_widget_settings()
    {
        $widgetSettingTeams = WidgetSetting::all()->pluck('team_id')->toArray();
        $randomTeam = Team::whereNotIn('id', $widgetSettingTeams)->inRandomOrder()->first();

        $response = $this->json(
            'POST',
            "api/v1/teams/{$randomTeam->id}/widget_settings",
            [
                'colours' => [
                    'primary' => '#005EB8',
                    'seconday' => '#006747',
                    'neutral' => '#E8EDEE',
                ],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        $responseGet = $this->get("api/v1/teams/{$randomTeam->id}/widget_settings", $this->header);

        $responseGet->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'team_id',
                        'colours',
                        'team',
                    ],
                ],
            ]);
    }

    public function test_the_application_cannot_create_widget_settings()
    {
        $latestTeam = Team::query()->orderBy('id', 'desc')->first();
        $teamIdTest = $latestTeam ? $latestTeam->id + 1 : 1;

        $response = $this->json(
            'POST',
            "api/v1/teams/{$teamIdTest}/widget_settings",
            [
                'colours' => [
                    'primary' => '#005EB8',
                    'seconday' => '#006747',
                    'neutral' => '#E8EDEE',
                ],
            ],
            $this->header
        );

        $response->assertStatus(400);
        $message = $response->decodeResponseJson()['message'];
        $this->assertEquals('Invalid argument(s)', $message);
    }

    public function test_the_application_can_delete_widget_settings()
    {
        $widgetSettingTeams = WidgetSetting::all()->pluck('team_id')->toArray();
        $randomTeam = Team::whereNotIn('id', $widgetSettingTeams)->inRandomOrder()->first();
        $teamId = $randomTeam->id;

        WidgetSetting::where('team_id', $teamId)->delete();


        $response = $this->json(
            'POST',
            "api/v1/teams/{$teamId}/widget_settings",
            [
                'colours' => [
                    'primary' => '#005EB8',
                    'seconday' => '#006747',
                    'neutral' => '#E8EDEE',
                ],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $content = $response->decodeResponseJson();
        $widgetSettingId = $content['data'];

        $responseGet = $this->get("api/v1/teams/{$teamId}/widget_settings", $this->header);

        $responseGet->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'team_id',
                        'colours',
                        'team',
                    ],
                ],
            ]);

        $responseDelete = $this->json(
            'DELETE',
            "api/v1/teams/{$teamId}/widget_settings/{$widgetSettingId}",
            [],
            $this->header
        );

        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
    }

    public function test_the_application_cannot_delete_widget_settings()
    {
        $widgetSettingTeams = WidgetSetting::all()->pluck('team_id')->toArray();
        $randomTeam = Team::whereNotIn('id', $widgetSettingTeams)->inRandomOrder()->first();
        $teamId = $randomTeam->id;

        $response = $this->json(
            'POST',
            "api/v1/teams/{$teamId}/widget_settings",
            [
                'colours' => [
                    'primary' => '#005EB8',
                    'seconday' => '#006747',
                    'neutral' => '#E8EDEE',
                ],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $content = $response->decodeResponseJson();
        $widgetSettingId = $content['data'];

        // invalid team
        $latestTeam = Team::query()->orderBy('id', 'desc')->first();
        $teamIdTest = $latestTeam ? $latestTeam->id + 1 : 1;

        $responseDelete = $this->json(
            'DELETE',
            "api/v1/teams/{$teamIdTest}/widget_settings/{$widgetSettingId}",
            [],
            $this->header
        );

        $responseDelete->assertStatus(400);
        $message = $responseDelete->decodeResponseJson()['message'];
        $this->assertEquals('Invalid argument(s)', $message);

        // invalid widget setting
        $latestWS = WidgetSetting::query()->orderBy('id', 'desc')->first();
        $wsIdTest = $latestWS ? $latestWS->id + 1 : 1;


        $responseDelete = $this->json(
            'DELETE',
            "api/v1/teams/{$teamId}/widget_settings/{$wsIdTest}",
            [],
            $this->header
        );

        $responseDelete->assertStatus(400);
        $message = $responseDelete->decodeResponseJson()['message'];
        $this->assertEquals('Invalid argument(s)', $message);

        // success
        $responseDelete = $this->json(
            'DELETE',
            "api/v1/teams/{$teamId}/widget_settings/{$widgetSettingId}",
            [],
            $this->header
        );

        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

    }

}
