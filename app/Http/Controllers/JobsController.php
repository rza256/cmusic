<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Models\ProcessingJob;
use App\Models\File;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Jobs\ProcessAudio;
use App\Enums\JobType;
use App\Helpers\PaginationHelper;

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

    public function transcode(Request $request, int $id)
    {
        $file = File::where('id', $id)->first();
        ProcessAudio::dispatch($file->file_path, JobType::TRANSCODE_AUDIO);
    }

    public function transcodeAlbum(Request $request, int $id)
    {
        $file = File::where('id', $id)->first();
        $albumSongs = File::where('album', $file->album)->get();
        foreach ($albumSongs as $song) {
            ProcessAudio::dispatch($song->file_path, JobType::TRANSCODE_AUDIO);
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
        
        $files = PaginationHelper::paginate($files, 100);

        // get jobs, in progress & succeeding
        $processingJobs = ProcessingJob::orderBy('updated_at', 'desc')->paginate(100);

        return view('jobs', [
            'files' => $files,
            'totalFS' => $totalFS,
            'lastCheck' => $rt,
            'jobs' => $processingJobs
        ]);
    }
}
