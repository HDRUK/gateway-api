<?php

namespace Tests\Unit\Middleware;

use ReflectionClass;
use Tests\TestCase;
use App\Http\Middleware\LogRequestResponse;

class LogRequestResponseTest extends TestCase
{
    private function maskSensitive(array $data)
    {
        $middleware = new LogRequestResponse();
        $method = (new ReflectionClass($middleware))->getMethod('maskSensitive');
        $method->setAccessible(true);

        return $method->invoke($middleware, $data);
    }

    public function test_masks_sensitive_field_at_top_level(): void
    {
        $result = $this->maskSensitive(['password' => 'secret123', 'email' => 'user@example.com']);

        $this->assertEquals('***', $result['password']);
        $this->assertEquals('user@example.com', $result['email']);
    }

    public function test_masks_sensitive_field_nested_within_bounded_depth(): void
    {
        $result = $this->maskSensitive([
            'data' => ['user' => ['token' => 'abc123', 'name' => 'Jane Doe']],
        ]);

        $this->assertEquals('***', $result['data']['user']['token']);
        $this->assertEquals('Jane Doe', $result['data']['user']['name']);
    }

    public function test_does_not_mask_sensitive_key_beyond_max_depth(): void
    {
        // Documented limitation: sensitive keys nested deeper than maxMaskDepth
        // (4) are left as-is. This trades a theoretical deep-nesting exposure
        // for bounded cost on huge domain payloads (dataset metadata etc.),
        // where sensitive keys never realistically appear this deep.
        $result = $this->maskSensitive([
            'a' => ['b' => ['c' => ['d' => ['password' => 'still-here']]]],
        ]);

        $this->assertEquals('still-here', $result['a']['b']['c']['d']['password']);
    }

    public function test_leaves_non_sensitive_data_untouched(): void
    {
        $data = ['name' => 'Jane Doe', 'nested' => ['count' => 5, 'items' => ['a', 'b']]];

        $this->assertEquals($data, $this->maskSensitive($data));
    }
}
