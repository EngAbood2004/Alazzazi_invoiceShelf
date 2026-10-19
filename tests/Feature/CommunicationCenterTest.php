<?php

namespace Tests\Feature;

use App\Mail\ExternalCommunicationMail;
use App\Models\CommunicationMessage;
use App\Models\CustomerMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CommunicationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_message_is_saved_and_sent_through_the_cloud_api(): void
    {
        config([
            'services.whatsapp.access_token' => 'test-access-token',
            'services.whatsapp.phone_number_id' => '12345',
            'services.whatsapp.api_version' => 'v24.0',
        ]);
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test-1']]], 200),
        ]);

        $operator = User::factory()->create();

        $response = $this->actingAs($operator)->post(route('messages.store'), [
            'channel' => 'whatsapp',
            'contact_address' => '+15551234567',
            'body' => 'Hello from the contact center.',
        ]);

        $response->assertRedirect(route('messages.index', [
            'channel' => 'whatsapp',
            'contact' => '+15551234567',
        ]));
        $this->assertDatabaseHas('communication_messages', [
            'channel' => 'whatsapp',
            'direction' => 'outbound',
            'contact_address' => '+15551234567',
            'status' => 'sent',
            'provider_message_id' => 'wamid.test-1',
        ]);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.facebook.com')
            && $request['messaging_product'] === 'whatsapp'
            && $request['to'] === '15551234567');
    }

    public function test_email_message_is_saved_and_sent_with_the_configured_mailer(): void
    {
        Mail::fake();
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.gmail.com',
            'mail.mailers.smtp.username' => 'sender@example.test',
            'mail.mailers.smtp.password' => 'test-app-password',
            'mail.from.address' => 'sender@example.test',
        ]);

        $operator = User::factory()->create();

        $response = $this->actingAs($operator)->post(route('messages.store'), [
            'channel' => 'email',
            'contact_address' => 'customer@example.test',
            'subject' => 'Order update',
            'body' => 'Your order has shipped.',
        ]);

        $response->assertRedirect(route('messages.index', [
            'channel' => 'email',
            'contact' => 'customer@example.test',
        ]));
        $this->assertDatabaseHas('communication_messages', [
            'channel' => 'email',
            'direction' => 'outbound',
            'contact_address' => 'customer@example.test',
            'subject' => 'Order update',
            'status' => 'sent',
        ]);
        Mail::assertSent(ExternalCommunicationMail::class, fn (ExternalCommunicationMail $mail) => $mail->hasTo('customer@example.test'));
    }

    public function test_sms_is_sent_through_twilio_and_the_provider_sid_is_saved(): void
    {
        config([
            'services.twilio.account_sid' => 'AC123456789',
            'services.twilio.auth_token' => 'test-twilio-auth-token',
            'services.twilio.from' => '+15557654321',
        ]);
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM-outbound-1', 'status' => 'queued'], 201),
        ]);

        $operator = User::factory()->create();

        $response = $this->actingAs($operator)->post(route('messages.store'), [
            'channel' => 'sms',
            'contact_address' => '+15551234567',
            'body' => 'Your appointment is confirmed.',
        ]);

        $response->assertRedirect(route('messages.index', [
            'channel' => 'sms',
            'contact' => '+15551234567',
        ]));
        $this->assertDatabaseHas('communication_messages', [
            'channel' => 'sms',
            'contact_address' => '+15551234567',
            'provider_message_id' => 'SM-outbound-1',
            'status' => 'sent',
        ]);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.twilio.com')
            && $request['To'] === '+15551234567'
            && $request['Body'] === 'Your appointment is confirmed.');
    }

    public function test_missing_provider_configuration_keeps_the_message_and_marks_it_failed(): void
    {
        config([
            'services.whatsapp.access_token' => null,
            'services.whatsapp.phone_number_id' => null,
        ]);

        $operator = User::factory()->create();

        $response = $this->actingAs($operator)->post(route('messages.store'), [
            'channel' => 'whatsapp',
            'contact_address' => '+15551234567',
            'body' => 'This must not be lost.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('communication_messages', [
            'channel' => 'whatsapp',
            'contact_address' => '+15551234567',
            'body' => 'This must not be lost.',
            'status' => 'failed',
        ]);
    }

    public function test_whatsapp_webhook_verifies_signature_and_stores_inbound_message(): void
    {
        $operator = User::factory()->create();
        $payload = json_encode([
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'contacts' => [['profile' => ['name' => 'Customer']]],
                        'messages' => [[
                            'from' => '15551234567',
                            'id' => 'wamid.inbound-1',
                            'text' => ['body' => 'Can you help me?'],
                            'type' => 'text',
                        ]],
                    ],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);
        $appSecret = 'test-meta-app-secret';
        config([
            'services.whatsapp.app_secret' => $appSecret,
            'services.communications.inbox_user_id' => $operator->id,
        ]);
        $signature = 'sha256='.hash_hmac('sha256', $payload, $appSecret);

        $response = $this->call(
            'POST',
            '/api/webhooks/whatsapp',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => $signature],
            $payload,
        );

        $response->assertOk();
        $this->assertDatabaseHas('communication_messages', [
            'operator_id' => $operator->id,
            'channel' => 'whatsapp',
            'direction' => 'inbound',
            'contact_address' => '+15551234567',
            'provider_message_id' => 'wamid.inbound-1',
            'body' => 'Can you help me?',
        ]);
    }

    public function test_twilio_webhook_verifies_signature_and_stores_inbound_sms(): void
    {
        $operator = User::factory()->create();
        $authToken = 'test-twilio-auth-token';
        $url = 'http://localhost/api/webhooks/twilio/inbound';
        $parameters = [
            'From' => '+15551234567',
            'To' => '+15557654321',
            'Body' => 'Reply by text',
            'MessageSid' => 'SM-inbound-1',
        ];
        $signedData = $url;
        ksort($parameters);
        foreach ($parameters as $key => $value) {
            $signedData .= $key.$value;
        }
        config([
            'services.twilio.auth_token' => $authToken,
            'services.twilio.webhook_url' => $url,
            'services.communications.inbox_user_id' => $operator->id,
        ]);
        $signature = base64_encode(hash_hmac('sha1', $signedData, $authToken, true));

        $response = $this->call(
            'POST',
            '/api/webhooks/twilio/inbound',
            $parameters,
            [],
            [],
            ['HTTP_X_TWILIO_SIGNATURE' => $signature],
        );

        $response->assertOk();
        $this->assertDatabaseHas('communication_messages', [
            'operator_id' => $operator->id,
            'channel' => 'sms',
            'direction' => 'inbound',
            'contact_address' => '+15551234567',
            'provider_message_id' => 'SM-inbound-1',
            'body' => 'Reply by text',
        ]);
    }

    public function test_existing_internal_messages_remain_available_after_external_messages_are_added(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $internal = CustomerMessage::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'subject' => 'Existing message',
            'body' => 'Keep this record.',
        ]);
        CommunicationMessage::create([
            'operator_id' => $sender->id,
            'channel' => 'sms',
            'direction' => 'inbound',
            'contact_address' => '+15551234567',
            'body' => 'New external message.',
            'status' => 'received',
        ]);

        $this->assertDatabaseHas('customer_messages', [
            'id' => $internal->id,
            'body' => 'Keep this record.',
        ]);
        $this->assertDatabaseCount('customer_messages', 1);
        $this->assertDatabaseCount('communication_messages', 1);
    }

    public function test_operator_can_open_an_external_thread_and_it_is_marked_as_read(): void
    {
        $operator = User::factory()->create();
        $message = CommunicationMessage::create([
            'operator_id' => $operator->id,
            'channel' => 'email',
            'direction' => 'inbound',
            'contact_address' => 'customer@example.test',
            'subject' => 'Question',
            'body' => 'Can you help with my order?',
            'status' => 'received',
        ]);

        $response = $this->actingAs($operator)->get(route('messages.index', [
            'channel' => 'email',
            'contact' => 'customer@example.test',
        ]));

        $response->assertOk()
            ->assertSee('customer@example.test')
            ->assertSee('Can you help with my order?');
        $this->assertNotNull($message->fresh()->read_at);
    }
}
