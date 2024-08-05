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
        Schema::table('users', function (Blueprint $table) {
            $table->string('orcid', 255)->nullable()->default(null)->change();
        });

        DB::table('users')
            ->where('orcid', 'https://orcid.org/')
            ->update(['orcid' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('orcid', 255)->nullable()->default('https://orcid.org/')->change();
        });

        DB::table('users')
            ->whereNull('orcid')
            ->update(['orcid' => 'https://orcid.org/']);
    }
};
