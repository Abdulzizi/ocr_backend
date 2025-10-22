<?php

namespace App\Services;

use Illuminate\Support\Str;

class TesseractOcrService
{
    public function process(string $imagePath, string $lang = 'eng'): array
    {
        $outputName = 'ocr_' . Str::random(10);
        $outputPath = storage_path("app/ocr/results/{$outputName}");

        $command = "tesseract " . escapeshellarg($imagePath) . " " . escapeshellarg($outputPath) . " -l " . escapeshellarg($lang) . " 2>&1";
        exec($command, $output, $status);

        if ($status !== 0) {
            return ['success' => false, 'error' => implode("\n", $output)];
        }

        $text = file_get_contents($outputPath . '.txt');

        return [
            'success' => true,
            'text' => $text,
            'output_file' => $outputPath . '.txt'
        ];
    }
}
