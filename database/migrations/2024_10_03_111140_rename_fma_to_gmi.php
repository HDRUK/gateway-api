<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Dataset;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Dataset::where('create_origin', 'FMA')->update(['create_origin' => 'GMI']);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Dataset::where('create_origin', 'GMI')->update(['create_origin' => 'FMA']);
    }
};
