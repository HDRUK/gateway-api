<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
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
        });
    }
};
