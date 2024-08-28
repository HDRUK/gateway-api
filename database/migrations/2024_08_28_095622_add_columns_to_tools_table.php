<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToToolsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tools', function (Blueprint $table) {
            // Adding the new columns after the 'description' column
            $table->text('results_insights')->nullable()->after('description')->comment('This is an example description of the results and insights from usage of this analysis script.');
            $table->string('tool_keywords')->nullable()->after('results_insights')->comment('Comma-separated list of keywords, e.g., Analytics');
            $table->string('tools_linkage')->nullable()->after('tool_keywords')->comment('Comma-separated list of linked tools');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tools', function (Blueprint $table) {
            // Dropping the columns if the migration is rolled back
            $table->dropColumn('results_insights');
            $table->dropColumn('tool_keywords');
            $table->dropColumn('tools_linkage');
        });
    }
}
