@extends('base', [
    'sTab' => 'jobs'
])
@section('title', 'home')
@section('content')
    <b>{{ number_format($files->count()) }}</b> <span class="sub">total files on disk</span><br>
    <b>{{ formatBytes($totalFS) }}</b> <span class="sub">total storage being used on disk</span><br>
    <b>{{ $lastCheck->diffForHumans() }}</b> <span class="sub">last checked time (cache)</span>

    @foreach($files as $file)
    @endforeach
@endsection