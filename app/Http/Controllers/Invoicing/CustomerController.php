<?php

namespace App\Http\Controllers\Invoicing;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Models\BusinessSetting;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")->orWhere('company_name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")))
            ->withCount('invoices')
            ->withSum(['invoices as total_due' => fn ($q) => $q->open()], 'amount_due')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('invoicing.customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('invoicing.customers.form', ['customer' => new Customer(['currency' => BusinessSetting::get('currency'), 'is_active' => true])]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated() + ['created_by' => $request->user()->id]);

        return redirect()->route('customers.show', $customer)->with('success', __('Customer saved.'));
    }

    public function show(Customer $customer): View
    {
        $customer->load(['invoices' => fn ($q) => $q->latest('issue_date')->limit(50), 'estimates' => fn ($q) => $q->latest('issue_date')->limit(20)]);

        $stats = [
            'invoiced' => (float) $customer->invoices()->issued()->sum('total'),
            'paid' => (float) $customer->invoices()->issued()->sum('amount_paid'),
            'due' => (float) $customer->invoices()->open()->sum('amount_due'),
            'overdue' => (float) $customer->invoices()->overdue()->sum('amount_due'),
        ];

        return view('invoicing.customers.show', compact('customer', 'stats'));
    }

    public function edit(Customer $customer): View
    {
        return view('invoicing.customers.form', compact('customer'));
    }

    public function update(StoreCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('customers.show', $customer)->with('success', __('Customer saved.'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->invoices()->exists() || $customer->estimates()->exists()) {
            return back()->with('error', __('This customer has documents; deactivate it instead.'));
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('success', __('Customer deleted.'));
    }
}
