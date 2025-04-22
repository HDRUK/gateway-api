<?php

namespace Tests;

use Tests\Traits\MockExternalApis;
use Tests\Traits\RunMigrationOnce;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RunMigrationOnce;
    use MockExternalApis;

    protected $baseUser = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->runMigrationsOnce();
        if (!static::$migrated) {
            $this->commonSetUp();
        }

        var_dump(static::$migrated);
    }
}
