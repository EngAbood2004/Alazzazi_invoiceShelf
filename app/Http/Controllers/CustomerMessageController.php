<?php

namespace App\Http\Controllers;

use App\Mail\CustomerMessageSent;
use App\Models\CommunicationMessage;
use App\Models\CustomerMessage;
use App\Models\User;
use App\Services\Communication\ChannelMessageSender;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class CustomerMessageController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'channel' => ['nullable', 'in:email,whatsapp,sms'],
            'contact' => ['nullable', 'string', 'max:255'],
        ]);
        $userId = auth()->id();
        $messages = CustomerMessage::with(['sender:id,name,email', 'recipient:id,name,email'])
            ->where(fn ($q) => $q->where('sender_id', $userId)->orWhere('recipient_id', $userId))
            ->latest()->paginate(15);
        $users = User::where('id', '!=', $userId)->orderBy('name')->get(['id', 'name', 'email']);
        CustomerMessage::where('recipient_id', $userId)->whereNull('read_at')->update(['read_at' => now()]);
        $latestContactIds = CommunicationMessage::query()
            ->selectRaw('MAX(id)')
            ->groupBy('channel', 'contact_address');
        $contacts = CommunicationMessage::whereIn('id', $latestContactIds)
            ->latest('id')
            ->limit(50)
            ->get();
        $selectedChannel = $filters['channel'] ?? null;
        $selectedContact = $filters['contact'] ?? null;
        $conversation = collect();
        $replySubject = null;

        if ($selectedChannel && $selectedContact) {
            $conversation = CommunicationMessage::where('channel', $selectedChannel)
                ->where('contact_address', $selectedContact)
                ->oldest()
                ->get();
            $replySubject = $conversation->last()?->subject;

            CommunicationMessage::where('channel', $selectedChannel)
                ->where('contact_address', $selectedContact)
                ->where('direction', 'inbound')
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return view('messages.index', compact(
            'messages',
            'users',
            'contacts',
            'conversation',
            'selectedChannel',
            'selectedContact',
            'replySubject',
        ));
    }

    public function store(Request $request, ChannelMessageSender $sender): RedirectResponse
    {
        if ($request->input('channel', 'internal') !== 'internal') {
            return $this->sendExternalMessage($request, $sender);
        }

        $data = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id', 'different:sender_id'],
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:10000'],
        ]);
        abort_if((int) $data['recipient_id'] === (int) auth()->id(), 422, __('You cannot message yourself.'));
        $message = CustomerMessage::create([
            'sender_id' => auth()->id(),
            ...$data,
        ])->load(['sender', 'recipient']);

        Mail::to($message->recipient->email)->send(new CustomerMessageSent($message));

        return redirect()->route('messages.index')->with('success', __('Message sent successfully.'));
    }

    private function sendExternalMessage(Request $request, ChannelMessageSender $sender): RedirectResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'in:email,whatsapp,sms'],
            'contact_address' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:'.match ($request->input('channel')) {
                'whatsapp' => 4096,
                'sms' => 1600,
                default => 5000,
            }],
        ]);

        if ($data['channel'] === 'email') {
            validator($data, ['contact_address' => ['required', 'email:rfc', 'max:255']])->validate();
        } else {
            validator($data, ['contact_address' => ['required', 'regex:/^\+[1-9]\d{7,14}$/']])->validate();
        }

        $message = CommunicationMessage::create([
            'operator_id' => auth()->id(),
            'channel' => $data['channel'],
            'direction' => 'outbound',
            'contact_address' => $data['contact_address'],
            'subject' => ($data['subject'] ?? null) ?: null,
            'body' => $data['body'],
            'status' => 'pending',
        ]);

        try {
            $sender->send($message);
        } catch (ConnectionException|TransportExceptionInterface|\Illuminate\Http\Client\RequestException|RuntimeException $exception) {
            $message->forceFill([
                'status' => 'failed',
                'error_message' => 'The provider rejected the message or could not deliver it. Check the application log for details.',
            ])->save();

            Log::error('Communication message delivery failed.', [
                'communication_message_id' => $message->id,
                'channel' => $message->channel,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            return redirect()->route('messages.index', [
                'channel' => $message->channel,
                'contact' => $message->contact_address,
            ])->with('error', __('The message could not be sent. Check channel configuration and the saved message status.'));
        }

        return redirect()->route('messages.index', [
            'channel' => $message->channel,
            'contact' => $message->contact_address,
        ])->with('success', __('Message sent successfully.'));
    }
}
