<?php

declare(strict_types=1);

return [
    'env'   => getenv('APP_ENV')   ?: 'production',
    'debug' => getenv('APP_DEBUG') === 'true',
    'url'   => getenv('APP_URL')   ?: 'http://localhost:8000',
];
