<?php

use Illuminate\Database\Migrations\Migration;

use App\Models\User;
use App\Models\UserHasRole;

return new class () extends Migration {
    private $envUsers = [
        'local' => [
            'phil.reeks@hdruk.ac.uk',
            'branwen.snelling@hdruk.ac.uk',
            'dan.nita@hdruk.ac.uk',
            'sam.cox@hdruk.ac.uk',
            'jamie.byrne@hdruk.ac.uk',
            'calum.macdonald@hdruk.ac.uk',
            'peter.hammans@hdruk.ac.uk',
            'clara.fennessy@hdruk.ac.uk',
            'giselle.kerry@hdruk.ac.uk',
            'chandra.chintakindi@hdruk.ac.uk',
            'stephen.lavenberg@hdruk.ac.uk',
            'loki.sinclair@hdruk.ac.uk',
        ],
        'dev' => [
            'phil.reeks@hdruk.ac.uk',
            'branwen.snelling@hdruk.ac.uk',
            'dan.nita@hdruk.ac.uk',
            'sam.cox@hdruk.ac.uk',
            'jamie.byrne@hdruk.ac.uk',
            'calum.macdonald@hdruk.ac.uk',
            'peter.hammans@hdruk.ac.uk',
            'clara.fennessy@hdruk.ac.uk',
            'giselle.kerry@hdruk.ac.uk',
            'chandra.chintakindi@hdruk.ac.uk',
            'stephen.lavenberg@hdruk.ac.uk',
            'loki.sinclair@hdruk.ac.uk',
        ],
        'preprod' => [
            'phil.reeks@hdruk.ac.uk',
            'branwen.snelling@hdruk.ac.uk',
            'dan.nita@hdruk.ac.uk',
            'sam.cox@hdruk.ac.uk',
            'jamie.byrne@hdruk.ac.uk',
            'calum.macdonald@hdruk.ac.uk',
            'peter.hammans@hdruk.ac.uk',
            'clara.fennessy@hdruk.ac.uk',
            'giselle.kerry@hdruk.ac.uk',
            'chandra.chintakindi@hdruk.ac.uk',
            'stephen.lavenberg@hdruk.ac.uk',
            'loki.sinclair@hdruk.ac.uk',
        ],
        'prod' => [
            'clara.fennessy@hdruk.ac.uk',
            'giselle.kerry@hdruk.ac.uk',
            'chandra.chintakindi@hdruk.ac.uk',
            'stephen.lavenberg@hdruk.ac.uk',
            'loki.sinclair@hdruk.ac.uk',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $user = User::where('email', 'developers@hdruk.ac.uk')->first();
        if ($user) {
            // Soft-delete the user to maintain data integrity for any related records
            $user->delete();

            // Remove su-role from the user just in case
            UserHasRole::where('user_id', $user->id)
                ->where('role_id', 1)
                ->delete();
        }

        // Now apply permissions to other users
        foreach ($this->envUsers[env('APP_ENV')] as $email) {
            $user = User::where('email', $email)->select('id')->first();
            if (!$user) {
                \Log::info('User with email ' . $email . ' does not exist, skipping.');
                continue;
            }

            // Create roles as neccessary
            UserHasRole::firstOrCreate([
                'user_id' => $user->id,
                'role_id' => 1,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $user = User::withTrashed()->where('email', 'developers@hdruk.ac.uk')->first();
        if ($user) {
            $user->restore();

            UserHasRole::firstOrCreate([
                'user_id' => $user->id,
                'role_id' => 1,
            ]);
        }
    }
};
