<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('email_templates')
            ->where('identifier', 'feasibilityenquiry.firstmessage')
            ->update(['subject' => 'Feasibility Enquiry from the Health Data Research Gateway: [[USER_FIRST_NAME]] [[USER_LAST_NAME]], [[USER_ORGANISATION]]']);
        DB::table('email_templates')
            ->where('identifier', 'generalenquiry.firstmessage')
            ->update(['subject' => 'General Enquiry from the Health Data Research Gateway: [[USER_FIRST_NAME]] [[USER_LAST_NAME]], [[USER_ORGANISATION]]']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('email_templates')
            ->where('identifier', 'feasibilityenquiry.firstmessage')
            ->update(['subject' => '[[USER_FIRST_NAME]] [[PROJECT_TITLE]]']);
        DB::table('email_templates')
            ->where('identifier', 'generalenquiry.firstmessage')
            ->update(['subject' => '[[USER_FIRST_NAME]] Enquiry']);


        if (!Schema::hasTable('authorisation_codes')) {
            Schema::create('authorisation_codes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->text('jwt');
                $table->timestamps();
            });
        }
    }
};
