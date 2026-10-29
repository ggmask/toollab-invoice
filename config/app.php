<?php

return [
    'name' => getenv('APP_NAME') ?: 'ToolLab Invoice',
    'env' => getenv('APP_ENV') ?: 'local', // 'local' or 'production'
    'debug' => getenv('APP_DEBUG') !== false ? (bool)getenv('APP_DEBUG') : true,
    'url' => getenv('APP_URL') ?: 'http://localhost/toollab-invoice/public',
    'timezone' => 'Asia/Kolkata',
    'locale' => 'en_IN',
    'currency' => 'INR',
    'currency_symbol' => '₹',
    'session_lifetime' => 86400 * 7, // 7 days
];
