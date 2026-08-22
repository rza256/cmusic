@extends('base', [
    'sTab' => 'songs'
])
@section('title', 'songs')
@section('content')
    <table>
        <tr>
            <th>cover</th>
            <th>track</th>
            <th>album</th>
            <th>title</th>
            <th>artist</th>
            <th>actions</th>
            <th>hash</th>
            <th>file size</th>
        </tr>
	
            @forelse($songs as $file)
                <tr>
                    <td>
                        <a href="{{ route('cmusic.meta.cover', ['id' => $file->id]) }}">
                            <img style="width: 16px; height:16px;" src="{{ route('cmusic.meta.cover', ['id' => $file->id]) }}">
                        </a>
                    </td>
                    <td>{!! $file->metadata['track_number'] ?? "<i>?</i>" !!} / {!! $file->metadata['totaltracks'] ?? "<i>?</i>" !!}</td>
                    <td>{!! $file->metadata['album'] ?? "<i>unknown</i>" !!}</td>
                    <td>{{ $file->metadata['title'] ?? $file->metadata['filename'] }}</td>
                    <td>{!! $file->metadata['artist'] ?? "<i>unknown</i>" !!}</td>
                    <td>
                        <a href="#" class="playSong_js" data-id="{{ $file->id }}" title="play">pl</a> 
                        <a href="" title="add queue">aq</a> 
                        <a href="" title="view album">va</a>
                        <a href="{{ route('cmusic.meta.file', ['id' => $file->id]) }}" title="get raw">rw</a>
                    </td>
                    <td><code>{{ $file->file_hash }}</code></td>
                    <td style="color: {{ gradientTarget("#000000", $file->file_size) }}">{{ formatBytes($file->file_size) }}</td>
                </tr>
            @empty
                There is no files being tracked.
            @endforelse
    </table><br>
@endsection
@section('options')
    <div class="fr optr">
        <span class="sub"><b>{{ \App\Models\File::all()->count() }}</b> files tracked</span>
        {{ $songs->links('pagination') }}
    </div>
@endsection
