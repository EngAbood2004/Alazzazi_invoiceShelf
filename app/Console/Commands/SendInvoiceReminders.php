<?php

namespace App\Console\Commands;

use App\Services\Reminders\InvoiceReminderService;
use Illuminate\Console\Command;

class SendInvoiceReminders extends Command
{
    protected $signature = 'invoices:send-reminders';

    protected $description = 'Send email/WhatsApp reminders for overdue invoices according to the reminder schedule';

    public function handle(InvoiceReminderService $reminders): int
    {
        $result = $reminders->sendDue();

        $this->info("Reminders: {$result['invoices']} invoice(s), {$result['sent']} sent, {$result['failed']} failed.");

        return self::SUCCESS;
    }
}
