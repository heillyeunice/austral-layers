<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateStaffAccount extends Command
{
    protected $signature = 'staff:create {role : The account role (superadmin only)}';

    protected $description = 'Bootstrap the initial SuperAdmin account';

    public function handle(): int
    {
        $role = $this->argument('role');

        if ($role !== User::ROLE_SUPERADMIN) {
            $this->error('Admin accounts can only be nominated by an authenticated SuperAdmin.');

            return self::FAILURE;
        }

        if (User::query()->where('role', User::ROLE_SUPERADMIN)->exists()) {
            $this->error('A SuperAdmin account already exists.');

            return self::FAILURE;
        }

        $data = [
            'name' => $this->ask('Name'),
            'email' => strtolower(trim((string) $this->ask('Email'))),
            'password' => $this->secret('Password (minimum 12 characters)'),
            'password_confirmation' => $this->secret('Confirm password'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $validated = $validator->validated();
        $user = new User;
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = $validated['password'];
        $user->role = $role;
        $user->save();

        $this->info('SuperAdmin account created successfully.');

        return self::SUCCESS;
    }
}
