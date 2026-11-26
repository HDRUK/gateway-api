<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('dataset_share_tokens', function (Blueprint $table) {
            $table->timestamp('ready_for_review_at')->nullable()->after('last_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('dataset_share_tokens', function (Blueprint $table) {
            $table->dropColumn('ready_for_review_at');
        });
    }
};
