<?php

return [
    'default_currency' => 'USD',
    'base_project_name' => 'Remote Raven',
    'max_attachment_size_mb' => 10,
    'supported_attachment_mimes' => [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ],
    'conversion_rate_direction' => 'per_base',
    // per_base: local_amount / conversion_rate = USD amount
    // e.g. KES 13000 / 130 = USD 100
    'expense_ref_prefix' => 'EXP',
    'reward_ref_prefix' => 'RWD',
    'ref_pad_length' => 5, // EXP-00042
];
