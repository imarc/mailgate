<?php

declare(strict_types=1);

namespace Imarc\Mailgate\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class FilterWhitelistedRecipients
{
    /**
     * Strip non-whitelisted To/Cc/Bcc recipients when the whitelist is enabled.
     *
     * @return bool|null False cancels sending when no recipients remain.
     */
    public function handle(MessageSending $event): ?bool
    {
        if (! config('mailgate.enabled')) {
            return null;
        }

        /** @var list<string> $addresses */
        $addresses = config('mailgate.addresses', []);
        $allowed = array_fill_keys(
            array_map(strtolower(...), $addresses),
            true,
        );

        $message = $event->message;
        $stripped = [];

        foreach (['to', 'cc', 'bcc'] as $type) {
            $stripped = array_merge(
                $stripped,
                $this->filterRecipients($message, $type, $allowed),
            );
        }

        if ($stripped !== []) {
            Log::debug('Stripped non-whitelisted mail recipients.', [
                'stripped' => $stripped,
            ]);
        }

        if ($this->hasNoRecipients($message)) {
            Log::debug('Canceled outgoing mail: no whitelisted recipients remain.');

            return false;
        }

        Log::debug('Sending mail to whitelisted recipients.', [
            'to' => $this->addresses($message->getTo()),
            'cc' => $this->addresses($message->getCc()),
            'bcc' => $this->addresses($message->getBcc()),
        ]);

        return null;
    }

    /**
     * @param  list<Address>  $addresses
     * @return list<string>
     */
    private function addresses(array $addresses): array
    {
        return array_map(
            static fn (Address $address): string => $address->getAddress(),
            $addresses,
        );
    }

    /**
     * @param  array<string, true>  $allowed
     * @return list<string>
     */
    private function filterRecipients(Email $message, string $type, array $allowed): array
    {
        $getter = 'get'.ucfirst($type);

        /** @var list<Address> $recipients */
        $recipients = $message->{$getter}();

        if ($recipients === []) {
            return [];
        }

        $kept = [];
        $stripped = [];

        foreach ($recipients as $recipient) {
            $address = strtolower($recipient->getAddress());

            if (isset($allowed[$address])) {
                $kept[] = $recipient;
            } else {
                $stripped[] = $recipient->getAddress();
            }
        }

        if ($kept === []) {
            $message->getHeaders()->remove($type);
        } else {
            $message->{$type}(...$kept);
        }

        return $stripped;
    }

    private function hasNoRecipients(Email $message): bool
    {
        return $message->getTo() === []
            && $message->getCc() === []
            && $message->getBcc() === [];
    }
}
