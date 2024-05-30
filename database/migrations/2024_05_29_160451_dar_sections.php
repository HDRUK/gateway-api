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
        Schema::table('dar_sections', function (Blueprint $table) {
            $table->text('description')->after('name');
            $table->unsignedBigInteger('sub_section')->nullable()->change();
        });
        
        Schema::table('dar_sections', function (Blueprint $table) {
            $table->renameColumn('sub_section', 'parent_section');
        });
    }

    /**
    * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::table('dar_sections', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->renameColumn('parent_section', 'sub_section');
        });

        Schema::table('dar_sections', function (Blueprint $table) {
            $table->string('sub_section')->nullable()->change();
        });
    }
};
