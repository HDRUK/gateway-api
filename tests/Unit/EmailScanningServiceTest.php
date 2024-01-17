<?php

namespace Tests\Unit;

use App\Mail\Email;
use App\Models\EmailTemplate;

use Tests\TestCase;
use Database\Seeders\EmailTemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

use App\Console\Commands\EmailScanningService;
use Illuminate\Support\Facades\Artisan;

class EmailScanningServiceTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            EmailTemplatesSeeder::class,
        ]);
    }

    public function test_it_can_run(): void
    {
          $this->mock(EmailScanningService::class, function ($mock) {
            // You can set expectations on the methods that the command should call
            //$mock->shouldReceive('argument')->once()->andReturn('mocked_argument');
            //$mock->shouldReceive('option')->once()->with('your_option')->andReturn('mocked_option');
            // You can also set expectations on any other method calls or behavior

            // For example, if your command interacts with the user, you can mock the user input/output
            //$mock->shouldReceive('ask')->once()->andReturn('mocked_input');
        });

        // Running the command
        Artisan::call('app:email-scanning-service');

         $this->assertEquals('Expected output', Artisan::output());

    }


}

?>
