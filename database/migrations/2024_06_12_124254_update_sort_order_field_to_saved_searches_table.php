<?php

use App\Models\SavedSearch;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('saved_searches', 'sort_order'))
        {
            $sortOrders = SavedSearch::all();

            DB::statement("ALTER TABLE saved_searches DROP COLUMN sort_order");

            Schema::table('saved_searches', function (Blueprint $table) {
                $table->text('search_term')->nullable()->change();
                $table->enum('sort_order', ['score:desc', 'name:asc', 'name:desc', 'created_at:asc', 'created_at:desc'])->default('score:desc');
            });

            foreach ($sortOrders as $sortOrder) {
                $sort = $sortOrder->sort_order;
                switch ($sort) {
                    case 'score':
                        $sort = 'score:desc';
                        break;
                    case 'title:asc':
                        $sort = 'name:asc';
                        break;
                    case 'title:desc':
                        $sort = 'name:desc';
                        break;
                    case 'updated_at:desc':
                        $sort = 'created_at:asc';
                        break;
                    case 'updated_at:asc':
                        $sort = 'created_at:desc';
                        break;
                    default:
                        $sort = 'score:desc';
                }

                SavedSearch::where([
                    'id' => $sortOrder->id,
                ])->update([
                    'sort_order' => $sort,
                ]);
            }
        }
        Schema::table('saved_searches', function (Blueprint $table) {
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('saved_searches', 'sort_order'))
        {
            $sortOrders = SavedSearch::all();

            DB::statement("ALTER TABLE saved_searches DROP COLUMN sort_order");

            Schema::table('saved_searches', function (Blueprint $table) {
                $table->enum('sort_order', ['score', 'title:asc', 'title:desc', 'updated_at:desc', 'updated_at:asc'])->default('score');
            });

            foreach ($sortOrders as $sortOrder) {
                $sort = $sortOrder->sort_order;
                switch ($sort) {
                    case 'score:desc':
                        $sort = 'score';
                        break;
                    case 'name:asc':
                        $sort = 'title:asc';
                        break;
                    case 'name:desc':
                        $sort = 'title:desc';
                        break;
                    case 'created_at:desc':
                        $sort = 'updated_at:desc';
                        break;
                    case 'created_at:asc':
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
};
