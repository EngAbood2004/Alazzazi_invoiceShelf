<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function token(string $role = 'accountant'): string
    {
        return ApiToken::issue(User::factory()->create(['role' => $role]), 'test');
    }

    public function test_login_returns_a_token_that_authenticates_requests(): void
    {
        $user = User::factory()->create(['email' => 'acc@example.test', 'password' => 'Secret123!']);
        $user->forceFill(['role' => 'accountant'])->save();

        $this->postJson('/api/v1/auth/login', ['email' => 'acc@example.test', 'password' => 'wrong', 'device_name' => 'phone'])->assertUnprocessable();

        $token = $this->postJson('/api/v1/auth/login', ['email' => 'acc@example.test', 'password' => 'Secret123!', 'device_name' => 'phone'])
            ->assertOk()->assertJsonPath('user.can_invoice', true)->json('token');

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('user.email', 'acc@example.test');
        $this->assertDatabaseMissing('api_tokens', ['token' => $token]); // only the hash is stored

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_requests_without_a_valid_token_are_rejected(): void
    {
        $this->getJson('/api/v1/invoices')->assertUnauthorized();
        $this->withToken('1|nope')->getJson('/api/v1/invoices')->assertUnauthorized();
        $this->withToken($this->token('worker'))->getJson('/api/v1/invoices')->assertForbidden();
    }

    public function test_invoice_flow_through_the_api(): void
    {
        $token = $this->token();
        $customer = Customer::factory()->create();

        $id = $this->withToken($token)->postJson('/api/v1/invoices', [
            'customer_id' => $customer->id, 'issue_date' => today()->toDateString(), 'currency' => 'USD',
            'discount_type' => 'fixed', 'send' => true,
            'items' => [['description' => 'Service', 'quantity' => 2, 'unit_price' => 75]],
        ])->assertCreated()->assertJsonPath('data.total', 150)->assertJsonPath('data.status', 'sent')->json('data.id');

        $this->withToken($token)->postJson("/api/v1/invoices/{$id}/payments", ['amount' => 150, 'paid_on' => today()->toDateString()])
            ->assertOk()->assertJsonPath('data.status', 'paid')->assertJsonPath('data.amount_due', 0);

        $this->withToken($token)->getJson('/api/v1/invoices?status=paid')->assertOk()->assertJsonCount(1, 'data');
        $this->withToken($token)->getJson("/api/v1/customers/{$customer->id}")->assertOk()->assertJsonPath('stats.invoiced', 150);
    }

    public function test_dashboard_and_lookups(): void
    {
        $token = $this->token();
        Invoice::factory()->overdue(5)->create();

        $this->withToken($token)->getJson('/api/v1/dashboard')->assertOk()
            ->assertJsonStructure(['currency', 'summary' => ['kpis', 'trend', 'forecast', 'aging'], 'insights' => ['source', 'insights']])
            ->assertJsonPath('summary.kpis.overdue_count', 1);

        $this->withToken($token)->getJson('/api/v1/lookups')->assertOk()->assertJsonStructure(['products', 'taxes', 'payment_methods']);
    }

    public function test_validation_errors_are_json(): void
    {
        $this->withToken($this->token())->postJson('/api/v1/invoices', ['items' => []])
            ->assertUnprocessable()->assertJsonValidationErrors(['customer_id', 'items']);
    }
}
