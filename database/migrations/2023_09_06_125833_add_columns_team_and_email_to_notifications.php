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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('email')->default('')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('notifications', 'team_id')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn([
                    'team_id',
                ]);
            });
        }

        if (Schema::hasColumn('notifications', 'user_id')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn([
                    'user_id',
                ]);
            });
        }

        if (Schema::hasColumn('notifications', 'email')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn([
                    'email',
                ]);
            });
        }

    }
};
