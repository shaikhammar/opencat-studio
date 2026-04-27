<?php

return [

    'tm' => [
        'use_global_by_default' => true,
        'provider' => 'postgres',
        'fuzzy_threshold' => 75,
    ],

    'segmentation' => [
        'engine' => 'srx',
        'srx_file' => null,
    ],

    'skeleton' => [
        // D-S4: filesystem chosen over BYTEA (avoids TOAST bloat for large PPTX/DOCX).
        // V4 SaaS path: set FILESYSTEM_DISK=s3 — zero code change.
        'store' => 'filesystem',
    ],

    'file_processing' => [
        'max_sync_size_bytes' => 1_048_576,
        'upload_max_size_bytes' => 52_428_800,
        'supported_formats' => ['docx', 'pptx', 'xlsx', 'html', 'txt', 'xliff', 'po', 'xml'],
    ],

    'mt' => [
        'default_provider' => env('CAT_MT_PROVIDER', null),
        'prefill_on_upload' => false,
        'providers' => [
            'deepl' => [
                'api_key' => env('CAT_DEEPL_API_KEY'),
                'info_url' => 'https://www.deepl.com/pro-api',
            ],
            'google' => [
                'api_key' => env('CAT_GOOGLE_TRANSLATE_KEY'),
                'info_url' => 'https://cloud.google.com/translate/docs/setup',
            ],
            'azure' => [
                'api_key' => env('CAT_AZURE_TRANSLATOR_KEY'),
                'info_url' => 'https://azure.microsoft.com/en-us/products/ai-services/translator',
            ],
        ],
    ],

    'qa' => [
        'default_checks' => [
            'tag_consistency'     => true,
            'length_ratio'        => true,
            'trailing_spaces'     => true,
            'double_spaces'       => true,
            'terminology'         => true,
            'number_consistency'  => true,
            'punctuation_parity'  => false,
        ],
        'length_ratio_max' => 2.5,
    ],

];
