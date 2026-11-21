@extends('layouts.app')
@section('title', __('Customer communication'))
@section('content')
<div class="mb-4">
    <div class="eyebrow">{{ __('Communication') }}</div>
    <h1 class="page-title">{{ __('Customer communication') }}</h1>
    <p class="text-muted mb-0">{{ __('Manage email, WhatsApp, SMS, and internal conversations in one place.') }}</p>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-send me-2 text-primary"></i>{{ __('New message') }}</h5>
            <form method="POST" action="{{ route('messages.store') }}" id="communicationForm">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="channel">{{ __('Channel') }}</label>
                    <select class="form-select" id="channel" name="channel">
                        <option value="internal" @selected(old('channel', $selectedChannel ?? 'internal') === 'internal')>{{ __('Internal message') }}</option>
                        <option value="email" @selected(old('channel', $selectedChannel) === 'email')>{{ __('Email') }}</option>
                        <option value="whatsapp" @selected(old('channel', $selectedChannel) === 'whatsapp')>WhatsApp</option>
                        <option value="sms" @selected(old('channel', $selectedChannel) === 'sms')>SMS</option>
                    </select>
                    @error('channel')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="mb-3" id="internalRecipient">
                    <label class="form-label" for="recipient_id">{{ __('Recipient') }}</label>
                    <select class="form-select" id="recipient_id" name="recipient_id">
                        <option value="">{{ __('Choose a recipient') }}</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('recipient_id') == $user->id)>{{ $user->name }} — {{ $user->email }}</option>
                        @endforeach
                    </select>
                    @error('recipient_id')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="mb-3 d-none" id="externalRecipient">
                    <label class="form-label" for="contact_address">{{ __('Email address or phone number') }}</label>
                    <input class="form-control" id="contact_address" name="contact_address" maxlength="255" value="{{ old('contact_address', $selectedContact) }}" placeholder="name@example.com or +15551234567">
                    <small class="text-muted">{{ __('Use an international number in E.164 format, including + and country code.') }}</small>
                    @error('contact_address')<small class="text-danger d-block">{{ $message }}</small>@enderror
                </div>
                <div class="mb-3" id="subjectField">
                    <label class="form-label" for="subject">{{ __('Subject') }}</label>
                    <input class="form-control" id="subject" name="subject" maxlength="160" value="{{ old('subject', $replySubject) }}">
                    @error('subject')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="body">{{ __('Message') }}</label>
                    <textarea class="form-control" id="body" name="body" rows="5" maxlength="5000" required>{{ old('body') }}</textarea>
                    @error('body')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <button class="btn btn-primary w-100"><i class="bi bi-send me-1"></i>{{ __('Send message') }}</button>
            </form>
            <div class="small text-muted mt-3">
                <i class="bi bi-info-circle me-1"></i>{{ __('WhatsApp free-form messages can only be sent during the 24-hour customer service window.') }}
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item"><a class="nav-link {{ !$selectedChannel ? 'active' : '' }}" href="{{ route('messages.index') }}">{{ __('Internal') }} <span class="badge text-bg-light">{{ $messages->total() }}</span></a></li>
                    <li class="nav-item"><a class="nav-link {{ $selectedChannel === 'email' ? 'active' : '' }}" href="{{ route('messages.index', ['channel' => 'email']) }}">{{ __('Email') }}</a></li>
                    <li class="nav-item"><a class="nav-link {{ $selectedChannel === 'whatsapp' ? 'active' : '' }}" href="{{ route('messages.index', ['channel' => 'whatsapp']) }}">WhatsApp</a></li>
                    <li class="nav-item"><a class="nav-link {{ $selectedChannel === 'sms' ? 'active' : '' }}" href="{{ route('messages.index', ['channel' => 'sms']) }}">SMS</a></li>
                </ul>
            </div>
            <div class="p-3">
                @if(!$selectedChannel)
                    @forelse($messages as $message)
                        <div class="border rounded-3 p-3 mb-3">
                            <div class="d-flex justify-content-between gap-2">
                                <strong>{{ $message->subject }}</strong>
                                <small class="text-muted">{{ $message->created_at->format('Y-m-d H:i') }}</small>
                            </div>
                            <div class="small text-muted my-2">{{ __('From') }}: {{ $message->sender->name }} · {{ __('To') }}: {{ $message->recipient->name }}</div>
                            <p class="mb-0" style="white-space:pre-wrap">{{ $message->body }}</p>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">{{ __('No messages yet.') }}</div>
                    @endforelse
                    {{ $messages->links() }}
                @else
                    <div class="row g-3">
                        <div class="col-md-4">
                            <h6 class="fw-bold">{{ __('Conversations') }}</h6>
                            @forelse($contacts->where('channel', $selectedChannel) as $contact)
                                <a class="d-block border rounded-3 p-3 mb-2 text-decoration-none {{ $selectedContact === $contact->contact_address ? 'border-primary bg-light' : 'text-reset' }}"
                                   href="{{ route('messages.index', ['channel' => $selectedChannel, 'contact' => $contact->contact_address]) }}">
                                    <strong class="d-block text-truncate">{{ $contact->contact_address }}</strong>
                                    <small class="text-muted">{{ Str::limit($contact->body, 55) }}</small>
                                    <small class="d-block text-muted mt-1">{{ $contact->created_at->format('Y-m-d H:i') }}</small>
                                </a>
                            @empty
                                <p class="small text-muted">{{ __('No conversations yet.') }}</p>
                            @endforelse
                        </div>
                        <div class="col-md-8">
                            @if($selectedContact)
                                <h6 class="fw-bold border-bottom pb-3">{{ $selectedContact }}</h6>
                                <div class="communication-thread">
                                    @forelse($conversation as $message)
                                        <div class="border rounded-3 p-3 mb-3 {{ $message->direction === 'outbound' ? 'ms-md-4 bg-light' : 'me-md-4' }}">
                                            <div class="d-flex justify-content-between gap-2 small text-muted mb-2">
                                                <span>{{ $message->direction === 'outbound' ? __('Sent') : __('Received') }} · {{ __($message->status) }}</span>
                                                <span>{{ $message->created_at->format('Y-m-d H:i') }}</span>
                                            </div>
                                            @if($message->subject)<strong class="d-block mb-2">{{ $message->subject }}</strong>@endif
                                            <p class="mb-0" style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $message->body }}</p>
                                            @if($message->error_message)<small class="text-danger d-block mt-2">{{ __('Delivery error') }}: {{ $message->error_message }}</small>@endif
                                        </div>
                                    @empty
                                        <p class="text-muted">{{ __('No messages yet.') }}</p>
                                    @endforelse
                                </div>
                            @else
                                <div class="text-center text-muted py-5">{{ __('Choose a conversation or start a new message.') }}</div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(() => {
    const channel = document.getElementById('channel');
    const internalRecipient = document.getElementById('internalRecipient');
    const externalRecipient = document.getElementById('externalRecipient');
    const recipient = document.getElementById('recipient_id');
    const contact = document.getElementById('contact_address');
    const subject = document.getElementById('subject');

    function updateFields() {
        const internal = channel.value === 'internal';
        internalRecipient.classList.toggle('d-none', !internal);
        externalRecipient.classList.toggle('d-none', internal);
        recipient.required = internal;
        recipient.disabled = !internal;
        contact.required = !internal;
        contact.disabled = internal;
        subject.required = internal || channel.value === 'email';
    }

    channel.addEventListener('change', updateFields);
    updateFields();
})();
</script>
@endsection
