<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DeleteUserByEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Request to remove user from Gateway';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->ask('Enter the user\'s email address');

        $user = User::withTrashed()
            ->where(function ($query) use ($email) {
                $query->where('email', $email)
                    ->orWhere('secondary_email', $email);
            })
            ->first();

        if (!$user) {
            $this->error('User with this email does not exist.');
            return 1;
        }

        if ($user->provider === 'service') {
            $this->error('User cannot be removed with this command.');
            return 1;
        }

        $this->info("User found: {$user->name} ({$user->email})");

        if ($user->trashed()) {
            $this->warn("⚠️ This user is already soft deleted.");
            return 0;
        }

        if ($this->confirm('Do you want to soft delete this user?', true)) {
            if ($this->confirm("Are you sure you want to soft delete {$user->email}?", true)) {
                $user->delete();
                $this->info("✅ User soft deleted.");
                return 0;
            } else {
                $this->warn("❌ Action canceled.");
                return 0;
            }
        } else {
            if ($this->confirm('Do you want to hard delete this user?', false)) {
                if ($this->confirm("Are you sure you want to permanently delete {$user->email}? This cannot be undone.", false)) {
                    $user->forceDelete();
                    $this->info("🗑️ User permanently deleted.");
                    return 0;
                } else {
                    $this->warn("❌ Action canceled.");
                    return 0;
                }
            } else {
                $this->info("🛑 No action taken.");
                return 0;
            }
        }
    }
}
