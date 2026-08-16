<?php

$adminEmails = array_values(array_filter(array_map(
    'trim',
    explode(',', env('ADMIN_EMAILS', env('ADMIN_EMAIL', 'admin@hmmusic.test')))
)));

return [
    // Keep the singular key for existing controllers, middleware, and tests.
    'admin_email' => $adminEmails[0] ?? 'admin@hmmusic.test',
    'admin_emails' => $adminEmails,
];
