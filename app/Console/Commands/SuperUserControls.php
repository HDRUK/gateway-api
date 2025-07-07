<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserHasRole;

class SuperUserControls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:super-user-controls {email : The email address of the user} {--A|action= : The action to perform (add/remove)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add / Remove super users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $action = $this->option('action');

        $user = User::where('email', $email)->select('id', 'email')->first();
        if (!$user) {
            $this->error('User with email ' . $email . ' does not exist.');
            return 1; // Exit with error code
        }

        switch($action) {
            case 'add':
                $role = UserHasRole::where('user_id', $user->id)
                    ->where('role_id', 1)->first();
                
                if (!$role) {
                    UserHasRole::create([
                        'user_id' => $user->id,
                        'role_id' => 1, // Assuming 1 is the ID for super-user role
                    ]);
                    $this->info('User ' . $email . ' has been added as a super user.');
                } else {
                    $this->info('User ' . $email . ' is already a super user.');
                }
                break;

            case 'remove':
                $role = UserHasRole::where('user_id', $user->id)
                    ->where('role_id', 1)
                    ->first();

                if (!$role) {
                    $this->info('User ' . $email . ' is not a super user.');
                    return 0; // Exit with success code
                }

                UserHasRole::where('user_id', $user->id)
                    ->where('role_id', 1)
                    ->delete();
                
                $this->info('User ' . $email . ' has been removed from super user status.');
                break;

            default:
                $this->error('Invalid action specified. Use `add` or `remove`.');
                return 1; // Exit with error code
        }
    }

    protected function promptForMissingArgumentsUsing()
    {
        return [
            'email' => 'Please provide the email address of the user.',
            'action' => 'Please specify the action to perform (add/remove).',
        ];
    }
}
