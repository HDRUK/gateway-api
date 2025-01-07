<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::table('federations', function (Blueprint $table) {
            // Remove the 'auth_secret_key' field
            $table->dropColumn('auth_secret_key');
        });
        Schema::table('federations', function (Blueprint $table) {
            $table->string('auth_secret_key_location')->nullable()->after('auth_type');
        });
        Schema::table('federations', function (Blueprint $table) {
            // Add a new field for PID
            $table->string('pid')->nullable()->after('id');
        });
    }

    public function down()
    {
        Schema::table('federations', function (Blueprint $table) {
            // Re-add the 'auth_secret_key' field
            $table->string('auth_secret_key')->nullable();
        });
        Schema::table('federations', function (Blueprint $table) {
            $table->dropColumn('auth_secret_key_location');
        });
        Schema::table('federations', function (Blueprint $table) {
            // Remove the 'pid' field
            $table->dropColumn('pid');
        });
    }
};
