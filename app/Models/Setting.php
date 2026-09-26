<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $fillable = [
        'setting_value', // correlated w/ ..
        'setting_name', // will always be ogg vorbis
    ];
}
