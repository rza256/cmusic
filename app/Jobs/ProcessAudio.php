<?php

namespace App\Jobs;

use App\Models\ProcessingJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Enums\JobType;

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

        ProcessingJob::create([
            'file_path' => $fileName,
            'job_status' => 0,
            'job_type' => $jobType,
            'file_hash' => null,
        ]);

        
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
    }
}
