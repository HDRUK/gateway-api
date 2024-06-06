<?php

use Illuminate\Support\Facades\Schema;
use App\Http\Enums\SortOrderSavedSearch;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('saved_searches', function (Blueprint $table) {
            $table->enum('sort_order', [
                SortOrderSavedSearch::MOST_RELEVANT->value,
                SortOrderSavedSearch::SORT_TITLE_ASC->value, 
                SortOrderSavedSearch::SORT_TITLE_DESC->value,
                SortOrderSavedSearch::MOST_RECENTLY_UPDATED->value,
                SortOrderSavedSearch::LEAST_RECENTLY_UPDATED->value,
            ])->default(SortOrderSavedSearch::MOST_RELEVANT->value);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('saved_searches', 'sort_order'))
        {
            Schema::table('saved_searches', function(Blueprint $table)
            {
                $table->dropColumn('sort_order');
            });
        }
    }
};
