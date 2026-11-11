<?php
return [
    'show_warnings' => false,
    'public_path' => base_path(),
    'convert_entities' => true,
    'options' => [
        'fontDir' => base_path('vendor/dompdf/dompdf/lib/fonts'),
        'fontCache' => base_path('vendor/dompdf/dompdf/lib/fonts'),
        'isRemoteEnabled' => true,
    ],
    'is_remote_enabled' => true,
];
