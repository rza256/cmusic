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
        'job_type', // can be transcoding, reading metadata, etc.
        'job_status',
    ];

    public function getColor() {
        if ($this->job_status == 0)
        {
            return "white";
        }
        elseif ($this->job_status < 0)
        {
            return "red";
        }
        else {
            return "green";
        }
    }
}
