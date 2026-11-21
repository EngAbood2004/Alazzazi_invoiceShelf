<?php

namespace App\Http\Controllers\Invoicing;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Models\BusinessSetting;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? today();

        $query = Expense::query()->with(['category', 'supplier'])
            ->whereDate('spent_on', '>=', $from->toDateString())->whereDate('spent_on', '<=', $to->toDateString())
            ->when($request->integer('category'), fn ($q, $c) => $q->where('expense_category_id', $c));

        $byCategory = (clone $query)->selectRaw('expense_category_id, SUM(amount) as total')
            ->groupBy('expense_category_id')->with('category')->orderByDesc('total')->get();

        return view('invoicing.expenses.index', [
            'expenses' => $query->latest('spent_on')->latest('id')->paginate(25)->withQueryString(),
            'total' => (float) $byCategory->sum('total'),
            'byCategory' => $byCategory,
            'categories' => ExpenseCategory::query()->orderBy('name')->get(),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function create(): View
    {
        return view('invoicing.expenses.form', $this->formData(new Expense(['spent_on' => today(), 'currency' => BusinessSetting::get('currency')])));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        Expense::create($this->payload($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('expenses.index')->with('success', __('Expense saved.'));
    }

    public function edit(Expense $expense): View
    {
        return view('invoicing.expenses.form', $this->formData($expense));
    }

    public function update(StoreExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update($this->payload($request, $expense));

        return redirect()->route('expenses.index')->with('success', __('Expense saved.'));
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        if ($expense->receipt_path) {
            Storage::disk('local')->delete($expense->receipt_path);
        }
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', __('Expense deleted.'));
    }

    public function receipt(Expense $expense)
    {
        abort_unless($expense->receipt_path && Storage::disk('local')->exists($expense->receipt_path), 404);

        return Storage::disk('local')->response($expense->receipt_path);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(StoreExpenseRequest $request, ?Expense $expense = null): array
    {
        $data = $request->safe()->except(['new_category', 'receipt']);

        if ($request->filled('new_category')) {
            $data['expense_category_id'] = ExpenseCategory::firstOrCreate(['name' => trim($request->string('new_category'))])->id;
        }

        if ($request->hasFile('receipt')) {
            if ($expense?->receipt_path) {
                Storage::disk('local')->delete($expense->receipt_path);
            }
            $data['receipt_path'] = $request->file('receipt')->store('expense-receipts', 'local');
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Expense $expense): array
    {
        return [
            'expense' => $expense,
            'categories' => ExpenseCategory::query()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'paymentMethods' => PaymentMethod::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
        ];
    }
}
