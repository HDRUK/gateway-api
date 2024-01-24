<?php

namespace Database\Seeders;

use App\Models\EnquiryThread;
use App\Models\EnquiryMessages;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EnquiryMessagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $threads = EnquiryThread::all();
        foreach ($threads as $thread) {
            $messagesPerThread = fake()->numberBetween(1,10);
            EnquiryMessages::factory($messagesPerThread)->create([
                'thread_id' => $thread->id
            ]);
        }
    }
}
