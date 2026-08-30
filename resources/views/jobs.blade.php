@extends('base', [
    'sTab' => 'jobs'
])
@section('title', 'jobs')
@section('content')
    <div class="flex">
        <div class="col-1">
            <b>{{ number_format($files->count()) }}</b> <span class="sub">total files on disk</span><br>
            <b>{{ formatBytes($totalFS) }}</b> <span class="sub">total storage being used on disk</span><br>
            <b>{{ $lastCheck->diffForHumans() }}</b> <span class="sub">last checked time (cache)</span><br>
        </div>
        <div class="col-1">
            <b>0</b> <span class="sub">jobs running</span><br>
            <b>0</b> <span class="sub">jobs failed</span><br>
            <b>{{ \App\Models\Transcode::all()->count() }} / {{ number_format($files->count()) }}</b> files processed<span class="sub"></span><br>
        </div>
        <div class="col-1">
	    <a href="{{ route('cmusic.forceMiss') }}">
		<button>recheck folders</button>
	    </a>

	    <a href="{{ route('cmusic.jobs.processAll') }}">
		<button>run jobs for all</button>
	    </a>
	</div>
        <div class="col-1"></div>
    </div><br>

    <div class="flex">
        <div class="col-1">
            <table>
                <tr>
                    <th>file path</th>
                    <th>file size</th>
                </tr>

                @forelse($files as $file)
                    @php
                        $tc = \App\Models\Transcode::where('file_name', $file->filePath)->first();
                    @endphp

                    @if (!$tc)
                        <tr>
                            <td>{{ $file->filePath }}</td>
                            <td style="color: {{ gradientTarget("#000000", $file->fileSize) }}">{{ formatBytes($file->fileSize) }}</td>
                        </tr>
                    @else
                        <tr>
                            <td>{{ $file->filePath }} <b>[tc]</b></td>
                            <td style="color: {{ gradientTarget("#000000", $file->fileSize) }}">{{ formatBytes($file->fileSize) }}</td>
                        </tr>
                    @endif
                @empty
                    There is no files being tracked.
                @endforelse
            </table>
        </div>
        <div class="col-1">
            <table>
                <tr>
                    <th>job #</th>
                    <th>target file</th>
                    <th>status</th>
                    <th>file hash</th>
                </tr>
            
                @foreach($jobs as $job)
                    <tr>
                        <td>{{ $job->id }}</td>
                        <td>{{ $job->file_path }}</td>
                        <td style="background-color:{{ $job->getColor() }}">{{ $job->job_status }}</td>
                        <td>{{ $job->file_hash }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>

@endsection
