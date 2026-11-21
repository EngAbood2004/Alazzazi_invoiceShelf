<?php

namespace App\Console\Commands;

use App\Models\CommunicationMessage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PullGmailInbox extends Command
{
    protected $signature = 'communications:pull-gmail';

    protected $description = 'Import new Gmail replies into the communication inbox';

    public function handle(): int
    {
        $config = config('services.gmail_imap');

        if (! $config['enabled']) {
            return self::SUCCESS;
        }

        if (! function_exists('imap_open')) {
            $this->error('Gmail inbox polling requires the PHP IMAP extension. Enable it for the PHP CLI runtime used by this project.');

            return self::FAILURE;
        }

        $inboxUserId = config('services.communications.inbox_user_id');
        $inboxUser = $inboxUserId ? User::find($inboxUserId) : null;

        if (! $inboxUser) {
            $this->error('Set COMMUNICATIONS_INBOX_USER_ID to an existing user ID before importing replies.');

            return self::FAILURE;
        }

        $mailbox = sprintf(
            '{%s:%d/imap/ssl}%s',
            $config['host'],
            $config['port'],
            $config['mailbox'],
        );
        $connection = @imap_open($mailbox, $config['username'], $config['password']);

        if ($connection === false) {
            $error = imap_last_error() ?: 'Unknown IMAP connection error.';
            $this->error($error);
            Log::error('Gmail IMAP connection failed.', ['error' => $error]);

            return self::FAILURE;
        }

        try {
            $uids = imap_search($connection, 'UNSEEN', SE_UID) ?: [];

            foreach (array_slice($uids, 0, 25) as $uid) {
                $overview = imap_fetch_overview($connection, (string) $uid, FT_UID)[0] ?? null;
                $sequence = imap_msgno($connection, (int) $uid);
                $header = $sequence ? imap_headerinfo($connection, $sequence) : false;
                $from = $header->from[0] ?? null;
                $address = $from ? strtolower($from->mailbox.'@'.$from->host) : '';
                $messageId = trim((string) ($overview->message_id ?? ''), " <>\t\r\n");

                if ($address === '' || ! filter_var($address, FILTER_VALIDATE_EMAIL)) {
                    Log::warning('Skipped a Gmail message with no valid sender address.', ['uid' => $uid]);
                    imap_setflag_full($connection, (string) $uid, '\\Seen', ST_UID);
                    continue;
                }

                if ($messageId === '') {
                    $messageId = 'gmail-uid-'.$uid;
                }
                $providerId = 'gmail:'.mb_substr($messageId, 0, 240);

                if (! CommunicationMessage::where('channel', 'email')->where('provider_message_id', $providerId)->exists()) {
                    $structure = imap_fetchstructure($connection, (int) $uid, FT_UID);
                    $body = $structure ? $this->extractBody($connection, (int) $uid, $structure) : '';

                    if ($body === '') {
                        Log::warning('Skipped an empty Gmail message.', ['uid' => $uid, 'message_id' => $providerId]);
                        imap_setflag_full($connection, (string) $uid, '\\Seen', ST_UID);
                        continue;
                    }

                    CommunicationMessage::create([
                        'operator_id' => $inboxUser->id,
                        'channel' => 'email',
                        'direction' => 'inbound',
                        'contact_address' => $address,
                        'subject' => isset($overview->subject) ? mb_substr(imap_utf8($overview->subject), 0, 160) : null,
                        'body' => $body,
                        'provider_message_id' => $providerId,
                        'status' => 'received',
                    ]);
                }

                imap_setflag_full($connection, (string) $uid, '\\Seen', ST_UID);
            }

            $this->info('Gmail inbox checked. Imported '.count($uids).' unseen message(s).');
        } finally {
            imap_close($connection);
        }

        return self::SUCCESS;
    }

    private function extractBody($connection, int $uid, object $structure): string
    {
        $plain = $this->findTextPart($connection, $uid, $structure, 'PLAIN');

        if ($plain !== '') {
            return trim($plain);
        }

        $html = $this->findTextPart($connection, $uid, $structure, 'HTML');

        return trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $html))));
    }

    private function findTextPart($connection, int $uid, object $part, string $wantedSubtype, string $section = ''): string
    {
        $subtype = strtoupper((string) ($part->subtype ?? ''));
        $disposition = strtoupper((string) ($part->disposition ?? ''));

        if ($disposition === 'ATTACHMENT') {
            return '';
        }

        if ((int) $part->type === TYPETEXT && $subtype === $wantedSubtype) {
            $partNumber = $section === '' ? '1' : $section;
            $body = imap_fetchbody($connection, $uid, $partNumber, FT_UID | FT_PEEK);

            if ($body === false) {
                return '';
            }

            $body = match ((int) ($part->encoding ?? 0)) {
                3 => base64_decode($body, true) ?: '',
                4 => quoted_printable_decode($body),
                default => $body,
            };

            $charset = $this->partCharset($part);

            return $charset !== '' && strcasecmp($charset, 'UTF-8') !== 0
                ? mb_convert_encoding($body, 'UTF-8', $charset)
                : $body;
        }

        foreach ($part->parts ?? [] as $index => $child) {
            $childSection = $section === '' ? (string) ($index + 1) : $section.'.'.($index + 1);
            $body = $this->findTextPart($connection, $uid, $child, $wantedSubtype, $childSection);

            if ($body !== '') {
                return $body;
            }
        }

        return '';
    }

    private function partCharset(object $part): string
    {
        foreach ($part->parameters ?? [] as $parameter) {
            if (strtolower((string) $parameter->attribute) === 'charset') {
                return trim((string) $parameter->value, "\"'");
            }
        }

        return '';
    }
}
