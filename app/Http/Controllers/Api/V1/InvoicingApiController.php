<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordInvoicePaymentRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\InvoiceResource;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Tax;
use App\Services\Analytics\AiInsightService;
use App\Services\Analytics\FinancialAnalytics;
use App\Services\Invoicing\InvoiceService;
use App\Services\Reminders\InvoiceReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * REST API v1 used by the Flutter app. Same services and validation as the
 * web screens, so both clients follow identical business rules.
 */
class InvoicingApiController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function dashboard(Request $request, FinancialAnalytics $analytics, AiInsightService $ai): JsonResponse
    {
        $summary = $analytics->summary();

        return response()->json([
            'currency' => BusinessSetting::get('currency'),
            'summary' => $summary,
            'insights' => $ai->insights($summary, $request->string('locale')->toString() ?: null),
        ]);
    }

    public function customers(Request $request): AnonymousResourceCollection
    {
        return CustomerResource::collection(Customer::query()
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('company_name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")))
            ->withCount('invoices')
            ->withSum(['invoices as total_due' => fn ($q) => $q->open()], 'amount_due')
            ->orderBy('name')->paginate(min(100, $request->integer('per_page', 30))));
    }

    public function storeCustomer(StoreCustomerRequest $request): CustomerResource
    {
        return new CustomerResource(Customer::create($request->validated() + ['created_by' => $request->user()->id]));
    }

    public function customer(Customer $customer): JsonResponse
    {
        return response()->json([
            'data' => new CustomerResource($customer),
            'invoices' => InvoiceResource::collection($customer->invoices()->latest('issue_date')->limit(50)->get()),
            'stats' => [
                'invoiced' => (float) $customer->invoices()->issued()->sum('total'),
                'due' => (float) $customer->invoices()->open()->sum('amount_due'),
                'overdue' => (float) $customer->invoices()->overdue()->sum('amount_due'),
            ],
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->string('status')->toString();

        return InvoiceResource::collection(Invoice::query()->with('customer')
            ->when($status === 'overdue', fn ($q) => $q->overdue())
            ->when($status === 'unpaid', fn ($q) => $q->open())
            ->when(in_array($status, Invoice::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->when($request->integer('customer_id'), fn ($q, $id) => $q->where('customer_id', $id))
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where('number', 'like', "%{$s}%"))
            ->latest('issue_date')->latest('id')
            ->paginate(min(100, $request->integer('per_page', 30))));
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        return new InvoiceResource($invoice->load(['customer', 'items', 'payments.paymentMethod']));
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->invoices->create($request->validated(), $request->user());

        if ($request->boolean('send')) {
            $this->invoices->markSent($invoice, $request->user());
        }

        return (new InvoiceResource($invoice->fresh(['customer', 'items'])))->response()->setStatusCode(201);
    }

    public function send(Request $request, Invoice $invoice): InvoiceResource
    {
        return new InvoiceResource($this->invoices->markSent($invoice, $request->user())->load('customer'));
    }

    public function recordPayment(RecordInvoicePaymentRequest $request, Invoice $invoice): InvoiceResource
    {
        $this->invoices->recordPayment($invoice, $request->validated(), $request->user());

        return new InvoiceResource($invoice->fresh(['customer', 'items', 'payments.paymentMethod']));
    }

    public function remind(Request $request, Invoice $invoice, InvoiceReminderService $reminders): JsonResponse
    {
        $results = $reminders->sendNow($invoice, $request->user());

        return response()->json(['data' => collect($results)->map(fn ($r) => $r->only(['channel', 'status', 'error_message']))]);
    }

    public function lookups(): JsonResponse
    {
        return response()->json([
            'currency' => BusinessSetting::get('currency'),
            'invoice_due_days' => (int) BusinessSetting::get('invoice_due_days'),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'selling_price']),
            'taxes' => Tax::query()->where('is_active', true)->get(['id', 'name', 'rate']),
            'payment_methods' => PaymentMethod::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }
}
