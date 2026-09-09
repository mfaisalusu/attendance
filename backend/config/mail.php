<?php

declare(strict_types=1);

return [
    'host'         => getenv('MAIL_HOST')         ?: 'smtp.mailtrap.io',
    'port'         => (int) (getenv('MAIL_PORT')  ?: 2525),
    'username'     => getenv('MAIL_USERNAME')      ?: '',
    'password'     => getenv('MAIL_PASSWORD')      ?: '',
    'from_address' => getenv('MAIL_FROM_ADDRESS')  ?: 'noreply@attendance.dev',
    'from_name'    => getenv('MAIL_FROM_NAME')     ?: 'Attendance App',
];
