<?php

namespace App\Http\Controllers;

use App\Models\CommunicationMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CommunicationWebhookController extends Controller
{
    public function verifyWhatsApp(Request $request): Response
    {
        $verifyToken = (string) config('services.whatsapp.verify_token');

        abort_if($verifyToken === '', HttpResponse::HTTP_SERVICE_UNAVAILABLE, 'WhatsApp webhook verify token is not configured.');
        abort_unless(
            $request->query('hub.mode') === 'subscribe'
            && hash_equals($verifyToken, (string) $request->query('hub.verify_token')),
            HttpResponse::HTTP_FORBIDDEN,
        );

        return response((string) $request->query('hub.challenge'), HttpResponse::HTTP_OK)
            ->header('Content-Type', 'text/plain');
    }

    public function receiveWhatsApp(Request $request): Response
    {
        $appSecret = (string) config('services.whatsapp.app_secret');
        $signature = (string) $request->header('X-Hub-Signature-256');

        abort_if($appSecret === '', HttpResponse::HTTP_SERVICE_UNAVAILABLE, 'WhatsApp webhook signing secret is not configured.');
        abort_unless(
            str_starts_with($signature, 'sha256=')
            && hash_equals('sha256='.hash_hmac('sha256', $request->getContent(), $appSecret), $signature),
            HttpResponse::HTTP_FORBIDDEN,
        );

        $inboxUser = $this->inboxUser();
        $payload = $request->json()->all();

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['messages'] ?? [] as $incoming) {
                    $contact = $this->normalisePhone((string) ($incoming['from'] ?? ''));
                    $body = $incoming['text']['body'] ?? null;
                    $messageId = $incoming['id'] ?? null;

                    if ($contact === '' || ! is_string($body) || ! is_string($messageId) || $messageId === '') {
                        continue;
                    }

                    CommunicationMessage::firstOrCreate(
                        ['channel' => 'whatsapp', 'provider_message_id' => $messageId],
                        [
                            'operator_id' => $inboxUser->id,
                            'direction' => 'inbound',
                            'contact_address' => $contact,
                            'body' => $body,
                            'status' => 'received',
                            'metadata' => ['profile_name' => $value['contacts'][0]['profile']['name'] ?? null],
                        ],
                    );
                }

                foreach ($value['statuses'] ?? [] as $status) {
                    if (! empty($status['id']) && ! empty($status['status'])) {
                        CommunicationMessage::where('channel', 'whatsapp')
                            ->where('provider_message_id', $status['id'])
                            ->update(['status' => $status['status']]);
                    }
                }
            }
        }

        return response('', HttpResponse::HTTP_OK);
    }

    public function receiveTwilioSms(Request $request): Response
    {
        abort_unless($this->validTwilioSignature($request, config('services.twilio.webhook_url')), HttpResponse::HTTP_FORBIDDEN);

        $inboxUser = $this->inboxUser();
        $contact = $this->normalisePhone((string) $request->input('From'));
        $messageId = (string) $request->input('MessageSid');
        $body = (string) $request->input('Body');

        if ($contact !== '' && $messageId !== '' && $body !== '') {
            CommunicationMessage::firstOrCreate(
                ['channel' => 'sms', 'provider_message_id' => $messageId],
                [
                    'operator_id' => $inboxUser->id,
                    'direction' => 'inbound',
                    'contact_address' => $contact,
                    'body' => $body,
                    'status' => 'received',
                ],
            );
        }

        return response('<?xml version="1.0" encoding="UTF-8"?><Response></Response>', HttpResponse::HTTP_OK)
            ->header('Content-Type', 'text/xml');
    }

    public function updateTwilioStatus(Request $request): Response
    {
        abort_unless($this->validTwilioSignature($request, config('services.twilio.status_callback_url')), HttpResponse::HTTP_FORBIDDEN);

        $sid = (string) $request->input('MessageSid');
        $status = (string) $request->input('MessageStatus');

        if ($sid !== '' && $status !== '') {
            CommunicationMessage::where('channel', 'sms')
                ->where('provider_message_id', $sid)
                ->update([
                    'status' => $status,
                    'error_message' => $request->input('ErrorMessage'),
                ]);
        }

        return response('', HttpResponse::HTTP_NO_CONTENT);
    }

    private function validTwilioSignature(Request $request, ?string $configuredUrl): bool
    {
        $authToken = (string) config('services.twilio.auth_token');
        $signature = (string) $request->header('X-Twilio-Signature');

        if ($authToken === '' || $signature === '') {
            return false;
        }

        $url = $configuredUrl ?: $request->fullUrl();
        $parameters = $request->all();
        ksort($parameters);

        foreach ($parameters as $key => $value) {
            $url .= $key.(is_array($value) ? implode('', $value) : (string) $value);
        }

        return hash_equals(base64_encode(hash_hmac('sha1', $url, $authToken, true)), $signature);
    }

    private function inboxUser(): User
    {
        $userId = config('services.communications.inbox_user_id');

        $user = $userId ? User::find($userId) : null;

        if (! $user || ! $user->is_active) {
            Log::error('Inbound communication was rejected because COMMUNICATIONS_INBOX_USER_ID is missing or invalid.');
            abort(HttpResponse::HTTP_SERVICE_UNAVAILABLE, 'Set COMMUNICATIONS_INBOX_USER_ID to an active user ID.');
        }

        return $user;
    }

    private function normalisePhone(string $phone): string
    {
        $normalised = preg_replace('/[^\d+]/', '', $phone) ?? '';

        return str_starts_with($normalised, '+') ? $normalised : '+'.$normalised;
    }
}
