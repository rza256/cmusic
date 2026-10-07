@extends('base', [
    'sTab' => 'songs'
])
@section('title', 'songs')
@php
$c = []; // buffer of albums
$rows = [
    "bpm",
    "date",
    "bitrate",
    "publisher",
    "year",
    "genre",
];
@endphp
@section('content')
    <div class="flex">
        <div class="col-1 left-side">
            <div id="audio-container"></div><br>

            <b>audio metadata</b>
            <table>
                <tr>
                    <th></th>
                    <th></th>
                </tr>
            
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row }}</td>
                        <td class="meta-row" data-row="{{ $row }}"><i class="sub">unknown</i></td>
                    </tr>
                @endforeach
            </table><br>
            <b>queue</b>
            <table>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            
                <tr class="queue-template">
                    <td class="author"></td>
                    <td class="title"></td>
                    <td class="play"></td>
                </tr>
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
