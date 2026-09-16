<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    protected $table = 'options';

    public $fillable = [
        'key',
        'value',
        'title',
        'placeholder',
        'modifiable',
        'render'
    ];
}
