<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\OcrJob;
use App\Jobs\ProcessOcrJob;

class OcrController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate(['file' => 'required|image|max:5120']);

        $file = $request->file('file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $realPath = $file->getRealPath();

        $file->storeAs('ocr/originals', $filename);

        $job = OcrJob::create([
            'uuid' => Str::uuid(),
            'filename' => $filename,
            'status' => 'pending'
        ]);

        ProcessOcrJob::dispatch($job, $realPath);

        return response()->json([
            'success' => true,
            'job_uuid' => $job->uuid,
            'message' => 'OCR job queued successfully.'
        ]);
    }

    public function result(string $uuid)
    {
        $job = OcrJob::where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'status' => $job->status,
            'ocr_text' => $job->ocr_text,
        ]);
    }

    public function download(string $uuid, Request $request)
    {
        $type = $request->query('type', 'txt');
        $job = OcrJob::where('uuid', $uuid)->firstOrFail();

        if ($job->status !== 'done') {
            return response()->json(['message' => 'Job not completed yet'], 409);
        }

        $resultsDisk = config('ocr.results_disk', 'local');

        if ($resultsDisk === 'local') {
            $rel = $job->result_path;

            if ($type === 'json') {
                $content = [
                    'uuid' => $job->uuid,
                    'text' => $job->ocr_text,
                    'duration_ms' => $job->duration_ms,
                    'created_at' => $job->created_at,
                ];
                return response()->json($content);
            }

            $fullPath = storage_path('app/' . $job->result_path);

            if (!file_exists($fullPath)) {
                return response()->json(['message' => 'Result file missing'], 404);
            }

            return response()->download($fullPath, $job->uuid . '.txt');
        }

        $rel = $job->result_path;
        if (!$rel || !Storage::disk($resultsDisk)->exists($rel)) {
            return response()->json(['message' => 'Result file missing on remote disk'], 404);
        }

        if ($type === 'json') {
            $content = [
                'uuid' => $job->uuid,
                'text' => $job->ocr_text,
                'duration_ms' => $job->duration_ms,
                'created_at' => $job->created_at,
            ];
            return response()->json($content);
        }

        $stream = Storage::disk($resultsDisk)->readStream($rel);
        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $job->uuid . '.txt"',
        ]);
    }
}
