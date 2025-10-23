<?php

namespace App\Jobs;

use App\Models\OcrJob;
use App\Services\TesseractOcrService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOcrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $jobRecord;
    public $filePath;

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

        $result = $ocr->process($imagePath);

        if ($result['success']) {
            $this->jobRecord->update([
                'status' => 'done',
                'ocr_text' => $result['text'],
                'result_path' => $result['output_file'],
            ]);
        } else {
            $this->jobRecord->update([
                'status' => 'failed',
                'ocr_text' => $result['error'],
            ]);
        }
    }
}
