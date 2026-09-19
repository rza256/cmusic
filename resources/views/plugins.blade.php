@extends('base', [
    'sTab' => 'plugins'
])
@section('title', 'plugins')
@section('content')
    @forelse($plugins as $plugin)
        <b>{{ $plugin->plugin_name }}</b> 
        <span class="sub">{{ $plugin->author }} ({{ $plugin->plugin_version }})</span><br>
        {{ $plugin->plugin_description }}

        <a href="{{ route('cmusic.plugin', ['plugin' => $plugin->plugin_qualifying]) }}">options</a>
        <a href="">disable</a>
    @empty
        No plugins available.
    @endforelse
@endsection