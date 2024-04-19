<?php

use Tests\TestCase;

use App\Models\User;
use App\Models\Team;
use App\Models\Application;
use App\Models\TeamHasUser;
use App\Models\Permission;
use App\Models\ApplicationHasPermission;

use Database\Seeders\SectorSeeder;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

use Illuminate\Support\Str;

use Illuminate\Foundation\Testing\DatabaseMigrations;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

use Tests\Traits\MockExternalApis;

/**
 * When you have created your Feature (*.feature) file, complete with scenario(s), you can run
 * `vendor/bin/behat` within the root of your gateway-api-2 directory and behat will determine
 * unresolved/undefined steps. It will then ask how you wish to run them. Should you choose
 * "FeatureContext" - behat will output the missing step signatures for you to copy paste here.
 */

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends TestCase implements Context
{
    use DatabaseMigrations;
    use MockExternalApis;

    protected $user = null;
    protected $team = null;
    protected $app = null;

    const TEST_URL = '/api/v1/integrations/datasets';

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        parent::setUp();
    }

    /** @BeforeScenario */
    public function before(BeforeScenarioScope $scope)
    {
        $this->artisan('migrate:fresh');

        $this->seed([
            SectorSeeder::class,
        ]);
    }

    /**
     * @Given I am a registered user on the gateway
     */
    public function iAmARegisteredUserOnTheGateway()
    {
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test.user@test.com',
            'password' => 'Passw@rd1!',
            'sector_id' => 1,
            'contact_feedback' => 1,
            'contact_news' => 1,
            'organisation' => 'Test Organisation',
            'bio' => 'Test Biography',
            'domain' => 'https://testdomain.com',
            'link' => 'https://testlink.com/link',
            'orcid' => "https://orcid.org/12345678",
            'mongo_id' => 1234567,
            'mongo_object_id' => "12345abcde",              
        ]);
        $this->assertNotNull($this->user);
    }

    /**
     * @Given I am a member of a team
     */
    public function iAmAMemberOfATeam()
    {
        $this->team = Team::factory(1)->create([
            'name' => 'Test Team 123',
        ]);
        $this->assertNotNull($this->team);

        $teamHasUser = TeamHasUser::create([
            'team_id' => $this->team[0]->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertNotNull($teamHasUser);

        $this->assertDatabaseHas('team_has_users', [
            'team_id' => $this->team[0]->id,
            'user_id' => $this->user->id,
        ]);
    }    

    /**
     * @Then I can create an application to use automation services
     */
    public function iCanCreateAnApplicationToUseAutomationServices()
    {
        $this->app = Application::factory(1)->create([
            'user_id' => $this->user->id,
            'name' => 'My Application v1',
        ]);

        $this->assertNotNull($this->app[0]->app_id);
        $this->assertNotNull($this->app[0]->client_id);

        $perms = Permission::whereIn('name', [
            'datasets.create',
            'datasets.read',
            'datasets.update',
            'datasets.delete',
        ])->get();

        foreach ($perms as $perm) {
            ApplicationHasPermission::firstOrCreate([
                'application_id' => $this->app[0]->id,
                'permission_id' => $perm->id,
            ]);
        }

        foreach ($perms as $perm) {
            $this->assertDatabaseHas('application_has_permissions', [
                'application_id' => $this->app[0]->id,
                'permission_id' => $perm->id,
            ]);
        }
    }
}
