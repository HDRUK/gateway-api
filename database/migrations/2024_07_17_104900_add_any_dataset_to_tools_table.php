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
        Schema::table('tools', function (Blueprint $table) {
            $table->boolean('any_dataset')->default('false');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tools', 'any_dataset')) {
            Schema::table('tools', function (Blueprint $table) {
                $table->dropColumn([
                    'any_dataset',
                ]);
            });
        }
    }
};
