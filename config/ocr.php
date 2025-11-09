<?php
return [
    'retain_original' => env('OCR_RETAIN_ORIGINAL', false),

    'results_disk' => env('OCR_RESULTS_DISK', 'local'),

    'results_path' => env('OCR_RESULTS_PATH', 'ocr/results'),

    'originals_path' => env('OCR_ORIGINALS_PATH', 'ocr/originals'),

    'default_lang' => env('OCR_LANG', 'eng'),

    'tesseract_bin' => env('TESSERACT_BIN', 'tesseract'),
];