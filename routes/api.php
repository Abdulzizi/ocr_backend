<?php 

use App\Http\Controllers\Api\OcrController;

Route::post('/ocr/upload', [OcrController::class, 'upload']);
Route::get('/ocr/result/{uuid}', [OcrController::class, 'result']);
