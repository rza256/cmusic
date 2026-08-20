<?php

namespace App\Jobs;

use App\Models\ProcessingJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAudio implements ShouldQueue
{
    use Queueable;

    public $fileName;

    /**
     * Create a new job instance.
     */
    public function __construct(string $fileName)
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
