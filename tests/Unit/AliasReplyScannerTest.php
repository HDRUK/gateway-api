<?php

namespace Tests\Unit;

use App\Mail\Email;
use App\Models\EmailTemplate;
use App\Models\EnquiryThread;

use Tests\TestCase;
use Database\Seeders\EnquiryThreadSeeder;
use Database\Seeders\EnquiryMessageSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\TeamSeeder;
use Database\Seeders\TeamHasUserSeeder;
use Database\Seeders\TeamUserHasRoleSeeder;
use Database\Seeders\RoleSeeder;



use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

use Mockery;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Attribute;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Support\MessageCollection;


use App\Console\Commands\AliasReplyScanner;
use Illuminate\Support\Facades\Artisan;

use AliasReplyScanner AS ARS;
use Tests\Traits\MockExternalApis;


class AliasReplyScannerTest extends TestCase
{
    use RefreshDatabase;

    use MockExternalApis {
        setUp as commonSetUp;
    }


    public function getMockedMessage ($email){

        $mock = Mockery::mock(Message::class)
            ->shouldReceive('get')->with('toaddress')
            ->andReturnUsing(function ($key) use ($email) {
                return $email[$key];
            })
            ->getMock();
        return $mock;
    }

    public function setUp(): void
    {

        $this->commonSetUp();
        $emails =  [
            [
                "toaddress"=>"noreply+123456abcdef@hdruk.ac.uk",
                "from"=>fake()->email(),
                "body"=>fake()->paragraph(),
                "subject"=>fake()->sentence(),
            ],
            [
                "toaddress"=>"noreply@hdruk.ac.uk",
                "from"=>fake()->email(),
                "body"=>fake()->paragraph(),
                "subject"=>fake()->sentence(),
            ]
        ];

        $mockedMessages = array();
        foreach ($emails as $email) {
            $mockedMessage = $this->getMockedMessage($email);
            $mockedMessages[] = $mockedMessage;
        }
        $messages = new MessageCollection($mockedMessages);
        
    
        ARS::shouldReceive('getNewMessages')
            ->andReturn($messages);

        ARS::makePartial();

        $this->seed([
            TeamSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            TeamHasUserSeeder::class,
            TeamUserHasRoleSeeder::class,
            EnquiryThreadSeeder::class,
        ]);
    }


    public function test_it_can_get_new_messages(): void
    {
        $messages = ARS::getNewMessages();
        $this->assertCount(2,$messages);
    }

    public function test_it_select_emails_with_aliases(): void
    {
        $messages = ARS::getNewMessages();

        $firstMessage = $messages[0];
        $alias = ARS::getAlias($firstMessage);
        $this->assertNotEmpty($alias);

        $secondMessage = $messages[1];
        $alias = ARS::getAlias($secondMessage);
        $this->assertEmpty($alias);

    }

    public function test_it_can_get_find_a_thread_from_a_alias(): void
    {
        $thread = EnquiryThread::get()->first();
        $alias = $thread->unique_key;
        $thread = ARS::getThread($alias);
        $this->assertNotEmpty($thread);
    }

    public function test_it_doesnt_find_a_thread_from_a_bad_alias(): void
    {
        $thread = ARS::getThread("....");
        $this->assertEmpty($thread);
    }


}

?>
