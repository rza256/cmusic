@extends('base', [
    'sTab' => 'settings'
])
@section('title', 'settings')
@php
$c = []; // buffer of albums
@endphp
@section('content')
    <table>
        <tr>
            <th>setting #</th>
            <th>name</th>
            <th>value</th>
        </tr>
    
        @foreach($settings as $setting)
            <tr>
                <td>{{ $setting->id }}</td>
                <td>{{ $setting->setting_name }}</td>
                <td>{{ $setting->setting_value }}</td>
            </tr>
        @endforeach
    </table>
@endsection
