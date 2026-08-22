@php ($tabs = ["home", "songs", "queue", "jobs"])
<!DOCTYPE html>
<html>
    <head>
        <title>{{ strtolower(config('app.name')) }} - @yield('title')</title>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/4.0.0/jquery.min.js" integrity="sha512-8LENNbXmzI/Gbj+OwXmqR6V4QaUAw0/porPzy1+dQoJqC0JPHedWoe0DDOTL2uHA5XXJyIsPtiMHH86pVlay6A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
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
                        <span class="artist_js">unknown artist</span> - <span class="title_js">unknown title</span> (<span class="timestamp_js">0:24</span>)

                        <audio controls class="audio_js" style="display:none;">
                            <source src="">
                            Your browser does not support the audio element.
                        </audio> 
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