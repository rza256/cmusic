<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class JobsController extends Controller {
    public function home(Request $request) {
        $rt = Cache::remember('last_checked_d', now()->addMinutes(10), function() {
            return now();    
        });

        $files = Cache::remember('music_files_d', now()->addMinutes(10), function () {
            return collect(Storage::disk('music')->allFiles())
                ->map(function ($file) {
                    return (object) [
                        'filePath' => $file,
                        // 'fileHash' => hash_file("xxh3", Storage::disk('music')->path($file)),
                        'fileSize' => Storage::disk('music')->size($file),
                    ];
                });
        });

        $totalFS = 0;
        $files->each(function($file) use (&$totalFS) {
            $totalFS += $file->fileSize;
        });

        return view('jobs', [
            'files' => $files,
            'totalFS' => $totalFS,
            'lastCheck' => $rt
        ]);
    }
}