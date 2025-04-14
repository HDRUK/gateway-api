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
        // Create a new table without a primary key constraint
        DB::statement('
            CREATE TABLE team_user_has_roles_tmp (
                team_has_user_id BIGINT UNSIGNED NULL,
                role_id BIGINT UNSIGNED NULL
                -- Add any other columns as needed.
            );
        ');

        // Copy the data from the original table to the new table
        DB::statement('
            INSERT INTO team_user_has_roles_tmp (team_has_user_id, role_id)
            SELECT team_has_user_id, role_id FROM team_user_has_roles;
        ');

        // Drop the original table
        Schema::drop('team_user_has_roles');

        // Rename the new table to the original table name
        DB::statement('ALTER TABLE team_user_has_roles_tmp RENAME TO team_user_has_roles;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_user_has_roles', function (Blueprint $table) {
            $table->id();
        });
    }
};
