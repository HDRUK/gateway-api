<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_privileged')->default(false);
        });

        Role::whereIn('name', ['hdruk.admin', 'hdruk.superadmin', 'hdruk.cohort.admin'])
            ->update(['is_privileged' => true]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('is_privileged');
        });
    }
};
