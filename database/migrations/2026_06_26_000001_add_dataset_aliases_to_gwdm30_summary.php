<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gwdm30_summary', function (Blueprint $table) {
            $table->json('dataset_aliases')->nullable()->after('funders');
        });
    }

    public function down(): void
    {
        Schema::table('gwdm30_summary', function (Blueprint $table) {
            $table->dropColumn('dataset_aliases');
        });
    }
};
