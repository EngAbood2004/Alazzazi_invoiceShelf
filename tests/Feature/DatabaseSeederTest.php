<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seeder_does_not_create_demo_records_or_default_accounts(): void
    {
        config([
            'laraventry.bootstrap_admin.name' => null,
            'laraventry.bootstrap_admin.email' => null,
            'laraventry.bootstrap_admin.password' => null,
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('facilities', 0);
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_seeder_creates_only_the_explicitly_configured_administrator(): void
    {
        config([
            'laraventry.bootstrap_admin.name' => 'Configured Administrator',
            'laraventry.bootstrap_admin.email' => 'owner@example.test',
            'laraventry.bootstrap_admin.password' => 'SecureTestPassword123!',
        ]);

        $this->seed(DatabaseSeeder::class);

        $user = User::where('email', 'owner@example.test')->firstOrFail();

        $this->assertSame('admin', $user->role->value);
        $this->assertTrue(password_verify('SecureTestPassword123!', $user->password));
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('products', 0);
    }
}
