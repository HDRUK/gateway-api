<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function tearDown(): void
    {
        parent::tearDown();
        echo memory_get_usage() . PHP_EOL;
    }
}
