<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EnquiryThread;


class EnquiryThreadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       EnquiryThread::factory(10)->create();
    }
}
