<?php

namespace App\Behat\Context;

use Behat\Behat\Context\Context;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;

use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    
    use InteractsWithDatabase;

    private $sharedContext;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $this->sharedContext = new SharedContext();
    }

    /**
     * @BeforeSuite
     */
    public static function before(BeforeSuiteScope $scope)
    {
        $behatMigrateAndSeed = env('BEHAT_MIGRATE_AND_SEED', false);

        echo "Run a new suite ...\n";
        echo "Initializing test environment...\n";
        echo "Reset shared context ...\n";
        SharedContext::reset();

        if ((bool) $behatMigrateAndSeed) {
            echo "Setting up database...\n";
            Artisan::call('migrate:fresh');
            echo Artisan::output();
            Artisan::call('db:seed');
            echo Artisan::output();
        }

        // empty 'logs/email.log'
        File::put(storage_path('logs/email.log'), '');
    }

    // /**
    //  * @BeforeFeature
    //  */
    // public static function beforeFeature(BeforeFeatureScope $scope)
    // {
    //     echo "here something can happen before the execution of each feature ... but nothing yet\n";
    // }

    public function getSharedContext()
    {
        return $this->sharedContext;
    }
}
