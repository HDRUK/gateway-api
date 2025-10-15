<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    

    public function test_that_user_emails_are_unique()
    {
        $user = User::create([
            'firstname' => 'Test',
            'lastname' => 'User I',
            'name' => 'Test User I',
            'email' => 'test_user_i@doesntexist.com',
        ]);

        if ($user) {
            try {
                $otherUser = User::create([
                    'firstname' => 'Test',
                    'lastname' => 'User II',
                    'name' => 'Test User II',
                    'email' => 'test_user_i@doesntexist.com',
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                $this->assertEquals($e->errorInfo[0], '23000'); // code
                $this->assertEquals($e->errorInfo[2], 'UNIQUE constraint failed: users.email'); // message
            }
        }
    }
}
