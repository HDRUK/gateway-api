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
        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->text('data_custodian_entities_ids')->nullable();
            $table->text('included_datasets')->nullable();
            $table->text('included_data_uses')->nullable();
            $table->text('included_scripts')->nullable();
            $table->text('included_collections')->nullable();
            $table->boolean('include_search_bar')->default(false);
            $table->boolean('include_cohort_link')->default(false);
            $table->integer('size_width')->nullable();
            $table->integer('size_height')->nullable();
            $table->enum('unit', ['px', '%', 'rem'])->default('px');
            $table->boolean('keep_proportions')->default(false);
            $table->string('widget_name');
            $table->text('permitted_domains')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('widgets');
    }
};
