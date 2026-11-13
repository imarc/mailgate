<?php

declare(strict_types=1);

namespace Imarc\Mailgate;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Imarc\Mailgate\Listeners\FilterWhitelistedRecipients;

class MailgateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mailgate.php', 'mailgate');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/mailgate.php' => config_path('mailgate.php'),
            ], 'mailgate-config');
        }

        Event::listen(MessageSending::class, FilterWhitelistedRecipients::class);
    }
}
