<?php

/*
|--------------------------------------------------------------------------
| Free / Consumer Email Domains
|--------------------------------------------------------------------------
|
| A request whose work-email domain matches one of these is NOT blocked —
| some legitimate small operators only have a personal address — but is
| flagged (access_requests.domain_is_risky) as a soft signal for SuperAdmin
| to weigh during review, per the "flag, don't reject" instruction.
|
*/

return [
    'gmail.com',
    'yahoo.com',
    'outlook.com',
    'hotmail.com',
    'live.com',
    'aol.com',
    'icloud.com',
    'protonmail.com',
    'proton.me',
    'mail.com',
    'gmx.com',
    'yandex.com',
    'zoho.com',
];
