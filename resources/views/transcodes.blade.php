@extends('base', [
    'sTab' => 'transcodes'
])
@section('title', 'transcodes')
@section('content')
    @forelse($transcodes as $tc)
        
    @empty
        No transcodes available.
    @endforelse
@endsection