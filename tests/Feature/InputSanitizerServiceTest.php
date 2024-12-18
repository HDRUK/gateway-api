<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\InputSanitizer;

class InputSanitizerServiceTest extends TestCase
{
    /**
     * Test that the InputSanitizer service decodes HTML entities and sanitizes input.
     *
     * @return void
     */
    public function test_sanitize_input_service()
    {

        $sanitizer = new InputSanitizer();
        
        $input = "<script>alert('XSS Attack')</script>Jamie B Tester&#039;s";
        
        $sanitized_input = $sanitizer->sanitizeInput($input);
        $this->assertStringNotContainsString('<script>', $sanitized_input);
        $this->assertStringContainsString("Jamie's", $sanitized_input);
        $this->assertEquals("Jamie B Tester's", $sanitized_input);
    }
}
