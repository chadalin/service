<?php

return [
    'large_file_threshold' => 50 * 1024 * 1024, // 50MB
    
    'optimization' => [
        'batch_size' => 10,
        'memory_limit' => '1024M',
        'time_limit' => 0,
        'extract_images' => false, // Отключить извлечение изображений для больших файлов
    ],
    
    'external_tools' => [
        'pdftotext' => '/usr/bin/pdftotext',
        'pdfinfo' => '/usr/bin/pdfinfo',
        'pdftk' => '/usr/bin/pdftk',
        'pdftoppm' => '/usr/bin/pdftoppm',
    ],
];