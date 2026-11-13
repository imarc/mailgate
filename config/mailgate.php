<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Recipient whitelist
    |--------------------------------------------------------------------------
    |
    | When enabled, outgoing mail is limited to the exact addresses listed
    | below (case-insensitive). Non-matching To/Cc/Bcc recipients are
    | stripped; if none remain, the message is not sent.
    |
    */

    'enabled' => filter_var(
        env('MAILGATE_ENABLED', false),
        FILTER_VALIDATE_BOOLEAN,
    ),

    'addresses' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MAILGATE_ADDRESSES', '')),
    ))),

];
