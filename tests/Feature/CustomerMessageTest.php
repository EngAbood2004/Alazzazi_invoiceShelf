<?php

namespace Tests\Feature;

use App\Mail\CustomerMessageSent;
use App\Models\CustomerMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_sending_a_message_stores_it_and_emails_the_recipient(): void
    {
        Mail::fake();

        $sender = User::factory()->create(['email' => 'sender@example.test']);
        $recipient = User::factory()->create(['email' => 'recipient@example.test']);

        $response = $this->actingAs($sender)->post(route('messages.store'), [
            'recipient_id' => $recipient->id,
            'subject' => 'Schedule update',
            'body' => 'The meeting starts at 10.',
        ]);

        $response->assertRedirect(route('messages.index'));
        $this->assertDatabaseHas('customer_messages', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'subject' => 'Schedule update',
            'body' => 'The meeting starts at 10.',
        ]);

        Mail::assertSent(CustomerMessageSent::class, function (CustomerMessageSent $mail) use ($recipient): bool {
            return $mail->hasTo($recipient->email)
                && $mail->customerMessage->subject === 'Schedule update';
        });
    }

    public function test_sending_a_message_to_yourself_is_rejected(): void
    {
        Mail::fake();

        $sender = User::factory()->create();

        $response = $this->actingAs($sender)->post(route('messages.store'), [
            'recipient_id' => $sender->id,
            'subject' => 'Not allowed',
            'body' => 'This should not be sent.',
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseCount('customer_messages', 0);
        Mail::assertNothingSent();
    }
}
