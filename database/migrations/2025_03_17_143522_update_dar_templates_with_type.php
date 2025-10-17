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
        Schema::table('dar_templates', function (Blueprint $table) {
            $table->string('template_type')->default('FORM');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    //     Schema::table('dar_template_has_files', function (Blueprint $table) {
    //         $table->dropForeign('dar_template_has_files_template_id_foreign');
    //     });

        Schema::table('dar_templates', function (Blueprint $table) {
            $table->dropColumn('template_type');
        });
    }
};
