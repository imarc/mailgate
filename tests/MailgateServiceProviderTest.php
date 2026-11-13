<?php

declare(strict_types=1);

namespace Imarc\Mailgate\Tests;

use Illuminate\Support\Env;
use Imarc\Mailgate\MailgateServiceProvider;

class MailgateServiceProviderTest extends TestCase
{
    public function test_package_provider_is_registered(): void
    {
        $this->assertTrue(
            $this->app->getProvider(MailgateServiceProvider::class) instanceof MailgateServiceProvider,
        );
    }

    public function test_mailgate_config_is_merged(): void
    {
        $this->assertIsArray(config('mailgate'));
        $this->assertArrayHasKey('enabled', config('mailgate'));
        $this->assertArrayHasKey('addresses', config('mailgate'));
    }

    public function test_config_reads_mailgate_environment_variables(): void
    {
        $this->setEnv('MAILGATE_ENABLED', 'true');
        $this->setEnv('MAILGATE_ADDRESSES', 'one@example.com, two@example.com');

        try {
            $this->refreshApplication();

            $this->assertTrue(config('mailgate.enabled'));
            $this->assertSame(
                ['one@example.com', 'two@example.com'],
                config('mailgate.addresses'),
            );
        } finally {
            $this->setEnv('MAILGATE_ENABLED', null);
            $this->setEnv('MAILGATE_ADDRESSES', null);
            $this->refreshApplication();
        }
    }

    public function test_config_ignores_mail_whitelist_environment_variables(): void
    {
        $this->setEnv('MAIL_WHITELIST_ENABLED', 'true');
        $this->setEnv('MAIL_WHITELIST_ADDRESSES', 'old@example.com');
        $this->setEnv('MAILGATE_ENABLED', null);
        $this->setEnv('MAILGATE_ADDRESSES', null);

        try {
            $this->refreshApplication();

            $this->assertFalse(config('mailgate.enabled'));
            $this->assertSame([], config('mailgate.addresses'));
        } finally {
            $this->setEnv('MAIL_WHITELIST_ENABLED', null);
            $this->setEnv('MAIL_WHITELIST_ADDRESSES', null);
            $this->refreshApplication();
        }
    }

    private function setEnv(string $key, ?string $value): void
    {
        if ($value === null) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        } else {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        Env::enablePutenv();
    }
}
