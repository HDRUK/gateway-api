<?php

namespace Tests\Feature;

use Tests\TestCase;

class InputSanitizerTest extends TestCase
{
    /**
     * Test that the formatCleanInput helper function decodes HTML entities and sanitizes input.
     *
     * @return void
     */
    public function test_format_clean_input()
    {
        $input = "<script>alert('XSS Attack')</script>Jamie B Tester&#039;s";

        $sanitized_input = formatCleanInput($input);

        $this->assertStringNotContainsString('<script>', $sanitized_input);
        $this->assertStringContainsString("Jamie", $sanitized_input);
        $this->assertEquals("Jamie B Tester's", $sanitized_input);
    }
}
