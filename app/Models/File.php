<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    public $fillable = [
        'file_path',
        'file_size',
        'hash',
        'metadata',
    ];
}
