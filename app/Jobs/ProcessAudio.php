<?php

namespace App\Jobs;

use App\Models\ProcessingJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Enums\JobType;
use Illuminate\Support\Facades\Storage;
use Kiwilan\Audio\Audio;
use App\Models\File;

class ProcessAudio implements ShouldQueue
{
    use Queueable;

    public string $fileName;
    public JobType $jobType;

    /**
     * Create a new job instance.
     */
    public function __construct(string $fileName, JobType $jobType)
    {
        $this->fileName = $fileName;
        $this->jobType = $jobType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Generally, this will most likely
        // change a lot (job status)
        
        // job_status will be turned
        // into an enum once everything
        // is set in stone, but for now
        // it represents a "stage" of the process.

        $jobExists = ProcessingJob::where('file_path', $this->fileName)->where('job_status', 1)->first();

        // -1 is File already processed error
        $job = ProcessingJob::create([
            'file_path' => $this->fileName,
            'job_status' => $jobExists ? -1 : 0,
            'job_type' => $this->jobType,
            'file_hash' => null,
        ]);

        if ($jobExists)
            return;

        // start checking things
        if(!Storage::disk('music')->exists($this->fileName))
        {
            // file no longer exists
            $job->update([
                'job_status' => -2,
            ]);
            
            return;
        }

        
        $audio = Audio::read(Storage::disk('music')->path($this->fileName));
        $metadata = $audio->getMetadata();
        $metadata = $metadata->toArray(); // meta
        $raw_all = $audio->getRaw(); // 
        
        $full = array_merge($metadata, $raw_all);

        $file = File::create([
            'file_path' => $this->fileName,
            'file_size' => Storage::disk('music')->size($this->fileName),
            'file_hash' => hash_file("xxh3", Storage::disk('music')->path($this->fileName)),
            'metadata' => $full,
        ]);

        $job->update([
            'job_status' => 1,
        ]);
    }
}
