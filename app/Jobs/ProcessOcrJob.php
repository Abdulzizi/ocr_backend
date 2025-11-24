<?php

namespace App\Jobs;

use App\Models\OcrJob;
use App\Services\TesseractOcrService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProcessOcrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public OcrJob $jobRecord;
    public string $filePath;

    public function __construct(OcrJob $jobRecord, string $filePath)
    {
        $this->jobRecord = $jobRecord;
        $this->filePath = $filePath;
    }

    public function handle(TesseractOcrService $ocr)
    {
        $this->jobRecord->update(['status' => 'processing']);

        $imagePath = $this->filePath;

        if (!file_exists($imagePath)) {
            $this->jobRecord->update([
                'status' => 'failed',
                'ocr_text' => "File not found: {$imagePath}"
            ]);
            return;
        }

        try {
            $start = microtime(true);
            $result = $ocr->process($imagePath, $this->jobRecord->lang ?? null);
            $duration = (int) round((microtime(true) - $start) * 1000);

            if ($result['success']) {
                $resultsDisk = config('ocr.results_disk', 'local');
                $resultsPathConfig = config('ocr.results_path', 'ocr/results');
                $resultLocalPath = $result['output_file'];

                $relative = str_replace(storage_path('app') . DIRECTORY_SEPARATOR, '', $resultLocalPath);

                if ($resultsDisk !== 'local') {
                    $contents = file_get_contents($resultLocalPath);
                    $destPath = $resultsPathConfig . '/' . basename($resultLocalPath);
                    Storage::disk($resultsDisk)->put($destPath, $contents);
                    $storedResultPath = $destPath;
                    @unlink($resultLocalPath);
                } else {
                    $storedResultPath = $relative;
                }

                $originalsPathConfig = config('ocr.originals_path', 'ocr/originals');
                if (strpos($imagePath, storage_path('app')) === false) {
                    if (config('ocr.retain_original', false)) {
                        $origFilename = basename($imagePath);
                        $copyRel = $originalsPathConfig . '/' . $origFilename;
                        Storage::disk('local')->put($copyRel, file_get_contents($imagePath));
                        $originalStored = $copyRel;
                    } else {
                        $originalStored = null;
                    }
                } else {
                    $originalStored = str_replace(storage_path('app') . DIRECTORY_SEPARATOR, '', $imagePath);
                }

                if (!config('ocr.retain_original', false)) {
                    if ($originalStored && Storage::disk('local')->exists($originalStored)) {
                        Storage::disk('local')->delete($originalStored);
                    }
                }

                $this->jobRecord->update([
                    'status' => 'done',
                    'ocr_text' => $result['text'],
                    'result_path' => $result['output_file'],
                    'duration_ms' => $duration,
                ]);
            } else {
                $this->jobRecord->update([
                    'status' => 'failed',
                    'ocr_text' => $result['error'] ?? 'Unknown error',
                ]);
            }
        } catch (Exception $e) {
            $this->jobRecord->update([
                'status' => 'failed',
                'ocr_text' => 'Exception: ' . $e->getMessage(),
            ]);
        }
    }
}
