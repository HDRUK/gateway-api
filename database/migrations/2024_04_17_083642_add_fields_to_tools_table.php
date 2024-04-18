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
            $table->string('programming_language', 255)->nullable(true);
            $table->string('programming_package', 255)->nullable(true);
            $table->string('type_category', 255)->nullable(true);
            $table->string('associated_authors', 255)->nullable(true);
            $table->string('contact_address', 255)->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tools', 'programming_language'))
        {
            Schema::table('tools', function(Blueprint $table)
            {
                $table->dropColumn('programming_language');
            });
        }

        if (Schema::hasColumn('tools', 'programming_package'))
        {
            Schema::table('tools', function(Blueprint $table)
            {
                $table->dropColumn('programming_package');
            });
        }

        if (Schema::hasColumn('tools', 'type_category'))
        {
            Schema::table('tools', function(Blueprint $table)
            {
                $table->dropColumn('type_category');
            });
        }

        if (Schema::hasColumn('tools', 'associated_authors'))
        {
            Schema::table('tools', function(Blueprint $table)
            {
                $table->dropColumn('associated_authors');
            });
        }

        if (Schema::hasColumn('tools', 'contact_address'))
        {
            Schema::table('tools', function(Blueprint $table)
            {
                $table->dropColumn('contact_address');
            });
        }
    }
};
