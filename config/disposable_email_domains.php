<?php

/*
|--------------------------------------------------------------------------
| Disposable / Burner Email Domains
|--------------------------------------------------------------------------
|
| Access requests from these domains are hard-rejected server-side (see
| App\Services\AccessRequestApprovalService). This is a maintained list,
| not exhaustive — add to it as new disposable providers are found. Kept
| as a plain config file (not a DB table) so it can be edited and deployed
| like any other config, no admin UI needed for something this mechanical.
|
*/

return [
    'mailinator.com',
    'guerrillamail.com',
    'guerrillamail.info',
    'guerrillamail.biz',
    'guerrillamail.de',
    'guerrillamail.net',
    'guerrillamail.org',
    'guerrillamailblock.com',
    '10minutemail.com',
    '10minutemail.net',
    'tempmail.com',
    'temp-mail.org',
    'throwawaymail.com',
    'yopmail.com',
    'trashmail.com',
    'getnada.com',
    'sharklasers.com',
    'dispostable.com',
    'fakeinbox.com',
    'maildrop.cc',
    'mintemail.com',
    'mohmal.com',
    'moakt.com',
    'emailondeck.com',
    'spamgourmet.com',
    'mailnesia.com',
    'mytemp.email',
    'tempinbox.com',
    'discard.email',
    'discardmail.com',
];
