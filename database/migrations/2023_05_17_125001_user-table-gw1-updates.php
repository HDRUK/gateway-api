<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('sector_id')->nullable();
            $table->string('organisation', 255)->nullable()->default('');
            $table->string('bio', 500)->nullable()->default('');
            $table->string('domain', 128)->nullable()->default('');
            $table->string('link', 255)->nullable()->default('');
            $table->string('orcid', 255)->nullable()->default('https://orcid.org/');
            $table->tinyInteger('contact_feedback')->default(0);
            $table->tinyInteger('contact_news')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sector_id');
            $table->dropColumn('organisation');
            $table->dropColumn('bio');
            $table->dropColumn('domain');
            $table->dropColumn('link');
            $table->dropColumn('orcid');
            $table->dropColumn('contact_feedback');
            $table->dropColumn('contact_news');
        });
    }
};
