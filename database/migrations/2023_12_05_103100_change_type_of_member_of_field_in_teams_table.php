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
        Schema::table('teams', function (Blueprint $table) {
            $table->string('member_of')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('member_of')->nullable()->change();
        });

        DB::table('teams')->update(['member_of' => null]);

        Schema::table('teams', function (Blueprint $table) {
            $table->integer('member_of')->nullable()->change();
        });
    }
};
