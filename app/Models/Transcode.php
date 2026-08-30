<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transcode extends Model
{
    public $fillable = [
        'file_id', // correlated w/ ..
        'file_name', // will always be ogg vorbis
        'file_size',
        'file_hash'
    ];

    public function file()
    {
        return $this->belongsTo(File::class, 'file_id', 'id');
    }
}
