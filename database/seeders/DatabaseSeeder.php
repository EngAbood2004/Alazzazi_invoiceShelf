<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $name = config('laraventry.bootstrap_admin.name');
        $email = config('laraventry.bootstrap_admin.email');
        $password = config('laraventry.bootstrap_admin.password');

        if (! $name && ! $email && ! $password) {
            $this->command?->info('No bootstrap administrator configured; no demo data was created.');

            return;
        }

        if (! $name || ! $email || ! $password || ! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 12) {
            throw new RuntimeException('Set BOOTSTRAP_ADMIN_NAME, BOOTSTRAP_ADMIN_EMAIL, and a BOOTSTRAP_ADMIN_PASSWORD of at least 12 characters.');
        }

        if (User::where('email', $email)->exists()) {
            $this->command?->info('The configured bootstrap administrator already exists; no account or demo data was changed.');

            return;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $user->forceFill(['role' => 'admin'])->save();

        $this->command?->info('The configured bootstrap administrator was created. No demo data was created.');
    }
}
