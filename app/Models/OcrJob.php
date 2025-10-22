<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OcrJob extends Model
{
    protected $table = 'ocr_jobs';

    protected $fillable = [
        'uuid',
        'filename',
        'status',
        'ocr_text',
        'result_path',
        'credit_used',
    ];
}
