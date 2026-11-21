<?php

namespace App\Services\Reminders;

use App\Models\BusinessSetting;
use App\Models\CommunicationMessage;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\User;
use App\Services\Communication\ChannelMessageSender;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Overdue reminders by email and WhatsApp.
 *
 * The schedule is a list of "days after the due date" (setting
 * reminder_days, default 1,7,14,30). Each step is sent at most once per
 * channel. Every reminder goes through the existing contact center
 * (communication_messages), so replies land in the same conversation.
 */
class InvoiceReminderService
{
    public function __construct(private readonly ChannelMessageSender $sender) {}

    /**
     * Sends every reminder that is due today.
     *
     * @return array{invoices: int, sent: int, failed: int}
     */
    public function sendDue(): array
    {
        $steps = $this->steps();
        $summary = ['invoices' => 0, 'sent' => 0, 'failed' => 0];

        Invoice::query()->overdue()->with('customer')->chunkById(100, function (Collection $invoices) use ($steps, &$summary) {
            foreach ($invoices as $invoice) {
                $days = $invoice->daysOverdue();
                $step = collect($steps)->filter(fn (int $s) => $s <= $days)->max();

                if ($step === null) {
                    continue;
                }

                $alreadySent = $invoice->reminders()->where('days_overdue', $step)->where('status', 'sent')->pluck('channel')->all();
                $channels = array_diff($this->channels(), $alreadySent);

                if ($channels === []) {
                    continue;
                }

                $summary['invoices']++;
                foreach ($this->send($invoice, $step, $channels) as $reminder) {
                    $reminder->status === 'sent' ? $summary['sent']++ : $summary['failed']++;
                }
            }
        });

        return $summary;
    }

    /**
     * Manual "send reminder now" from the invoice page.
     *
     * @return array<int, InvoiceReminder>
     */
    public function sendNow(Invoice $invoice, ?User $user = null): array
    {
        return $this->send($invoice->loadMissing('customer'), $invoice->daysOverdue(), $this->channels(), $user);
    }

    /**
     * @param  array<int, string>  $channels
     * @return array<int, InvoiceReminder>
     */
    private function send(Invoice $invoice, int $daysOverdue, array $channels, ?User $user = null): array
    {
        $results = [];

        foreach ($channels as $channel) {
            $address = $channel === 'email' ? $invoice->customer->email : $invoice->customer->whatsappNumber();

            if (! $address) {
                continue;
            }

            $message = CommunicationMessage::create([
                'operator_id' => $user?->id,
                'channel' => $channel,
                'direction' => 'outbound',
                'contact_address' => $address,
                'subject' => $channel === 'email' ? __('Payment reminder: invoice :number', ['number' => $invoice->number]) : null,
                'body' => $this->body($invoice, $daysOverdue),
                'status' => 'pending',
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'reminder' => true,
                    'whatsapp_template' => $this->whatsappTemplate($invoice),
                ],
            ]);

            try {
                $this->sender->send($message);
                $status = 'sent';
                $error = null;
            } catch (Throwable $e) {
                $status = 'failed';
                $error = $e->getMessage();
                $message->forceFill(['status' => 'failed', 'error_message' => $error])->save();
            }

            $results[] = $invoice->reminders()->create([
                'communication_message_id' => $message->id,
                'channel' => $channel,
                'days_overdue' => $daysOverdue,
                'status' => $status,
                'error_message' => $error,
            ]);
        }

        if (collect($results)->contains('status', 'sent')) {
            $invoice->forceFill(['last_reminder_at' => now(), 'reminders_sent' => $invoice->reminders_sent + 1])->save();
        }

        return $results;
    }

    private function body(Invoice $invoice, int $daysOverdue): string
    {
        $amount = number_format((float) $invoice->amount_due, 2).' '.$invoice->currency;
        $company = BusinessSetting::get('company_name');

        $lines = [
            __('Hello :name,', ['name' => $invoice->customer->name]),
            $daysOverdue > 0
                ? __('Invoice :number for :amount was due on :date (:days days ago).', ['number' => $invoice->number, 'amount' => $amount, 'date' => $invoice->due_date?->format('Y-m-d'), 'days' => $daysOverdue])
                : __('This is a friendly reminder that invoice :number for :amount is due on :date.', ['number' => $invoice->number, 'amount' => $amount, 'date' => $invoice->due_date?->format('Y-m-d')]),
            __('You can view and pay it here: :url', ['url' => $invoice->publicUrl()]),
            __('Thank you, :company', ['company' => $company]),
        ];

        return implode("\n\n", $lines);
    }

    /**
     * Approved WhatsApp template for business-initiated messages, if configured.
     * Expected body variables: {{1}} name, {{2}} invoice number, {{3}} amount, {{4}} due date, {{5}} link.
     *
     * @return array<string, mixed>|null
     */
    private function whatsappTemplate(Invoice $invoice): ?array
    {
        $name = config('services.whatsapp.reminder_template');

        if (! $name) {
            return null;
        }

        return [
            'name' => $name,
            'language' => config('services.whatsapp.reminder_template_language', 'ar'),
            'parameters' => [
                $invoice->customer->name,
                $invoice->number,
                number_format((float) $invoice->amount_due, 2).' '.$invoice->currency,
                (string) $invoice->due_date?->format('Y-m-d'),
                $invoice->publicUrl(),
            ],
        ];
    }

    /** @return array<int, int> */
    private function steps(): array
    {
        return collect(explode(',', (string) BusinessSetting::get('reminder_days')))
            ->map(fn ($d) => (int) trim($d))->filter(fn ($d) => $d > 0)->unique()->sort()->values()->all();
    }

    /** @return array<int, string> */
    private function channels(): array
    {
        return collect(explode(',', (string) BusinessSetting::get('reminder_channels')))
            ->map(fn ($c) => trim($c))->filter(fn ($c) => in_array($c, ['email', 'whatsapp'], true))->values()->all();
    }
}
