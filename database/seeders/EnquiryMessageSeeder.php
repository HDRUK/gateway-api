<?php

namespace Database\Seeders;

use App\Models\EnquiryThread;
use App\Models\EnquiryMessage;
use Illuminate\Database\Seeder;

class EnquiryMessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $threads = EnquiryThread::all();
        foreach ($threads as $thread) {
            $messagesPerThread = fake()->numberBetween(1, 10);
            EnquiryMessage::factory($messagesPerThread)->create([
                'thread_id' => $thread->id
            ]);
        }
    }
}
