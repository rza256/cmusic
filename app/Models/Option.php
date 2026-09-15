<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    public $fillable = [
        'key',
        'value',
        'title',
        'placeholder',
        'modifiable',
        'render'
    ];
}
