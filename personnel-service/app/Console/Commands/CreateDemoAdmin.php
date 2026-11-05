<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateDemoAdmin extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create an administrator for the local demo.';

    public function handle(): int
    {
        $name = trim((string) $this->ask('Admin name'));
        $email = strtolower(trim((string) $this->ask('Admin email')));

        if ($name === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('Enter a name and a valid email address.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('An account with that email already exists.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Admin password (at least 12 characters)');
        $confirmation = (string) $this->secret('Confirm admin password');

        if (mb_strlen($password) < 12 || ! hash_equals($password, $confirmation)) {
            $this->error('Passwords must match and contain at least 12 characters.');

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info('Administrator created.');

        return self::SUCCESS;
    }
}
