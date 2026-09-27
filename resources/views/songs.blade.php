@extends('base', [
    'sTab' => 'songs'
])
@section('title', 'songs')
@php
$c = []; // buffer of albums
$rows = [
    "bpm",
    "upc",
    "url",
    "date",
    "isrc",
    "bitrate",
    "encoder",
    "copyright",
];
@endphp
@section('content')
    <div class="flex">
        <div class="col-1">
            <b>audio metadata</b>
            <table>
                <tr>
                    <th></th>
                    <th></th>
                </tr>
            
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row }}</td>
                        <td data-row="{{ $row }}"></td>
                    </tr>
                @endforeach
            </table>
        </div>
        <div class="col-2">
            <div class="dynamic" data-name="songs"></div>
        </div>
    </div>
@endsection
@section('options')
    <span class="sub">filters: </span>
    <a class="passthrough sub filter_js" href="{{ route('cmusic.songs', ['fileType' => 'all']) }}" data-type="all">all</a>
    <a class="passthrough sub filter_js" href="{{ route('cmusic.songs', ['fileType' => 'transcodes']) }}" data-type="transcodes">transcodes</a>
    <div class="fr optr">
        <div class="pagination-dynamic"></div>
        <span class="sub"><b>{{ \App\Models\File::all()->count() }}</b> files tracked</span>
    </div>
@endsection
