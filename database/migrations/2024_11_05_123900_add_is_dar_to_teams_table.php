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
            $table->boolean('is_dar')->default(0)->after('dar_modal_footer')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('teams')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->dropIndex(['is_dar']);
                $table->dropColumn('is_dar');
            });
        }
    }
};
