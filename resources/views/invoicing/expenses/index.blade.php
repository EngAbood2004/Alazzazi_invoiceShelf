@extends('layouts.app')
@section('title', __('Expenses'))
@section('content')
<div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
    <div><div class="eyebrow">{{ __('Invoicing') }}</div><h1 class="page-title">{{ __('Expenses') }}</h1></div>
    <a href="{{ route('expenses.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>{{ __('New expense') }}</a>
</div>
<form class="card mb-3" method="GET"><div class="card-body row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label small">{{ __('From') }}</label><input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label small">{{ __('To') }}</label><input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label small">{{ __('Category') }}</label><select name="category" class="form-select"><option value="">{{ __('All') }}</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected((int) request('category') === $c->id)>{{ $c->name }}</option>@endforeach</select></div>
    <div class="col-md-3"><button class="btn btn-light border w-100"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button></div>
</div></form>
<div class="row g-3">
    <div class="col-lg-8"><div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Category') }}</th><th>{{ __('Description') }}</th><th>{{ __('Supplier') }}</th><th class="text-end">{{ __('Amount') }}</th><th></th></tr></thead>
        <tbody>
        @forelse($expenses as $expense)
            <tr><td>{{ $expense->spent_on->format('Y-m-d') }}</td><td><span class="badge text-bg-light border">{{ $expense->category->name }}</span></td><td>{{ $expense->description }}@if($expense->receipt_path) <a href="{{ route('expenses.receipt', $expense) }}" target="_blank" title="{{ __('Receipt') }}"><i class="bi bi-paperclip"></i></a>@endif</td><td>{{ $expense->supplier?->name }}</td><td class="text-end fw-semibold">{{ \App\Support\Money::format($expense->amount, $expense->currency) }}</td>
                <td class="text-end text-nowrap"><a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a><form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="d-inline" onsubmit="return confirm(@json(__('Delete this expense?')))">@csrf @method('DELETE')<button class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button></form></td></tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-5">{{ __('No expenses in this period.') }}</td></tr>
        @endforelse
        </tbody>
    </table></div>@if($expenses->hasPages())<div class="card-footer bg-white">{{ $expenses->links() }}</div>@endif</div></div>
    <div class="col-lg-4"><div class="card"><div class="card-header d-flex justify-content-between"><span class="fw-bold">{{ __('By category') }}</span><span class="fw-bold">{{ \App\Support\Money::format($total) }}</span></div><div class="card-body">
        @forelse($byCategory as $row)
            @php($pct = $total > 0 ? round($row->total / $total * 100) : 0)
            <div class="mb-3"><div class="d-flex justify-content-between small"><span>{{ $row->category->name }}</span><span class="text-muted">{{ $pct }}% · {{ \App\Support\Money::format($row->total) }}</span></div><div class="progress" style="height:6px"><div class="progress-bar" style="width: {{ $pct }}%"></div></div></div>
        @empty
            <p class="text-muted small mb-0">{{ __('No data yet') }}</p>
        @endforelse
    </div></div></div>
</div>
@endsection
