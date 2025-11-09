<?php 

use App\Http\Controllers\Api\OcrController;

Route::prefix('ocr')->group(function () {
    Route::post('/upload', [OcrController::class, 'upload']);
    Route::get('/{uuid}', [OcrController::class, 'result']);
    Route::get('/{uuid}/download', [OcrController::class, 'download']);
});