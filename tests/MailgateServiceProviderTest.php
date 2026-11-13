<?php

declare(strict_types=1);

namespace Imarc\Mailgate\Tests;

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
}
