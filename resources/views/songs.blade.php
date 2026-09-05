@extends('base', [
    'sTab' => 'songs'
])
@section('title', 'songs')
@php
$c = []; // buffer of albums
@endphp
@section('content')
    <div class="dynamic" data-name="songs"></div>
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
