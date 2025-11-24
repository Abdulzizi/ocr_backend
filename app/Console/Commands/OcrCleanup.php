<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\OcrJob;
use Carbon\Carbon;

class OcrCleanup extends Command
{
    protected $signature = 'ocr:cleanup {--days=2 : delete files older than N days}';
    protected $description = 'Cleanup OCR results and optionally original files older than given days';

    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $this->info("Cleaning OCR jobs older than {$days} days (before {$cutoff})");

        $jobs = OcrJob::where('created_at', '<', $cutoff)->get();

        $resultsDisk = config('ocr.results_disk', 'local');
        $retainOriginal = config('ocr.retain_original', false);

        foreach ($jobs as $job) {
            $this->line("Cleaning job {$job->uuid} (status: {$job->status})");

            if ($job->result_path) {
                if ($resultsDisk === 'local') {
                    if (Storage::disk('local')->exists($job->result_path)) {
                        Storage::disk('local')->delete($job->result_path);
                        $this->line(" - deleted result {$job->result_path}");
                    }
                } else {
                    if (Storage::disk($resultsDisk)->exists($job->result_path)) {
                        Storage::disk($resultsDisk)->delete($job->result_path);
                        $this->line(" - deleted remote result {$job->result_path}");
                    }
                }
            }

            if (!$retainOriginal && $job->original_path) {
                if (Storage::disk('local')->exists($job->original_path)) {
                    Storage::disk('local')->delete($job->original_path);
                    $this->line(" - deleted original {$job->original_path}");
                }
            }

            $job->update(['result_path' => null]);
        }

        $this->info('Cleanup finished.');
    }
}
