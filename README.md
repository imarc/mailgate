# Mailgate

Laravel package that **whitelists outgoing mail recipients** (To, Cc, Bcc) in non-production environments. When enabled, only configured addresses survive; if every recipient is removed, the send is canceled.

## Why Mailgate?

Staging and QA often use real mail transports (Mailgun, SES, etc.) while the app still generates notifications with production-like recipient lists. Mailgate hooks Laravel’s pre-send mail event so you can limit delivery to your team without changing application code.

## Behavior

1. **Disabled** — message unchanged; send proceeds.
2. **Enabled** — each To/Cc/Bcc address must match an entry in the allow list **exactly** (case-insensitive; no domain wildcards). Non-matching addresses are stripped.
3. If no recipients remain, the send is **canceled**.
4. Debug logs record stripped addresses, canceled sends, and successful sends.

## Installation

```bash
composer require imarc/mailgate
```

Laravel auto-discovers `MailgateServiceProvider` and registers the listener on `Illuminate\Mail\Events\MessageSending`.

Optional — publish config:

```bash
php artisan vendor:publish --tag=mailgate-config
```

## Configuration

Environment variables (default config supports both naming conventions):

| Env | Purpose |
|-----|---------|
| `MAILGATE_ENABLED` or `MAIL_WHITELIST_ENABLED` | `true` / `false` |
| `MAILGATE_ADDRESSES` or `MAIL_WHITELIST_ADDRESSES` | Comma-separated exact email addresses |

Config keys (`config/mailgate.php`):

- `mailgate.enabled`
- `mailgate.addresses` — array of strings

### Example (staging)

```ini
MAIL_MAILER=mailgun
MAILGATE_ENABLED=true
MAILGATE_ADDRESSES="you@imarc.com,team@imarc.com"
```

Leave `MAILGATE_ENABLED` false (or unset) in production.

## License

MIT
