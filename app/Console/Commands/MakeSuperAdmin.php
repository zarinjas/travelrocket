<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeSuperAdmin extends Command
{
    protected $signature = 'users:make-super-admin {email : Email for the user account}';

    protected $description = 'Promote an existing user account to super admin role';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error('User not found for this email.');

            return self::FAILURE;
        }

        $user->role = User::ROLE_SUPER_ADMIN;
        $user->tenant_id = null;
        $user->save();

        $this->info('User promoted to super admin successfully.');

        return self::SUCCESS;
    }
}
