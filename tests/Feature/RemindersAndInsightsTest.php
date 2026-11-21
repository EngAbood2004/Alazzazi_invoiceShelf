<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\CommunicationMessage;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use App\Services\Analytics\AiInsightService;
use App\Services\Analytics\FinancialAnalytics;
use App\Services\Analytics\Forecaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RemindersAndInsightsTest extends TestCase
{
    use RefreshDatabase;

    private function configureChannels(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.test',
            'mail.mailers.smtp.username' => 'user',
            'mail.mailers.smtp.password' => 'secret',
            'mail.from.address' => 'billing@example.test',
            'services.whatsapp.access_token' => 'token',
            'services.whatsapp.phone_number_id' => '123',
            'services.whatsapp.reminder_template' => 'invoice_reminder',
        ]);
        Mail::fake();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);
    }

    public function test_overdue_invoice_gets_one_reminder_per_step_and_channel(): void
    {
        $this->configureChannels();
        $invoice = Invoice::factory()->overdue(8)->create();

        $this->artisan('invoices:send-reminders')->assertSuccessful();

        $reminders = $invoice->reminders()->get();
        $this->assertCount(2, $reminders);
        $this->assertSame(['email', 'whatsapp'], $reminders->pluck('channel')->sort()->values()->all());
        $this->assertTrue($reminders->every(fn ($r) => $r->status === 'sent' && $r->days_overdue === 7));
        $this->assertSame(2, CommunicationMessage::where('direction', 'outbound')->count());

        Http::assertSent(fn ($request) => $request['type'] === 'template'
            && $request['template']['name'] === 'invoice_reminder'
            && $request['template']['components'][0]['parameters'][1]['text'] === $invoice->number);

        // Running again the same day does not resend the same step.
        $this->artisan('invoices:send-reminders')->assertSuccessful();
        $this->assertSame(2, $invoice->reminders()->count());
    }

    public function test_paid_or_not_yet_due_invoices_get_no_reminder(): void
    {
        $this->configureChannels();
        Invoice::factory()->create(); // due in 5 days
        Invoice::factory()->overdue(20)->create(['status' => Invoice::STATUS_PAID, 'amount_due' => 0]);

        $this->artisan('invoices:send-reminders')->assertSuccessful();

        $this->assertSame(0, CommunicationMessage::count());
    }

    public function test_failed_channel_is_logged_without_stopping_the_others(): void
    {
        $this->configureChannels();
        config(['services.whatsapp.access_token' => null]);
        $invoice = Invoice::factory()->overdue(2)->create();

        $this->artisan('invoices:send-reminders')->assertSuccessful();

        $this->assertSame('sent', $invoice->reminders()->where('channel', 'email')->value('status'));
        $failed = $invoice->reminders()->where('channel', 'whatsapp')->first();
        $this->assertSame('failed', $failed->status);
        $this->assertStringContainsString('WHATSAPP_ACCESS_TOKEN', $failed->error_message);
    }

    public function test_manual_reminder_button(): void
    {
        $this->configureChannels();
        BusinessSetting::put(['reminder_channels' => 'email']);
        $invoice = Invoice::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'accountant']))
            ->post(route('invoices.remind', $invoice))
            ->assertSessionHas('success');

        $this->assertSame(1, $invoice->fresh()->reminders_sent);
    }

    public function test_forecaster_switches_to_holt_winters_after_two_years(): void
    {
        $forecaster = new Forecaster;
        $seasonal = array_map(fn ($t) => 1000 + 20 * $t + 300 * sin(2 * M_PI * $t / 12), range(0, 35));

        $hw = $forecaster->forecast($seasonal, 3);
        $this->assertSame('holt_winters', $hw['method']);
        $this->assertEqualsWithDelta(1720, $hw['values'][0], 5);
        $this->assertSame('high', $hw['confidence']);

        $short = $forecaster->forecast(array_slice($seasonal, 0, 10), 3);
        $this->assertSame('linear', $short['method']);
    }

    public function test_analytics_summary_and_rule_based_insights(): void
    {
        $customer = Customer::factory()->create(['name' => 'Big Client']);
        $invoice = Invoice::factory()->overdue(40)->create(['customer_id' => $customer->id, 'total' => 1000, 'subtotal' => 1000, 'amount_due' => 600, 'amount_paid' => 400, 'status' => Invoice::STATUS_PARTIALLY_PAID]);
        InvoicePayment::create(['invoice_id' => $invoice->id, 'amount' => 400, 'paid_on' => today()->toDateString()]);
        Expense::create(['expense_category_id' => ExpenseCategory::create(['name' => 'Rent'])->id, 'amount' => 150, 'currency' => 'USD', 'spent_on' => today()->toDateString()]);

        $summary = app(FinancialAnalytics::class)->summary();

        $this->assertEquals(400, $summary['kpis']['cash_in']['value']);
        $this->assertEquals(150, $summary['kpis']['expenses']['value']);
        $this->assertEquals(600, $summary['kpis']['overdue_amount']);
        $this->assertEquals(600, collect($summary['aging'])->firstWhere('key', '31_60')['amount']);
        $this->assertSame('Big Client', $summary['top_customers'][0]['name']);
        $this->assertSame('Rent', $summary['expense_categories'][0]['name']);
        $this->assertCount(12, $summary['trend']['labels']);

        $ai = app(AiInsightService::class)->insights($summary);
        $this->assertSame('rules', $ai['source']);
        $this->assertNotEmpty($ai['insights']);
    }

    public function test_openai_insights_are_used_when_a_key_is_configured(): void
    {
        config(['services.openai.api_key' => 'sk-test', 'services.openai.model' => 'gpt-4o-mini']);
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode(['insights' => [
            ['title' => 'Cash is up', 'explanation' => 'Because client C1 paid 400.', 'action' => 'Keep it up', 'tone' => 'positive'],
            ['title' => 'Bad tone', 'explanation' => 'x', 'action' => null, 'tone' => 'weird'],
        ]])]]]])]);

        $summary = app(FinancialAnalytics::class)->summary();
        $ai = app(AiInsightService::class)->insights($summary, 'ar');

        $this->assertSame('openai', $ai['source']);
        $this->assertSame('Cash is up', $ai['insights'][0]['title']);
        $this->assertSame('info', $ai['insights'][1]['tone']);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->hasHeader('Authorization', 'Bearer sk-test')
                && $body['response_format']['type'] === 'json_object'
                && str_contains($body['messages'][0]['content'], 'Arabic')
                && ! str_contains($body['messages'][1]['content'], '@'); // no customer emails sent to the model
        });
    }

    public function test_openai_failure_falls_back_to_rules(): void
    {
        config(['services.openai.api_key' => 'sk-test']);
        Http::fake(['api.openai.com/*' => Http::response(['error' => 'down'], 500)]);

        $ai = app(AiInsightService::class)->insights(app(FinancialAnalytics::class)->summary());

        $this->assertSame('rules', $ai['source']);
    }
}
