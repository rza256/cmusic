@php ($tabs = ["home", "songs", "queue", "jobs"])
<!DOCTYPE html>
<html>
    <head>
        <title>{{ strtolower(config('app.name')) }} - @yield('title')</title>
        @vite(['resources/js/app.js'])
        @vite(['resources/scss/app.scss'])
    </head>
    <body>
        <div class="header">
            <div class="header-left">
                <img class="logo" src="{{ asset('/logo.png') }}">
            </div>
            <div class="header-right">
                <div class="header-top">
                    <h1>@yield('title') : cmusic</h1>
                    <div class="fr">
                        <form>
                            <input type="text">
                            <select>
                                <option value="">all</option>
                                <option value="">title</option>
                                <option value="">author</option>
                                <option value="">album</option>
                            </select>
                            <input type="submit" value="search">
                        </form>
                    </div>
                </div>
                <div class="header-low sub">
                    <div>
                        unknown artist - unknown title (0:24)
                    </div>
                    <div>
                        
                    </div>
                </div>
            </div>
        </div>
        <div class="pages">
            <div class="options">
                @foreach($tabs as $tab)
                    <div class="option @if ($sTab == $tab) selected @endif">
                        <a href="{{ route('cmusic.' . $tab) }}">{{ $tab }}</a>
                    </div>
                @endforeach

                @yield('options')
            </div>
        </div>
        @yield('content')
    </body>
</html>