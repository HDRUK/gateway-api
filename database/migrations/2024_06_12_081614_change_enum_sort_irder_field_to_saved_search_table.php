<?php

use App\Models\SavedSearch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('saved_searches', 'sort_order')) {
            $sortOrders = SavedSearch::all();

            DB::statement("ALTER TABLE saved_searches DROP COLUMN sort_order");

            Schema::table('saved_searches', function (Blueprint $table) {
                $table->enum('sort_order', ['score', 'title:asc', 'title:desc', 'updated_at:desc', 'updated_at:asc'])->default('score');
            });

            foreach ($sortOrders as $sortOrder) {
                $sort = $sortOrder->sort_order;
                switch ($sort) {
                    case 'score':
                        $sort = 'score';
                        break;
                    case 'title_asc':
                        $sort = 'title:asc';
                        break;
                    case 'title_desc':
                        $sort = 'title:desc';
                        break;
                    case 'updated_at_desc':
                        $sort = 'updated_at:desc';
                        break;
                    case 'updated_at_asc':
                        $sort = 'updated_at:asc';
                        break;
                    default:
                        $sort = 'score';
                }

                SavedSearch::where([
                    'id' => $sortOrder->id,
                ])->update([
                    'sort_order' => $sort,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('saved_searches', 'sort_order')) {
            $sortOrders = SavedSearch::all();

            DB::statement("ALTER TABLE saved_searches DROP COLUMN sort_order");

            Schema::table('saved_searches', function (Blueprint $table) {
                $table->enum('sort_order', ['score', 'title_asc', 'title_desc', 'updated_at_desc', 'updated_at_asc'])->default('score');
            });

            foreach ($sortOrders as $sortOrder) {
                $sort = $sortOrder->sort_order;
                switch ($sort) {
                    case 'score':
                        $sort = 'score';
                        break;
                    case 'title:asc':
                        $sort = 'title_asc';
                        break;
                    case 'title:desc':
                        $sort = 'title_desc';
                        break;
                    case 'updated_at:desc':
                        $sort = 'updated_at_desc';
                        break;
                    case 'updated_at:asc':
                        $sort = 'updated_at_asc';
                        break;
                    default:
                        $sort = 'score';
                }

                SavedSearch::where([
                    'id' => $sortOrder->id,
                ])->update([
                    'sort_order' => $sort,
                ]);
            }
        }
    }
};
