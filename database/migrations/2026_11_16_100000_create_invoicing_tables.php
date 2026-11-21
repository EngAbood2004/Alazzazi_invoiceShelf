<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invoicing module: customers, taxes, invoices, estimates, invoice payments,
 * expenses, recurring invoices, reminders and business settings.
 * Amounts are stored as decimal(15,2), like the existing sales table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 40)->nullable();
            $table->string('whatsapp', 40)->nullable();
            $table->string('tax_number', 60)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('country', 120)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('rate', 6, 3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('estimates', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('discount_type', 10)->default('fixed');
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('public_token', 64)->unique();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('frequency', 20);
            $table->date('next_run_date')->index();
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('max_runs')->nullable();
            $table->unsignedInteger('runs')->default(0);
            $table->unsignedSmallInteger('due_in_days')->default(15);
            $table->boolean('auto_send')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('currency', 3)->default('USD');
            $table->json('items');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('estimate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recurring_invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->date('issue_date')->index();
            $table->date('due_date')->nullable()->index();
            $table->string('currency', 3)->default('USD');
            $table->string('discount_type', 10)->default('fixed');
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('amount_due', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('public_token', 64)->unique();
            $table->boolean('stock_deducted')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('last_reminder_at')->nullable();
            $table->unsignedSmallInteger('reminders_sent')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        foreach (['invoice', 'estimate'] as $parent) {
            Schema::create("{$parent}_items", function (Blueprint $table) use ($parent) {
                $table->id();
                $table->foreignId("{$parent}_id")->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('tax_id')->nullable()->constrained()->nullOnDelete();
                $table->string('description');
                $table->decimal('quantity', 12, 3);
                $table->decimal('unit_price', 15, 2);
                $table->decimal('tax_rate', 6, 3)->default(0);
                $table->decimal('line_subtotal', 15, 2);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('line_total', 15, 2);
                $table->unsignedSmallInteger('position')->default(0);
                $table->timestamps();
            });
        }

        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('paid_on')->index();
            $table->string('reference')->nullable();
            $table->string('gateway', 30)->nullable();
            $table->string('gateway_reference')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('spent_on')->index();
            $table->string('description')->nullable();
            $table->string('receipt_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('invoice_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('communication_message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20);
            $table->smallInteger('days_overdue');
            $table->string('status', 20);
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index(['invoice_id', 'channel', 'days_overdue']);
        });
    }

    public function down(): void
    {
        foreach (['invoice_reminders', 'expenses', 'expense_categories', 'invoice_payments', 'estimate_items', 'invoice_items', 'invoices', 'recurring_invoices', 'estimates', 'taxes', 'customers', 'business_settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
