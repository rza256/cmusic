<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    public $fillable = [
        'file_path',
        'file_size',
        'file_hash',
        'album',
        'title',
        'artist',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function transcode()
    {
        return $this->hasOne(Transcode::class, 'file_id', 'id');
    }
}
