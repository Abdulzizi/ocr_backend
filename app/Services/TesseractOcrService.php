<?php

namespace App\Services;

use Illuminate\Support\Str;

class TesseractOcrService
{
    protected string $tesseractBin;
    protected string $resultsPath;

    public function __construct()
    {
        $this->tesseractBin = config('ocr.tesseract_bin', 'tesseract');
        $this->resultsPath = storage_path('app/' . rtrim(config('ocr.results_path', 'ocr/results'), '/'));

        if (!is_dir($this->resultsPath)) {
            mkdir($this->resultsPath, 0755, true);
        }
    }

    public function process(string $imagePath, ?string $lang = null): array
    {
        $lang = $lang ?? config('ocr.default_lang', 'eng');

        $outputName = 'ocr_' . Str::random(12);
        $outputFull = $this->resultsPath . DIRECTORY_SEPARATOR . $outputName;

        $cmd = escapeshellarg($this->tesseractBin) . ' ' .
            escapeshellarg($imagePath) . ' ' .
            escapeshellarg($outputFull) . ' -l ' .
            escapeshellarg($lang) . ' 2>&1';

        exec($cmd, $outputLines, $status);

        $txtPath = $outputFull . '.txt';

        if (!file_exists($txtPath)) {
            return [
                'success' => false,
                'error' => 'Tesseract finished but output file missing: ' . $txtPath,
            ];
        }

        $text = file_get_contents($txtPath);
        $output_file = 'ocr/results/' . basename($txtPath);

        return [
            'success' => true,
            'text' => $text,
            'output_file' => $output_file,
        ];
    }
}
