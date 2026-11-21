<?php

namespace App\Console\Commands;

use App\Models\RecurringInvoice;
use App\Services\Invoicing\InvoiceService;
use Illuminate\Console\Command;
use Throwable;

class RunRecurringInvoices extends Command
{
    protected $signature = 'invoices:run-recurring';

    protected $description = 'Create the invoices of every recurring schedule that is due';

    public function handle(InvoiceService $invoices): int
    {
        $created = 0;

        RecurringInvoice::query()->where('is_active', true)->whereDate('next_run_date', '<=', today())->each(function (RecurringInvoice $schedule) use ($invoices, &$created) {
            // Catch up on missed runs (e.g. the server was off), one invoice per period.
            while ($schedule->is_active && $schedule->next_run_date->lte(today())) {
                try {
                    if (! $invoices->runRecurring($schedule)) {
                        break;
                    }
                    $created++;
                } catch (Throwable $e) {
                    $this->error("Schedule #{$schedule->id}: {$e->getMessage()}");
                    break;
                }
                $schedule->refresh();
            }
        });

        $this->info("Recurring invoices created: {$created}.");

        return self::SUCCESS;
    }
}
