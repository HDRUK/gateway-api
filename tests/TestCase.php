<?php

namespace Tests;

use Closure;
use Tests\Traits\RefreshDatabaseLite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Queue;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabaseLite;

    protected bool $shouldFakeQueue = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disableObservers();

        $this->liteSetUp();

        $this->enableObservers();

        if ($this->shouldFakeQueue) {
            Queue::fake();
        }
    }

    protected function disableMiddleware(): void
    {
        $this->withoutMiddleware();
    }

    protected function enableMiddleware(): void
    {
        $this->withMiddleware();
    }

    protected function disableObservers()
    {
        Model::unsetEventDispatcher();
    }

    protected function enableObservers()
    {
        Model::setEventDispatcher(app('events'));
    }

    protected function withTemporaryObservers(Closure $callback)
    {
        $this->enableObservers();

        try {
            return $callback();
        } finally {
            $this->disableObservers();
        }
    }
}
