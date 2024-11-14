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
            $table->string('dar_modal_footer')->nullable()->after('dar_modal_content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('teams', 'dar_modal_footer')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->dropColumn([
                    'dar_modal_footer',
                ]);
            });
        }
    }
};
