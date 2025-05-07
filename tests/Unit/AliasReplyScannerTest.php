<?php

namespace Tests\Unit;

use Mockery;
use App\Mail\Email;
use Tests\TestCase;
use App\Models\Role;
use App\Models\Team;
use Webklex\PHPIMAP\Message;
use AliasReplyScanner as ARS;
use App\Models\EnquiryThread;
use App\Models\EnquiryMessage;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TeamSeeder;
use Database\Seeders\UserSeeder;
use Tests\Traits\MockExternalApis;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\TeamHasUserSeeder;
use Database\Seeders\EnquiryThreadSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\EnquiryMessageSeeder;
use Database\Seeders\TeamUserHasRoleSeeder;
use App\Exceptions\AliasReplyScannerException;
use Webklex\PHPIMAP\Support\MessageCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AliasReplyScannerTest extends TestCase
{
    use RefreshDatabase;

    use MockExternalApis {
        setUp as commonSetUp;
    }

    private $emails = null;
    private $messages = null;

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
            EnquiryMessageSeeder::class,
            EmailTemplateSeeder::class,
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
        $this->assertCount(3, $messages);
    }

    public function test_it_can_extract_an_alias_from_an_email(): void
    {
        $messages = ARS::getNewMessages();

        $firstMessage = $messages[0];
        $alias = ARS::getAlias($firstMessage);
        $this->assertNotEmpty($alias);
        $this->assertSame(8, strlen($alias));

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

        $this->assertSame(array_keys($response->toArray()), [
            'id',
            'user_id',
            'project_title',
            'unique_key',
            'is_dar_dialogue',
            'is_dar_status',
            'enabled',
            'is_general_enquiry',
            'is_feasibility_enquiry',
            'is_dar_review',
            'created_at',
            'updated_at',
            'enquiry_unique_key',
            'team_id',
        ]);

        $this->assertEquals($response->id, $thread->id);
    }

    public function test_it_doesnt_find_a_thread_from_a_bad_alias(): void
    {
        $thread = ARS::getThread("....");
        $this->assertEmpty($thread);
    }

    /*  // 17/12/2024 - temporary turn off
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
        $this->assertCount(2, $messages);
    }
    */

    public function test_it_can_scrape_and_store_email_content(): void
    {
        $messages = ARS::getNewMessagesSafe();
        $firstMessage = $messages[0];
        $alias = ARS::getAlias($firstMessage);
        $enquiryThread = ARS::getThread($alias);
        $nMessagesBefore = EnquiryMessage::get()->count();

        $enquiryMessage = ARS::scrapeAndStoreContent($firstMessage, $enquiryThread->id);
        $this->assertNotEmpty($enquiryMessage);

        $nMessagesAfter = EnquiryMessage::get()->count();
        $this->assertTrue($nMessagesAfter == $nMessagesBefore + 1);
        $this->assertSame($enquiryMessage->message_body, $firstMessage->getHTMLBody());

    }

    // public function test_it_can_scrape_an_email_and_store_content(): void
    // {

    //     $messages = ARS::getNewMessagesSafe();
    //     $firstMessage = $messages[0];
    //     $alias = ARS::getAlias($firstMessage);
    //     $enquiryThread = ARS::getThread($alias);

    //     $teamId = $enquiryThread->team_id;
    //     $team = Team::with("users")
    //             ->where("id",$teamId)
    //             ->first();

    //     $actualDarManagers = $team->teamUserRoles
    //         ->where("role_name", "custodian.dar.manager")
    //         ->where("enabled", true);

    //     $darManagers = ARS::getDarManagersFromEnquiryMessage($enquiryThread->id);
    //     $this->assertEqualsCanonicalizing($darManagers,$actualDarManagers);
    // }

    // public function test_it_will_fail_to_get_dar_managers_if_team_doesnt_exist(): void
    // {

    //     $messages = ARS::getNewMessagesSafe();
    //     $firstMessage = $messages[0];
    //     $alias = ARS::getAlias($firstMessage);
    //     $enquiryThread = ARS::getThread($alias);


    //     $teamId = $enquiryThread->team_id;
    //     Team::where("id",$teamId)->delete();

    //     $this->expectException(AliasReplyScannerException::class);
    //     ARS::getDarManagersFromEnquiryMessage($enquiryThread->id);

    // }


    private function getMockedMessage($email)
    {

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

    private function getInjectionAttack()
    {
        return "<HTML>
                <HEAD>
                <TITLE>example</title></head><body><img src=http://myImage.png></TITLE>
                </HEAD>
                <BODY>
                <BR SIZE=\"&{alert(\'Injected\')}\"> 
                <DIV STYLE=\"background-image: url(javascript:alert(\'Injected\'))\">
                I like this site because <script>alert(\'Injected!\');</script> teaches me a lot
                Something
                </BODY>
                </HTML>";
    }

    private function generateRandomCode($length = 50)
    {
        $characters = '!@#$%^&*()_-+=<>?{}[]|';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }

        return "<script>".$code."</script>";
    }
    private function getEmails($unique_key)
    {
        return [
            [ //email that contains a valid alias
                "toaddress" => "noreply+".$unique_key."@hdruk.ac.uk",
                "from" => fake()->email(),
                "body" => fake()->paragraph(),
                "subject" => fake()->sentence(),
            ],
            [//email that doesnt contains a valid alias
                "toaddress" => "noreply@hdruk.ac.uk",
                "from" => fake()->email(),
                "body" => fake()->paragraph(),
                "subject" => fake()->sentence(),
            ],
            [//email that contains some nasty code
                "toaddress" => "noreply+".$unique_key."@hdruk.ac.uk",
                "from" => fake()->email(),
                "body" => $this->generateRandomCode(),
                "subject" => fake()->sentence(),
            ]
        ];
    }


}
