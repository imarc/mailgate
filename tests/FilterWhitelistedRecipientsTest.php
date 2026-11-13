<?php

declare(strict_types=1);

namespace Imarc\Mailgate\Tests;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Imarc\Mailgate\Listeners\FilterWhitelistedRecipients;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class FilterWhitelistedRecipientsTest extends TestCase
{
    private FilterWhitelistedRecipients $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new FilterWhitelistedRecipients;
    }

    public function test_does_nothing_when_whitelist_disabled(): void
    {
        config([
            'mailgate.enabled' => false,
            'mailgate.addresses' => ['allowed@example.com'],
        ]);

        $message = $this->makeMessage(
            to: ['allowed@example.com', 'blocked@example.com'],
            cc: ['also-blocked@example.com'],
        );

        $result = $this->listener->handle(new MessageSending($message));

        $this->assertNull($result);
        $this->assertSame(
            ['allowed@example.com', 'blocked@example.com'],
            $this->addresses($message->getTo()),
        );
        $this->assertSame(['also-blocked@example.com'], $this->addresses($message->getCc()));
    }

    public function test_strips_non_whitelisted_to_cc_and_bcc(): void
    {
        config([
            'mailgate.enabled' => true,
            'mailgate.addresses' => ['allowed@example.com', 'cc-allowed@example.com'],
        ]);

        $message = $this->makeMessage(
            to: ['allowed@example.com', 'blocked@example.com'],
            cc: ['cc-allowed@example.com', 'cc-blocked@example.com'],
            bcc: ['bcc-blocked@example.com'],
        );

        $result = $this->listener->handle(new MessageSending($message));

        $this->assertNull($result);
        $this->assertSame(['allowed@example.com'], $this->addresses($message->getTo()));
        $this->assertSame(['cc-allowed@example.com'], $this->addresses($message->getCc()));
        $this->assertSame([], $this->addresses($message->getBcc()));
    }

    public function test_cancels_when_no_recipients_remain(): void
    {
        config([
            'mailgate.enabled' => true,
            'mailgate.addresses' => ['allowed@example.com'],
        ]);

        $message = $this->makeMessage(
            to: ['blocked@example.com'],
            cc: ['also-blocked@example.com'],
        );

        $result = $this->listener->handle(new MessageSending($message));

        $this->assertFalse($result);
        $this->assertSame([], $this->addresses($message->getTo()));
        $this->assertSame([], $this->addresses($message->getCc()));
    }

    public function test_exact_match_only(): void
    {
        config([
            'mailgate.enabled' => true,
            'mailgate.addresses' => ['user@imarc.com'],
        ]);

        $message = $this->makeMessage(
            to: ['user@imarc.com', 'other@imarc.com'],
        );

        $result = $this->listener->handle(new MessageSending($message));

        $this->assertNull($result);
        $this->assertSame(['user@imarc.com'], $this->addresses($message->getTo()));
    }

    public function test_match_is_case_insensitive(): void
    {
        config([
            'mailgate.enabled' => true,
            'mailgate.addresses' => ['dave@example.com'],
        ]);

        $message = $this->makeMessage(
            to: ['Dave@Example.com'],
        );

        $result = $this->listener->handle(new MessageSending($message));

        $this->assertNull($result);
        $this->assertSame(['Dave@Example.com'], $this->addresses($message->getTo()));
    }

    public function test_logs_when_sending_to_whitelisted_recipients(): void
    {
        Log::spy();

        config([
            'mailgate.enabled' => true,
            'mailgate.addresses' => ['allowed@example.com'],
        ]);

        $message = $this->makeMessage(
            to: ['allowed@example.com', 'blocked@example.com'],
        );

        $this->listener->handle(new MessageSending($message));

        Log::shouldHaveReceived('debug')
            ->with('Sending mail to whitelisted recipients.', [
                'to' => ['allowed@example.com'],
                'cc' => [],
                'bcc' => [],
            ])
            ->once();
    }

    /**
     * @param  list<string>  $to
     * @param  list<string>  $cc
     * @param  list<string>  $bcc
     */
    private function makeMessage(array $to = [], array $cc = [], array $bcc = []): Email
    {
        $message = (new Email)
            ->from('noreply@example.com')
            ->subject('Test')
            ->text('Body');

        if ($to !== []) {
            $message->to(...$to);
        }

        if ($cc !== []) {
            $message->cc(...$cc);
        }

        if ($bcc !== []) {
            $message->bcc(...$bcc);
        }

        return $message;
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
}
