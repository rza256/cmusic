<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Models\ProcessingJob;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Jobs\ProcessAudio;
use App\Enums\JobType;

class JobsController extends Controller {
    public function forceMiss(Request $request)
    {
        Cache::forget('last_checked_d');
        Cache::forget('music_files_d');

        return redirect(route('cmusic.jobs'));
    }

    public function processAll(Request $request) {
        $files = Storage::disk('music')->allFiles();

        foreach($files as $file) {
            ProcessAudio::dispatch($file, JobType::GRAB_METADATA);
        }
    }
    
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

        // get jobs, in progress & succeeding
        $processingJobs = ProcessingJob::orderBy('id', 'desc')->paginate(100);

        return view('jobs', [
            'files' => $files,
            'totalFS' => $totalFS,
            'lastCheck' => $rt,
            'jobs' => $processingJobs
        ]);
    }
}
