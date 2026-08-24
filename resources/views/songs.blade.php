@extends('base', [
    'sTab' => 'songs'
])
@section('title', 'songs')
@php
$c = []; // buffer of albums
@endphp
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
                @if (!in_array($file->metadata['album'] ?? "unknown", $c, false))
                <tr class="ignore-color">
                    <td class="cover">
                    </td>
                    <td class="track">
                        <b>{!! $file->metadata['album'] ?? "<i>unknown</i>" !!}</b>
                    </td>
                    <td class="album"></td>
                    <td class="title"></td>
                    <td class="artist"></td>
                    <td class="actions"></td>
                    <td class="hash"></td>
                    <td class="size"></td>
                </tr>
                @endif

                @php
                    $c[] = $file->album ?? 'unknown';
                @endphp

                <tr>
                    <td class="cover">
                        <a href="{{ route('cmusic.meta.cover', ['id' => $file->id]) }}">
                            <img style="width: 16px; height:16px;" src="{{ route('cmusic.meta.cover', ['id' => $file->id]) }}">
                        </a>
                    </td>
                    <td class="track">
                        {!! $file->metadata['track_number'] ?? "<i>?</i>" !!} / {!! $file->metadata['totaltracks'] ?? "<i>?</i>" !!}
                    </td>
                    <td class="album">
                        <a href="{{ route('cmusic.songs', ['album' => $file->album]) }}">
                            {!! $file->metadata['album'] ?? "<i>unknown</i>" !!}
                        </a>
                    </td>
                    <td class="title">
                        {{ $file->metadata['title'] ?? $file->metadata['filename'] }}
                    </td>
                    <td class="artist">
                        <a href="{{ route('cmusic.songs', ['artist' => $file->artist]) }}">
                            {!! $file->metadata['artist'] ?? "<i>unknown</i>" !!}
                        </a>
                    </td>
                    <td class="actions">
                        <a href="#" class="playSong_js" data-id="{{ $file->id }}" title="play">pl</a> 
                        <a href="" title="add queue">aq</a> 
                        <a href="" title="view album">va</a>
                        <a href="{{ route('cmusic.meta.file', ['id' => $file->id]) }}" title="get raw">rw</a>
                    </td>
                    <td class="hash"><code>{{ $file->file_hash }}</code></td>
                    <td class="size" style="color: {{ gradientTarget("#000000", $file->file_size) }}">{{ formatBytes($file->file_size) }}</td>
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
