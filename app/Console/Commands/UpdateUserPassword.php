<?php

namespace App\Console\Commands;

use Hash;
use App\Models\User;
use Illuminate\Console\Command;

class UpdateUserPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-user-password';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update user password by email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->ask('Enter the user\'s email address');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error('User with this email does not exist.');
            return 1; // Return non-zero exit code for failure
        }

        $newPassword = $this->secret('Please enter the new password');
        $confirmPassword = $this->secret('Please confirm the new password');

        if ($newPassword !== $confirmPassword) {
            $this->error('Passwords do not match. Try again.');
            return 1;
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        $this->info('Password updated successfully for user with email ' . $email);
        return 0;
    }
}
