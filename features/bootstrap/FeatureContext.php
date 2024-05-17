<?php

namespace App\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use App\Behat\Context\SharedContext;
use Behat\Gherkin\Node\PyStringNode;
use Illuminate\Support\Facades\Artisan;
use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
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
        echo "Run a new suite ...\n";
        echo "Initializing test environment...\n";
        echo "Reset shared context ...\n";
        SharedContext::reset();

        echo "Setting up database...\n";
        Artisan::call('migrate:fresh');
        Artisan::call('db:seed');
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
