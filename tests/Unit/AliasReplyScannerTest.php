<?php

namespace Tests\Unit;

use App\Mail\Email;
use App\Models\EmailTemplate;
use App\Models\EnquiryMessages;
use App\Models\EnquiryThread;

use Tests\TestCase;
use Database\Seeders\EnquiryThreadSeeder;
use Database\Seeders\EnquiryMessagesSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\TeamSeeder;
use Database\Seeders\TeamHasUserSeeder;
use Database\Seeders\TeamUserHasRoleSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Testing\Fluent\AssertableJson;


use Illuminate\Foundation\Testing\RefreshDatabase;

use Mockery;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Support\MessageCollection;


use AliasReplyScanner AS ARS;
use Tests\Traits\MockExternalApis;


class AliasReplyScannerTest extends TestCase
{
    use RefreshDatabase;

    use MockExternalApis {
        setUp as commonSetUp;
    }

    private $emails = null;
    private $messages = null;

    

    private function generateRandomCode($length = 50) {
        $characters = '!@#$%^&*()_-+=<>?{}[]|';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }

        return "<script>".$code."</script>";
    }

    private function getEmails ($unique_key) {
        return [
            [ //email that contains a valid alias 
                "toaddress"=>"noreply+".$unique_key."@hdruk.ac.uk",
                "from"=>fake()->email(),
                "body"=>fake()->paragraph(),
                "subject"=>fake()->sentence(),
            ],
            [//email that doesnt contains a valid alias 
                "toaddress"=>"noreply@hdruk.ac.uk",
                "from"=>fake()->email(),
                "body"=>fake()->paragraph(),
                "subject"=>fake()->sentence(),
            ],
            [//email that contains some nasty code
                "toaddress"=>"noreply+".$unique_key."@hdruk.ac.uk",
                "from"=>fake()->email(),
                "body"=>$this->generateRandomCode(),
                "subject"=>fake()->sentence(),
            ]
        ];
    }

    public function getMockedMessage ($email){

        $mock = Mockery::mock(Message::class)
            ->shouldReceive('get')->with(Mockery::any())
            ->andReturnUsing(function ($key) use ($email) {
                return $email[$key];
            })
            ->shouldReceive('getHTMLBody')
            ->andReturnUsing(function () use ($email) {
                return $email['body'];
            })
            ->shouldReceive('getFrom')
            ->andReturnUsing(function () use ($email) {
                return $email['from'];
            })
            ->getMock();
        return $mock;
    }

    public function setUp(): void
    {

        $this->commonSetUp();

        $this->seed([
            TeamSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            TeamHasUserSeeder::class,
            TeamUserHasRoleSeeder::class,
            EnquiryThreadSeeder::class,
            EnquiryMessagesSeeder::class,
        ]);

        $unique_key = EnquiryThread::get()->first()->unique_key;

        $this->emails = $this->getEmails($unique_key);

        $this->messages = new MessageCollection();
        foreach ($this->emails as $email) {
            $mockedMessage = $this->getMockedMessage($email);
            $this->messages->add($mockedMessage);
        }
       
        ARS::shouldReceive('getNewMessages')
            ->andReturn($this->messages);

        ARS::makePartial();

    }


    public function test_it_can_get_new_messages(): void
    {
        $messages = ARS::getNewMessages();
        $this->assertCount(3,$messages);
    }

    public function test_it_can_extract_an_alias_from_an_email(): void
    {
        $messages = ARS::getNewMessages();

        $firstMessage = $messages[0];
        $alias = ARS::getAlias($firstMessage);
        $this->assertNotEmpty($alias);
        $this->assertSame(32,strlen($alias));

        $secondMessage = $messages[1];
        $alias = ARS::getAlias($secondMessage);
        $this->assertEmpty($alias);

    }

    public function test_it_can_get_find_a_thread_from_an_alias(): void
    {
        $thread = EnquiryThread::get()->first();
        $alias = $thread->unique_key;
        $response = ARS::getThread($alias);
        $this->assertNotEmpty($response);
        $this->assertEquals($response->id,$thread->id);
    }

    public function test_it_doesnt_find_a_thread_from_a_bad_alias(): void
    {
        $thread = ARS::getThread("....");
        $this->assertEmpty($thread);
    }

     public function test_it_can_check_messages(): void
    {
        $messages = ARS::getNewMessages();

        $firstMessage = $messages[0];
        $thirdMessage = $messages[2];
    
        $text = ARS::getSanitisedBody($firstMessage);
        $check = ARS::checkBodyIsSensible($text);
        $this->assertTrue($check);

        $text = ARS::getSanitisedBody($thirdMessage);
        $check = ARS::checkBodyIsSensible($text);
        $this->assertFalse($check);
    }

    public function test_it_can_get_new_safe_messages(): void
    {
        $messages = ARS::getNewMessagesSafe();
        $this->assertCount(2,$messages);
    }

    public function test_it_can_scrape_and_store_email_content(): void
    {

        $messages = ARS::getNewMessagesSafe();
        $firstMessage = $messages[0];
        $alias = ARS::getAlias($firstMessage);
        $enquiryThread = ARS::getThread($alias);

        $this->assertSame(array_keys($enquiryThread->toArray()),
                            ['id',
                            'user_id',
                            'team_id',
                            'project_title',
                            'unique_key']);

        $nMessagesBefore = EnquiryMessages::get()->count();

        $enquiryMessage = ARS::scrapeAndStoreContent($firstMessage,$enquiryThread->id);
        $this->assertNotEmpty($enquiryMessage);

        $nMessagesAfter = EnquiryMessages::get()->count();
        $this->assertTrue($nMessagesAfter == $nMessagesBefore + 1);

        $this->assertSame($enquiryMessage->message_body,$firstMessage->getHTMLBody());



    }



}

?>
