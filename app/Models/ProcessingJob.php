<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessingJob extends Model
{
    // this acts as like a "lock", so 
    // if jobs fail they don't cause loose
    // things
    public $fillable = [
        'file_path',
        'file_hash',
        'job_status',
    ];
}
