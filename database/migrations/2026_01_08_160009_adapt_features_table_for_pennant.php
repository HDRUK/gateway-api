<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Pennant\Migrations\PennantMigration;

return new class () extends PennantMigration {
    public function up(): void
    {
        if (Schema::hasColumn('features', 'name')) {
            Schema::table('features', function (Blueprint $table) {
                try {
                    $table->dropUnique(['name']);
                } catch (\Throwable $e) {
                }
            });
        }

        if (Schema::hasColumn('features', 'deleted_at')) {
            Schema::table('features', function (Blueprint $table) {
                try {
                    $table->dropIndex(['deleted_at']);
                } catch (\Throwable $e) {
                }
            });
        }

        $columnsToDrop = array_filter(
            ['enabled', 'deleted_at'],
            fn ($col) => Schema::hasColumn('features', $col)
        );

        if (! empty($columnsToDrop)) {
            Schema::table('features', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }

        Schema::table('features', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });

        if (! Schema::hasColumn('features', 'scope')) {
            Schema::table('features', function (Blueprint $table) {
                $table->string('scope')->default('global')->after('name');
            });
        }

        if (! Schema::hasColumn('features', 'value')) {
            Schema::table('features', function (Blueprint $table) {
                $table->text('value')->after('scope');
            });
        }

        Schema::table('features', function (Blueprint $table) {
            try {
                $table->unique(['name', 'scope']);
            } catch (\Throwable $e) {
            }
        });
    }

    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            try {
                $table->dropUnique(['name', 'scope']);
            } catch (\Throwable $e) {
            }
        });

        if (! Schema::hasColumn('features', 'enabled')) {
            Schema::table('features', function (Blueprint $table) {
                $table->boolean('enabled')->default(true);
            });
        }

        if (! Schema::hasColumn('features', 'deleted_at')) {
            Schema::table('features', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        $columnsToDrop = array_filter(
            ['scope', 'value'],
            fn ($col) => Schema::hasColumn('features', $col)
        );

        if (! empty($columnsToDrop)) {
            Schema::table('features', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }

        Schema::table('features', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });

        Schema::table('features', function (Blueprint $table) {
            try {
                $table->unique('name');
            } catch (\Throwable $e) {
            }
        });
    }
};
