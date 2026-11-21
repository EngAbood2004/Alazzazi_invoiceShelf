@php
    $locale = app()->getLocale();
    $m = fn ($v) => \App\Support\Money::format($v, $invoice->currency);
    $status = $invoice->displayStatus();
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ config("laraventry.locales.{$locale}.dir", 'rtl') }}">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $invoice->number }} · {{ $company['company_name'] }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap{{ $locale === 'ar' ? '.rtl' : '' }}.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>body{font-family:'Cairo',system-ui,sans-serif;background:#f4f7fb;color:#162033}.hero{background:linear-gradient(135deg,#111827,#1e3a8a);color:#fff;border-radius:20px}.card{border:1px solid #e7edf5;border-radius:16px}</style>
</head>
<body>
<div class="container py-4" style="max-width:860px">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-warning">{{ session('error') }}</div>@endif

    <div class="hero p-4 p-md-5 mb-4">
        <div class="d-flex justify-content-between flex-wrap gap-3">
            <div><div class="opacity-75 small">{{ $company['company_name'] }}</div><h1 class="h3 fw-bold mb-1">{{ __('Invoice') }} {{ $invoice->number }}</h1><div class="opacity-75">{{ __('For') }} {{ $invoice->customer->name }}</div></div>
            <div class="text-end"><div class="opacity-75 small">{{ __('Balance due') }}</div><div class="display-6 fw-bold">{{ $m($invoice->amount_due) }}</div><div class="small opacity-75">{{ __('Due date') }}: {{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</div></div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4">
            @if($canPayOnline)
                <form method="POST" action="{{ route('public.invoices.pay', $invoice->public_token) }}">@csrf<button class="btn btn-light btn-lg fw-bold px-4"><i class="bi bi-credit-card me-2"></i>{{ __('Pay :amount online', ['amount' => $m($invoice->amount_due)]) }}</button></form>
            @elseif($status === 'paid')
                <span class="btn btn-success btn-lg disabled"><i class="bi bi-check-circle me-2"></i>{{ __('Paid in full') }}</span>
            @endif
            <a href="{{ route('public.invoices.pdf', $invoice->public_token) }}" target="_blank" class="btn btn-outline-light btn-lg"><i class="bi bi-download me-2"></i>{{ __('Download') }}</a>
        </div>
        @if($canPayOnline)<div class="small opacity-75 mt-2"><i class="bi bi-shield-lock me-1"></i>{{ __('Secure payment by Lahza: cards, Apple Pay and local wallets.') }}</div>@endif
    </div>

    <div class="card mb-3"><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>{{ __('Description') }}</th><th class="text-end">{{ __('Qty') }}</th><th class="text-end">{{ __('Amount') }}</th></tr></thead>
        <tbody>@foreach($invoice->items as $item)<tr><td>{{ $item->description }}</td><td class="text-end">{{ rtrim(rtrim((string) $item->quantity, '0'), '.') }}</td><td class="text-end">{{ $m($item->line_total) }}</td></tr>@endforeach</tbody>
        <tfoot>
            <tr><th colspan="2" class="text-end">{{ __('Total') }}</th><th class="text-end">{{ $m($invoice->total) }}</th></tr>
            @if((float) $invoice->amount_paid > 0)<tr><th colspan="2" class="text-end text-success">{{ __('Paid') }}</th><th class="text-end text-success">{{ $m($invoice->amount_paid) }}</th></tr>@endif
        </tfoot>
    </table></div></div>
    @if($invoice->notes)<div class="card"><div class="card-body small">{{ $invoice->notes }}</div></div>@endif
    <p class="text-center text-muted small mt-4">{{ $company['company_name'] }} @if($company['company_phone'])· <span dir="ltr">{{ $company['company_phone'] }}</span>@endif @if($company['company_email'])· {{ $company['company_email'] }}@endif</p>
</div>
</body>
</html>
