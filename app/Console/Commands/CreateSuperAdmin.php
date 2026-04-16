<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateSuperAdmin extends Command
{
    protected $signature = 'users:create-super-admin {email : Super admin email} {name=Platform Admin : Display name} {password=SuperAdmin123! : Initial password}';

    protected $description = 'Create or update a super admin account with direct platform access';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $name = (string) $this->argument('name');
        $password = (string) $this->argument('password');

        $user = User::query()->firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->name = $name;
            $user->password = $password;
        }

        $user->role = User::ROLE_SUPER_ADMIN;
        $user->tenant_id = null;

        if (! $user->exists) {
            $user->permissions = ['content.manage'];
        } else {
            $permissions = is_array($user->permissions) ? $user->permissions : [];
            if (! in_array('content.manage', $permissions, true)) {
                $permissions[] = 'content.manage';
            }
            $user->permissions = array_values($permissions);
        }

        $user->save();

        $this->info('Super admin account is ready.');
        $this->line('Email: '.$email);
        $this->line('Password: '.$password);

        return self::SUCCESS;
    }
}
