<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $isSQLite = DB::getDriverName() === 'sqlite';

        Schema::dropIfExists('oauth_clients_12x');

        if ($isSQLite) {
            DB::statement('CREATE TABLE oauth_clients_12x AS SELECT * FROM oauth_clients');
        } else {
            DB::statement('CREATE TABLE oauth_clients_12x LIKE oauth_clients');
            DB::statement('INSERT INTO oauth_clients_12x SELECT * FROM oauth_clients');
        }

        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->string('owner_id')->nullable()->after('user_id');
            $table->string('owner_type')->nullable()->after('owner_id');
            $table->text('redirect_uris')->nullable()->after('provider');
            $table->text('grant_types')->nullable()->after('redirect_uris');
        });

        DB::table('oauth_clients')->cursor()->each(function ($client) {
            $ownerType = null;

            if ($client->user_id) {
                $provider = $client->provider ?: config('auth.guards.api.provider');
                $ownerType = config("auth.providers.{$provider}.model");
            }

            $redirectUris = json_encode(array_values(array_filter(array_map('trim', explode(',', $client->redirect ?? '')))));

            $grantTypes = json_encode(array_values(array_filter([
                $client->personal_access_client ? 'personal_access' : null,
                $client->password_client ? 'password' : null,
                'authorization_code',
            ])));

            DB::table('oauth_clients')->where('id', $client->id)->update([
                'owner_id' => $client->user_id,
                'owner_type' => $ownerType,
                'redirect_uris' => $redirectUris,
                'grant_types' => $grantTypes,
            ]);
        });

        if (! $isSQLite) {
            Schema::table('oauth_clients', function (Blueprint $table) {
                $table->dropColumn([
                    'user_id',
                    'redirect',
                    'personal_access_client',
                    'password_client',
                ]);

                $table->text('redirect_uris')->nullable(false)->change();
                $table->text('grant_types')->nullable(false)->change();
            });
        } else {
            DB::statement('CREATE TABLE oauth_clients_new AS SELECT id, name, owner_id, owner_type, secret, provider, redirect_uris, grant_types, revoked, created_at, updated_at FROM oauth_clients');
            DB::statement('DROP TABLE oauth_clients');
            DB::statement('ALTER TABLE oauth_clients_new RENAME TO oauth_clients');
        }


        // Schema::dropIfExists('oauth_clients_12x');
        // DB::statement('CREATE TABLE oauth_clients_12x LIKE oauth_clients');
        // DB::statement('INSERT INTO oauth_clients_12x SELECT * FROM oauth_clients');

        // Schema::table('oauth_clients', function (Blueprint $table) {
        //     $table->nullableMorphs('owner', after: 'user_id');

        //     $table->after('provider', function (Blueprint $table) {
        //         $table->text('redirect_uris')->nullable();
        //         $table->text('grand_types')->nullable();
        //     });
        // });

        // DB::table('oauth_clients')->cursor()->each(function ($client) {
        //     $ownerType = null;

        //     if ($client->user_id) {
        //         $provider = $client->provider ?: config('aouth.guards.api.provider');
        //         $ownerType = config("auth.providers.{$provider}.model");
        //     }

        //     $redirectUris = json_encode(array_values(array_filter(array_map('trim', explode(',', $client->redirect ?? '')))));

        //     $grandTypes = json_encode(array_values(array_filter([
        //         $client->personal_access_client ? 'personal_access' : null,
        //         'authorization_code'
        //     ])));

        //     DB::table('oauth_clients')
        //         ->where('id', $client->id)
        //         ->update([
        //             'owner_id' => $client->user_id,
        //             'owner_type' => $ownerType,
        //             'redirect_uris' => $redirectUris,
        //             'grand_types' => $grandTypes,
        //         ]);
        // });

        // Schema::table('oauth_clients', function (Blueprint $table) {
        //     $table->dropColumn([
        //         'user_id',
        //         'redirect',
        //         'personal_access_client',
        //     ]);

        //     $table->text('redirect_uris')->nullable(false)->change();
        //     $table->text('grand_types')->nullable(false)->change();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // not yet
    }
};
