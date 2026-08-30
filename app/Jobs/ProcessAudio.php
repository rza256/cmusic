<?php

namespace App\Jobs;

use App\Models\ProcessingJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Enums\JobType;
use Illuminate\Support\Facades\Storage;
use Kiwilan\Audio\Audio;
use App\Models\File;
use App\Models\Transcode;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Illuminate\Contracts\Filesystem\Filesystem;

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

        $jobExists = ProcessingJob::where('file_path', $this->fileName)
            ->where('job_status', 1)
            ->where('job_type', $this->jobType)
            ->first();

        // -1 is File already processed error
        $job = ProcessingJob::create([
            'file_path' => $this->fileName,
            'job_status' => $jobExists ? -1 : 0,
            'job_type' => $this->jobType,
            'album' => '',
            'title' => '',
            'artist' => '',
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

        $hash = hash_file("xxh3", Storage::disk('music')->path($this->fileName));
        $file = File::where('file_hash', $hash)->first();

        if ($file) {
            // file w/ hash already exists
            $job->update([
                'job_status' => -3,
            ]);
            
            return;
        }

        if ($this->jobType == JobType::GRAB_METADATA)
        {
            $audio = Audio::read(Storage::disk('music')->path($this->fileName));
            $metadata = $audio->getMetadata();
            $metadata = $metadata->toArray(); // meta
            $raw_all = $audio->getRaw(); // 
            
            $full = array_merge($metadata, $raw_all);

            try {
                $file = File::create([
                    'file_path' => $this->fileName,
                    'album' => $full['album'] ?? '',
                    'title' => $full['title'] ?? '',
                    'artist' => $full['artist'] ?? '',
                    'file_path' => $this->fileName,
                    'file_size' => Storage::disk('music')->size($this->fileName),
                    'file_hash' => $hash,
                    'metadata' => $full,
                ]);
            } catch(\Exception $e) {
                // most likely
                // "Unable to encode attribute [metadata] for model"
                $job->update([
                    'job_status' => -1,
                ]);
            }

            $job->update([
                'job_status' => 1,
            ]);
        }

        if ($this->jobType == JobType::TRANSCODE_AUDIO)
        {
            $file = File::where('file_path', $this->fileName)->first();
            
            if (!$file)
                return; // todo: handle properly
            
            FFMpeg::fromDisk('music')
                ->open($this->fileName)
                ->export()
                ->toDisk('transcodes')
                ->inFormat(new \FFMpeg\Format\Audio\Vorbis)
                ->save(pathinfo($this->fileName, PATHINFO_FILENAME) . ".ogg");
        
            $tc = Transcode::create([
                'file_id' => $file->id,
                'file_name' => $file->file_path,
                'file_size' => Storage::disk('transcodes')->size(pathinfo($this->fileName, PATHINFO_FILENAME) . ".ogg"),
                'file_hash' => hash_file("xxh3", Storage::disk('transcodes')->path(pathinfo($this->fileName, PATHINFO_FILENAME) . ".ogg")),
            ]);

            $job->update([
                'job_status' => 1,
            ]);
        }
    }
}
