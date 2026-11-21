@php
    $map = [
        'draft' => 'secondary', 'sent' => 'info', 'viewed' => 'primary', 'partially_paid' => 'warning',
        'paid' => 'success', 'cancelled' => 'dark', 'overdue' => 'danger',
        'accepted' => 'success', 'rejected' => 'danger', 'converted' => 'primary', 'expired' => 'warning',
    ];
    $labels = [
        'draft' => __('Draft'), 'sent' => __('Sent'), 'viewed' => __('Viewed'), 'partially_paid' => __('Partially paid'),
        'paid' => __('Paid'), 'cancelled' => __('Cancelled'), 'overdue' => __('Overdue'),
        'accepted' => __('Accepted'), 'rejected' => __('Rejected'), 'converted' => __('Converted'), 'expired' => __('Expired'),
    ];
@endphp
<span class="badge rounded-pill text-bg-{{ $map[$status] ?? 'secondary' }}">{{ $labels[$status] ?? $status }}</span>
