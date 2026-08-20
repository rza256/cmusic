<?php

namespace App\Jobs;

use App\Models\ProcessingJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Enums\JobType;
use Illuminate\Support\Facades\Storage;
use Kiwilan\Audio\Audio;

class ProcessAudio implements ShouldQueue
{
    use Queueable;

    public $fileName;

    /**
     * Create a new job instance.
     */
    public function __construct(string $fileName, JobType $jobType)
    {
        // Generally, this will most likely
        // change a lot (job status)
        
        // job_status will be turned
        // into an enum once everything
        // is set in stone, but for now
        // it represents a "stage" of the process.

        $jobExists = ProcessingJob::where('file_path', $fileName)->where('job_status', 1);

        // -1 is File already processed error
        $job = ProcessingJob::create([
            'file_path' => $fileName,
            'job_status' => $jobExists ? -1 : 0,
            'job_type' => $jobType,
            'file_hash' => null,
        ]);

        if ($jobExists)
            return;

        // start checking things
        if(!Storage::disk('music')->exists($fileName))
        {
            // file no longer exists
            $job->update([
                'job_status' => -2,
            ]);
            
            return;
        }

        
        $audio = Audio::read($fileName);
        $metadata = $audio->getMetadata();
        $metadata = $metadata->toArray();
        $raw_all = $audio->getRawAll();
        
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
    }
}
