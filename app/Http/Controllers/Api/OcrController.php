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

        // $path = $request->file('file')->storeAs('ocr/originals', $filename);
        // dd(storage_path('app/' . $path));

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
}