<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Http\Traits\DataAccessApplicationHelpers;

return new class () extends Migration {
    use DataAccessApplicationHelpers;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->uuid('uuid')->nullable();
        });

        $uploads = DB::table("uploads")->whereNull('uuid')->get();

        foreach ($uploads as $row) {
            DB::table("uploads")
                ->where('id', $row->id)
                ->update(['uuid' => Str::uuid()]);
        }

        Schema::table("uploads", function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });

        // Update the DAR answers that involve files with the uuids
        $rows = DB::table("dar_application_answers")->get();

        foreach ($rows as $k => $row) {
            $answer = json_decode($row->answer, true);
            $isFileAnswer = $this->isFileAnswer($answer);

            if (!$isFileAnswer['is_file']) {
                continue;
            }
            \Log::info("Updating row ID: {$row->id}");

            $value = $answer['value'];

            if ($isFileAnswer['multifile']) {
                foreach ($value as $i => $a) {
                    if (array_key_exists("id", $a)) {
                        $uploadRow = DB::table("uploads")->where('id', $a['id'])->first();
                        if (!$uploadRow) {
                            \Log::error("Upload not found for ID: {$a['id']}");
                            continue;
                        }
                        $a['uuid'] = $uploadRow->uuid;
                        unset($a['id']);
                        DB::table("dar_application_answers")->where("id", $row->id)->update(['answer' => ['value' => $a]]);
                    }
                }
            } else {
                if (array_key_exists("id", $value)) {
                    $uploadRow = DB::table("uploads")->where('id', $value['id'])->first();
                    if (!$uploadRow) {
                        continue;
                    }
                    unset($value['id']);
                    $value['uuid'] = $uploadRow->uuid;
                    DB::table("dar_application_answers")->where("id", $row->id)->update(['answer' => ['value' => $value]]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Update the DAR answers that involve files with the uuids
        $rows = DB::table("dar_application_answers")->get();

        foreach ($rows as $k => $row) {
            $answer = json_decode($row->answer, true);
            $isFileAnswer = $this->isFileAnswer($answer);

            if (!$isFileAnswer['is_file']) {
                continue;
            }
            \Log::info("Updating row ID: {$row->id}");

            $value = $answer['value'];

            if ($isFileAnswer['multifile']) {
                foreach ($value as $i => $a) {
                    if (array_key_exists("uuid", $a)) {
                        $uploadRow = DB::table("uploads")->where('uuid', $a['uuid'])->first();
                        if (!$uploadRow) {
                            \Log::error("Upload not found for uuid: {$a['uuid']}");
                            continue;
                        }
                        $a['id'] = $uploadRow->id;
                        unset($a['uuid']);
                        DB::table("dar_application_answers")->where("id", $row->id)->update(['answer' => ['value' => $a]]);
                    }
                }
            } else {
                if (array_key_exists("uuid", $value)) {
                    $uploadRow = DB::table("uploads")->where('uuid', $value['uuid'])->first();
                    if (!$uploadRow) {
                        continue;
                    }
                    unset($value['uuid']);
                    $value['id'] = $uploadRow->id;
                    DB::table("dar_application_answers")->where("id", $row->id)->update(['answer' => ['value' => $value]]);
                }
            }
        }
            
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};