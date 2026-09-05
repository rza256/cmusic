@php ($tabs = ["home", "songs", "queue", "jobs", "transcodes"])
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
                        <form class="searchJs">
                            <input type="text" class="searchQueryJs" name="query">
                            <select class="searchTypeJs" name="type">
                                <option value="all">all (metadata search)</option>
                                <option value="filename">filename</option>
                                <option value="title">title</option>
                                <option value="author">author</option>
                                <option value="album">album</option>
                            </select>
                        </form>
                    </div>
                </div>
                <div class="header-low sub">
                    <div>
                        <div class="flex">
                            <div class="col-3">
                                <span class="artist_js">unknown artist</span> - <span class="title_js">unknown title</span> (<span class="timestamp_js">0:00</span>)

                                <button title="play" class="play_js">pl</button>
                                <button title="pause" class="pause_js">pa</button>
                                <button title="repeat" class="repeat_js">rp</button>
                                <button title="shuffle" class="shuffle_js">sf</button>
                            </div>
                            <div class="col-5">
                                <input type="range" id="seek" name="seek" min="0" max="0" />
                            </div>
                            <div class="col-1">
                                <input type="range" id="volume" name="volume" min="0" max="100" />
                            </div>
                        </div>

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