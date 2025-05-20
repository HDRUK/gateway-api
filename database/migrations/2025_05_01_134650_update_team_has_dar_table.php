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
        Schema::disableForeignKeyConstraints();

        Schema::create('tmp_team_has_dar_applications', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->bigInteger('dar_application_id')->unsigned();
            $table->bigInteger('team_id')->unsigned();
        });

        DB::table('tmp_team_has_dar_applications')->insert(
            DB::table('team_has_dar_applications')
                ->select('id', 'team_id', 'dar_application_id', 'created_at', 'updated_at')
                ->get()
                ->map(function ($row) {
                    return (array) $row;
                })
                ->toArray()
        );

        Schema::drop('team_has_dar_applications');

        Schema::create('team_has_dar_applications', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->bigInteger('dar_application_id')->unsigned();
            $table->bigInteger('team_id')->unsigned();

            $table->foreign('dar_application_id')->references('id')->on('dar_applications');
            $table->foreign('team_id')->references('id')->on('teams');
        });

        DB::table('team_has_dar_applications')->insert(
            DB::table('tmp_team_has_dar_applications')
                ->select('id', 'team_id', 'dar_application_id', 'created_at', 'updated_at')
                ->get()
                ->map(function ($row) {
                    return (array) $row;
                })
                ->toArray()
        );

        Schema::drop('tmp_team_has_dar_applications');

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_has_dar_applications', function (Blueprint $table) {
            $table->string('approval_status')->nullable();
            $table->string('submission_status')->default('DRAFT');
            $table->bigInteger('review_id')->nullable();
        });
    }
};
