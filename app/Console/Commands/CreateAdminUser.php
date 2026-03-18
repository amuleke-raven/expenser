<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class CreateAdminUser extends Command
{
    protected $signature = 'app:create-admin-user';

    protected $description = 'Create the default system administrator user';

    public function handle(): int
    {
        $email = 'sysadmin@domain.com';

        if (User::where('email', $email)->exists()) {
            $this->info("Admin user [{$email}] already exists.");

            return self::SUCCESS;
        }

        User::create([
            'name' => 'sysadmin',
            'email' => $email,
            'password' => 'l0ck3d',
            'role' => UserRole::Admin,
        ]);

        $this->info("Admin user [{$email}] created successfully.");

        return self::SUCCESS;
    }
}
