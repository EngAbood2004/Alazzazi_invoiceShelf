<h1>{{ $customerMessage->subject }}</h1>

<p>{{ __('Hello :name,', ['name' => $customerMessage->recipient->name]) }}</p>
<p>{{ __(':name sent you a message through :app.', ['name' => $customerMessage->sender->name, 'app' => config('app.name')]) }}</p>

<p style="white-space: pre-wrap">{{ $customerMessage->body }}</p>

<p>{{ __('Sign in to your account to reply.') }}</p>
