<?php

namespace App\Services\Communication;

use App\Mail\ExternalCommunicationMail;
use App\Models\CommunicationMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ChannelMessageSender
{
    /**
     * @throws ConnectionException
     * @throws TransportExceptionInterface
     */
    public function send(CommunicationMessage $message): void
    {
        $providerMessageId = match ($message->channel) {
            'email' => $this->sendEmail($message),
            'whatsapp' => $this->sendWhatsApp($message),
            'sms' => $this->sendSms($message),
            default => throw new RuntimeException('Unsupported communication channel.'),
        };

        $message->forceFill([
            'status' => 'sent',
            'provider_message_id' => $providerMessageId,
            'sent_at' => now(),
            'error_message' => null,
        ])->save();
    }

    private function sendEmail(CommunicationMessage $message): ?string
    {
        if (config('mail.default') !== 'smtp') {
            throw new RuntimeException('Set MAIL_MAILER=smtp to send customer emails through Gmail.');
        }

        $this->requireConfig('mail.mailers.smtp.host', 'Set the Gmail SMTP settings before sending email.');
        $this->requireConfig('mail.mailers.smtp.username', 'Set MAIL_USERNAME before sending email.');
        $this->requireConfig('mail.mailers.smtp.password', 'Set MAIL_PASSWORD to a Gmail App Password before sending email.');
        $this->requireConfig('mail.from.address', 'Set MAIL_FROM_ADDRESS before sending email.');

        Mail::to($message->contact_address)->send(new ExternalCommunicationMail($message));

        return null;
    }

    private function sendWhatsApp(CommunicationMessage $message): ?string
    {
        $token = $this->requireConfig('services.whatsapp.access_token', 'Set WHATSAPP_ACCESS_TOKEN before sending WhatsApp messages.');
        $phoneNumberId = $this->requireConfig('services.whatsapp.phone_number_id', 'Set WHATSAPP_PHONE_NUMBER_ID before sending WhatsApp messages.');
        $version = config('services.whatsapp.api_version');

<<<<<<< HEAD
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => ltrim($message->contact_address, '+'),
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message->body,
            ],
        ];

        // Business-initiated messages (e.g. payment reminders) outside the 24h
        // customer-service window must use an approved template.
        $template = $message->metadata['whatsapp_template'] ?? null;
        if (is_array($template) && ! empty($template['name'])) {
            unset($payload['text']);
            $payload['type'] = 'template';
            $payload['template'] = [
                'name' => $template['name'],
                'language' => ['code' => $template['language'] ?? 'ar'],
                'components' => [[
                    'type' => 'body',
                    'parameters' => array_map(
                        fn ($value) => ['type' => 'text', 'text' => (string) $value],
                        $template['parameters'] ?? [],
                    ),
                ]],
            ];
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", $payload)
=======
        $response = Http::withToken($token)
            ->acceptJson()
            ->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => ltrim($message->contact_address, '+'),
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message->body,
                ],
            ])
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
            ->throw();

        $messageId = $response->json('messages.0.id');

        if (! is_string($messageId) || $messageId === '') {
            throw new RuntimeException('WhatsApp accepted no message ID; check the Cloud API response and phone number configuration.');
        }

        return $messageId;
    }

    private function sendSms(CommunicationMessage $message): ?string
    {
        $accountSid = $this->requireConfig('services.twilio.account_sid', 'Set TWILIO_ACCOUNT_SID before sending SMS.');
        $authToken = $this->requireConfig('services.twilio.auth_token', 'Set TWILIO_AUTH_TOKEN before sending SMS.');
        $from = $this->requireConfig('services.twilio.from', 'Set TWILIO_FROM before sending SMS.');

        $payload = [
            'To' => $message->contact_address,
            'From' => $from,
            'Body' => $message->body,
        ];

        if ($callback = config('services.twilio.status_callback_url')) {
            $payload['StatusCallback'] = $callback;
        }

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", $payload)
            ->throw();

        $messageId = $response->json('sid');

        if (! is_string($messageId) || $messageId === '') {
            throw new RuntimeException('Twilio accepted no message SID; check the API response and sender configuration.');
        }

        return $messageId;
    }

    private function requireConfig(string $key, string $message): string
    {
        $value = config($key);

        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException($message);
        }

        return $value;
    }
}
