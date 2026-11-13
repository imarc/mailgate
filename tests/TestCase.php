<?php

declare(strict_types=1);

namespace Imarc\Mailgate\Tests;

use Imarc\Mailgate\MailgateServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            MailgateServiceProvider::class,
        ];
    }
}
