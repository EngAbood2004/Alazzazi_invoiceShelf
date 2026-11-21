@php
    $isInvoice = $type === 'invoice';
    $locale = app()->getLocale();
    $dir = config("laraventry.locales.{$locale}.dir", 'rtl');
    $m = fn ($v) => \App\Support\Money::format($v, $document->currency);
    $qty = fn ($q) => rtrim(rtrim((string) $q, '0'), '.');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
<meta charset="UTF-8">
<title>{{ $document->number }}</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
    @page { size: A4; margin: 0; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif; color: #162033; font-size: 12.5px; background: #eef2f7; }
    .sheet { width: 210mm; min-height: 297mm; margin: 0 auto; background: #fff; position: relative; }
    .band { background: #111827; color: #fff; padding: 28px 36px 64px; display: flex; justify-content: space-between; gap: 24px; }
    .band .accent { position: absolute; top: 0; inset-inline: 0; height: 6px; background: linear-gradient(90deg, #2563eb, #60a5fa); }
    .brand { font-size: 22px; font-weight: 800; }
    .company { color: #cbd5e1; font-size: 11.5px; line-height: 1.7; margin-top: 6px; white-space: pre-line; }
    .doc-title { text-align: end; }
    .doc-title h1 { margin: 0; font-size: 30px; letter-spacing: 2px; font-weight: 800; }
    .doc-title .num { color: #93c5fd; font-size: 13px; }
    .summary { margin: -40px 36px 0; background: #fff; border: 1px solid #e7edf5; border-radius: 12px; display: grid; grid-template-columns: repeat(4, 1fr); position: relative; }
    .summary div { padding: 12px 16px; border-inline-end: 1px solid #eef2f7; }
    .summary div:last-child { border-inline-end: 0; }
    .label { color: #718096; font-size: 10px; text-transform: uppercase; letter-spacing: .6px; }
    .value { font-weight: 700; font-size: 13.5px; margin-top: 2px; }
    .value.big { color: #2563eb; font-size: 17px; }
    .parties { display: flex; justify-content: space-between; gap: 24px; padding: 24px 36px 4px; }
    .party-title { color: #2563eb; font-weight: 800; font-size: 10.5px; text-transform: uppercase; letter-spacing: .8px; margin-bottom: 4px; }
    .pill { display: inline-block; padding: 4px 12px; border-radius: 999px; font-weight: 700; font-size: 11px; }
    .pill.paid { background: #dcfce7; color: #15803d; } .pill.partially_paid { background: #fef3c7; color: #b45309; }
    .pill.overdue, .pill.cancelled { background: #fee2e2; color: #b91c1c; } .pill.other { background: #e0e7ff; color: #3730a3; }
    table { width: calc(100% - 72px); margin: 18px 36px 0; border-collapse: collapse; }
    th { background: #eff6ff; color: #1d4ed8; font-size: 10.5px; text-transform: uppercase; letter-spacing: .5px; padding: 9px 10px; text-align: start; }
    td { padding: 10px; border-bottom: 1px solid #eef2f7; vertical-align: top; }
    .num-col { text-align: end; white-space: nowrap; }
    .totals { width: 280px; margin: 16px 36px 0; margin-inline-start: auto; }
    .totals .row { display: flex; justify-content: space-between; padding: 5px 0; }
    .totals .grand { background: #111827; color: #fff; padding: 10px 14px; border-radius: 10px; font-weight: 800; font-size: 15px; margin-top: 6px; }
    .notes { margin: 24px 36px 0; padding: 12px 16px; background: #f8fafc; border-inline-start: 3px solid #2563eb; border-radius: 6px; white-space: pre-line; }
    .footer { position: absolute; bottom: 0; inset-inline: 0; padding: 10px 36px; background: #eff6ff; color: #1d4ed8; font-size: 10.5px; text-align: center; border-top: 3px solid #2563eb; }
    .toolbar { text-align: center; padding: 14px; }
    .toolbar button { font-family: inherit; background: #2563eb; color: #fff; border: 0; border-radius: 8px; padding: 8px 18px; font-weight: 700; cursor: pointer; }
    @media print { body { background: #fff; } .toolbar { display: none; } .sheet { margin: 0; } }
</style>
</head>
<body>
@unless($forPdf)<div class="toolbar"><button onclick="window.print()">{{ __('Print / Save as PDF') }}</button></div>@endunless
<div class="sheet">
    <div class="band">
        <div class="accent"></div>
        <div>
            <div class="brand">{{ $company['company_name'] }}</div>
            <div class="company">{{ trim(implode("\n", array_filter([$company['company_address'], $company['company_phone'], $company['company_email'], $company['company_tax_number'] ? __('Tax number').': '.$company['company_tax_number'] : null]))) }}</div>
        </div>
        <div class="doc-title"><h1>{{ $isInvoice ? __('INVOICE') : __('ESTIMATE') }}</h1><div class="num"># {{ $document->number }}</div></div>
    </div>

    <div class="summary">
        <div><div class="label">{{ __('Number') }}</div><div class="value">{{ $document->number }}</div></div>
        <div><div class="label">{{ __('Date') }}</div><div class="value">{{ $document->issue_date->format('Y-m-d') }}</div></div>
        <div><div class="label">{{ $isInvoice ? __('Due date') : __('Valid until') }}</div><div class="value">{{ ($isInvoice ? $document->due_date : $document->expiry_date)?->format('Y-m-d') ?? '—' }}</div></div>
        <div><div class="label">{{ $isInvoice ? __('Balance due') : __('Total') }}</div><div class="value big">{{ $m($isInvoice ? $document->amount_due : $document->total) }}</div></div>
    </div>

    <div class="parties">
        <div>
            <div class="party-title">{{ __('Bill to') }}</div>
            <div style="font-weight:700">{{ $document->customer->name }}</div>
            <div style="color:#4a5568;line-height:1.7">{{ $document->customer->company_name }}@if($document->customer->address)<br>{{ $document->customer->address }} {{ $document->customer->city }}@endif @if($document->customer->tax_number)<br>{{ __('Tax number') }}: {{ $document->customer->tax_number }}@endif</div>
        </div>
        @if($isInvoice)
            @php($status = $document->displayStatus())
            <div><span class="pill {{ in_array($status, ['paid','partially_paid','overdue','cancelled']) ? $status : 'other' }}">@include('invoicing.partials.status-text', ['status' => $status])</span></div>
        @endif
    </div>

    <table>
        <thead><tr><th>{{ __('Description') }}</th><th class="num-col">{{ __('Qty') }}</th><th class="num-col">{{ __('Unit price') }}</th><th class="num-col">{{ __('Tax') }}</th><th class="num-col">{{ __('Amount') }}</th></tr></thead>
        <tbody>
        @foreach($document->items as $item)
            <tr><td>{{ $item->description }}</td><td class="num-col">{{ $qty($item->quantity) }}</td><td class="num-col">{{ $m($item->unit_price) }}</td><td class="num-col">{{ (float) $item->tax_rate > 0 ? $qty($item->tax_rate).'%' : '—' }}</td><td class="num-col">{{ $m($item->line_total) }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="row"><span>{{ __('Subtotal') }}</span><span>{{ $m($document->subtotal) }}</span></div>
        @if((float) $document->discount_total > 0)<div class="row"><span>{{ __('Discount') }}</span><span>- {{ $m($document->discount_total) }}</span></div>@endif
        <div class="row"><span>{{ __('Tax') }}</span><span>{{ $m($document->tax_total) }}</span></div>
        @if($isInvoice && (float) $document->amount_paid > 0)<div class="row"><span>{{ __('Paid') }}</span><span>{{ $m($document->amount_paid) }}</span></div>@endif
        <div class="row grand"><span>{{ __('Total') }}</span><span>{{ $m($document->total) }}</span></div>
    </div>

    @if($document->notes || $document->terms)
        <div class="notes">@if($document->notes)<strong>{{ __('Notes') }}</strong><br>{{ $document->notes }}@endif @if($document->terms)<br><strong>{{ __('Terms and conditions') }}</strong><br>{{ $document->terms }}@endif</div>
    @endif

    <div class="footer">{{ __('Thank you for your business') }} · {{ $company['company_name'] }}</div>
</div>
@unless($forPdf)@if(request()->routeIs('*.pdf'))<script>window.addEventListener('load', () => setTimeout(() => window.print(), 400));</script>@endif @endunless
</body>
</html>
