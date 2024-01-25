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

        //find any duplicate pids 
        $duplicateIds = \DB::table('datasets')
            ->select('pid')
            ->groupBy('pid')
            ->havingRaw('COUNT(pid) > 1')
            ->pluck('pid');

        foreach ($duplicateIds as $duplicateId) {
            $datasets = \DB::table('datasets')->where('id', $duplicateId)->get();
            // Generate new unique PID for each dataset with duplicate ID
            foreach ($datasets as $dataset) {
                $newPid = (string) Str::uuid();
                \DB::table('datasets')->where('id', $dataset->id)->update(['pid' => $newPid]);
            }
        }

        //find any null pids
        $datasets = \DB::table('datasets')->whereNull('pid')->get();
        foreach ($datasets as $dataset) {
            $newPid = (string) Str::uuid();
            \DB::table('datasets')->where('id', $dataset->id)->update(['pid' => $newPid]);
        }

        Schema::table('datasets', function (Blueprint $table) {
            $table->unique('pid')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->dropUnique('datasets_pid_unique');
        });
    }
};
