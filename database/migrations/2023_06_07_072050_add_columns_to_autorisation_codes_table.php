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
        Schema::table('authorisation_codes', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->default(null);
            $table->timestamp('expired_at')->nullable()->default(null);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('authorisation_codes', 'created_at')) {
            Schema::table('authorisation_codes', function (Blueprint $table) {
                $table->dropColumn([
                    'created_at',
                ]);
            });
        }

        if (Schema::hasColumn('authorisation_codes', 'expired_at')) {
            Schema::table('authorisation_codes', function (Blueprint $table) {
                $table->dropColumn([
                    'expired_at',
                ]);
            });

        }
        if (Schema::hasColumn('authorisation_codes', 'updated_at')) {
            Schema::table('authorisation_codes', function (Blueprint $table) {
                $table->dropColumn([
                    'updated_at',
                ]);
            });
        }
        if (Schema::hasColumn('authorisation_codes', 'deleted_at')) {
            Schema::table('authorisation_codes', function (Blueprint $table) {
                $table->dropColumn([
                    'deleted_at',
                ]);
            });
        }
    }
};
