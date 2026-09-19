@extends('base', [
    'sTab' => 'plugins'
])
@section('title', 'plugins')
@section('content')
    <h2>{{ $plugin->getAuthorInfo()->plugin_name }}</h2><br>
    <form method="POST" action="{{ route('cmusic.plugin.settings', ['plugin' => $plugin->getAuthorInfo()->plugin_qualifying]) }}">
        @foreach ($plugin->getFields()->get() as $field)
            @if ($field->isText())
                {!! $field->getValue() !!}<br>
            @endif

            @if (!$field->isText())
                <b>{{ $field->getTitle() }}:</b> 
                <input type="text" {{ $field->isModifiable() == 0 ? "disabled" : "" }} placeholder="{{ $field->getPlaceholder() }}" name="{{ $field->getKey() }}" value="{{ $field->getValue() }}"><br>
            @endif
        @endforeach

        <input type="submit" value="modify">
    </form>
@endsection