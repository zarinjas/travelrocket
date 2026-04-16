<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantUserPermission extends Command
{
    protected $signature = 'users:grant-permission {email : User email} {permission : Permission key}';

    protected $description = 'Grant a specific permission key to a user account';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $permission = (string) $this->argument('permission');

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error('User not found for this email.');

            return self::FAILURE;
        }

        $permissions = is_array($user->permissions) ? $user->permissions : [];

        if (! in_array($permission, $permissions, true)) {
            $permissions[] = $permission;
        }

        $user->permissions = array_values($permissions);
        $user->save();

        $this->info('Permission granted successfully.');

        return self::SUCCESS;
    }
}
