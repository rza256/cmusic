const baseUrl = 'http://localhost'

let currentSong = -1;
let pageStates = {};
let playStatus = "sequential";
let queue = [];
let queueIndex = 0;
let currentLyricsCache = "";
let lrcLines = [];
var lrc = null; // new Lyric();

$().ready(function() {
    const audio = $('.audio_js')[0];
    const seek = $('#seek');
    const volume = $('#volume');

    // sound related
    const lastTs = localStorage.getItem("lastTimestamp") ?? 0;
    const lastSong = localStorage.getItem("lastSong") ?? -1;
    const lastGain = localStorage.getItem("lastGain") ?? 100;

    // search related
    const searchTerm = localStorage.getItem("searchTerm") ?? "";
    const searchType = localStorage.getItem("searchType") ?? "sequential";
    const playOrder = localStorage.getItem("playOrder") ?? "all";

    // queue
    const queueOrder = localStorage.getItem("queueOrder");
    const queueIndex_ = localStorage.getItem("queueIndex");

    //
    if(queueOrder == null) {
        queue = []; 
        localStorage.setItem("queueOrder", JSON.stringify(queue))
    } else {
        console.log('found queueOrder' , queueOrder)
        queue = JSON.parse(queueOrder);
    }

    playStatus = playOrder === null ? "sequential" : playOrder;
    currentSong = lastSong === null ? -1 : lastSong;
    queueIndex = queueIndex_ === null ? 0 : Number(queueIndex_);

    volume.val(lastGain);
    audio.volume = lastGain / 100;
    audio.currentTime = lastTs;

    if (lastSong !== null)
    {
        loadSong(lastSong, false, true);
    }

    // set & trigger
    console.log(playOrder)
    console.log(searchType)
    console.log(searchTerm)
    $('.playSequenceJs').val(playOrder).trigger("input");
    $('.searchTypeJs').val(searchType).trigger("input");
    $('.searchQueryJs').val(searchTerm).trigger("input");

    $('.dynamic').each(function(i, obj) {
        searchDynamic()

        console.log(pageStates)
    });
    
    $('.playSequenceJs').on('input', function(e) {
        console.log(e);
        playStatus = $(this).val();
        localStorage.setItem("playOrder", playStatus);
    })

    $('.searchTypeJs').on('input', function(e) {
        console.log('search type changed');
        localStorage.setItem("searchType", $(this).val());
        searchDynamic()
    })

    // https://stackoverflow.com/questions/17384218/jquery-input-event
    $('.searchQueryJs').on('propertychange input', function (e) {
        var valueChanged = false;

        if (e.type=='propertychange') {
            valueChanged = e.originalEvent.propertyName=='value';
        } else {
            valueChanged = true;
        }
        if (valueChanged) {
            localStorage.setItem("searchTerm", $(this).val());
            searchDynamic()
        }
    });
})

function searchDynamic() {
    let q = ($('.searchQueryJs').val());
    let searchType = ($('.searchTypeJs').val());

    let url = new URL(baseUrl + '/d/songs')
    url.searchParams.append('q', q);
    url.searchParams.append('t', searchType);

    loadDynamic(url, 'songs')
}

function loadDynamic(url, type) {
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'html',
        success: function(res) {
            // console.log(res);
            // grab pagination (if it exists) and put
            // it in the thing

            $('.dynamic[data-name="' + type + '"]').html(res);
            $('.songRow[data-id="' + currentSong + '"]').addClass('playing');

            $('.pagination-dynamic').html('');
            let pag = $('.pagination-default').detach();
            $('.pagination-dynamic').append(pag);

            $('.playSong_js').on('click', function(event) {
                event.preventDefault();
                
                let id = $(this).data('id');
                loadSong(id);
            });

            $('.passthrough').each(function(i, obj) {
                $(this).on('click', function(event) {
                    event.preventDefault();

                    let href = ($(this).attr('href'));

                    // check if page
                    let url = new URL(href);
                    let searchParams = new URLSearchParams(url.search)
                
                    console.log(searchParams.has('page')) 
                    console.log(searchParams) 

                    url.pathname = '/d/songs'

                    loadDynamic(url, 'songs');
                })
            });

            $('.js_searchAlbum').on('click', function(event) {
                event.preventDefault();

                $('.searchQueryJs').val($(this).data('term')).trigger('input')
                $('.searchTypeJs').val('album').trigger('input')
            })

            $('.js_searchArtist').on('click', function(event) {
                event.preventDefault();

                $('.searchQueryJs').val($(this).data('term')).trigger('input')
                $('.searchTypeJs').val('author').trigger('input')
            })
        }
    });
}

String.prototype.toHHMMSS = function () {
    var sec_num = parseInt(this, 10); // don't forget the second param
    var hours   = Math.floor(sec_num / 3600);
    var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
    var seconds = sec_num - (hours * 3600) - (minutes * 60);

    if (hours   < 10) {hours   = "0"+hours;}
    if (minutes < 10) {minutes = "0"+minutes;}
    if (seconds < 10) {seconds = "0"+seconds;}
    return hours+':'+minutes+':'+seconds;
}

function loadSong(id, shouldPush = true, resume = false) {
    id = Number(id);

    console.warn('loadSong:', id);
    console.warn('queue before:', queue);
    console.warn('queueIndex before:', queueIndex);

    currentSong = id;

    if (shouldPush) {
        queue.push(id);
        queueIndex = queue.length - 1;
    }

    localStorage.setItem("queueOrder", JSON.stringify(queue));
    localStorage.setItem("queueIndex", queueIndex);

    console.warn('queue after:', queue);
    console.warn('queueIndex after:', queueIndex);

    let url = baseUrl + '/meta/file/' + id;
    let json = baseUrl + '/meta/json/' + id;

    $.ajax({
        url: baseUrl + '/plugins/hooks/play/' + id + "?resume=" + (resume ? "true" : "false"),
        type: 'POST',
        dataType: 'json',
        success: function(res) { console.log(res); }
    });

    // grab lyrics. does it exist? if not, it returns a 404.
    $.ajax({
        url: baseUrl + '/meta/lyrics/' + id,
        type: 'GET',
        success: function(res) { 
            console.log(res);
            currentLyricsCache = res;

            lrc = new Lyric({
                onPlay: function (line, text) {
                    console.log(lrcLines[line].text + '\n' + lrcLines[line].extendedLyrics.join('\n'))
                    // console.log(lrc.lines[lrc.curLineNum].time - lrc.offset - dom_audio.currentTime * 1000)
                    // dom_lyric.innerHTML = text + '<br>' + lrcLines[line].extendedLyrics.join('<br>')
                
                    $('.header-top-text').text(lrcLines[line].text);
                },
                onSetLyric: function (lines) {
                    lrcLines = lines
                    console.log(lines)
                }
            })
            // lrc.setLyric(b64DecodeUnicode(encodeLrc), b64DecodeUnicode(encodeLrc))
            lrc.setLyric(res)

            /*
            dom_audio.onplay = function () {
                lrc.play(dom_audio.currentTime * 1000)
            }
            dom_audio.onpause = function () {
                lrc.pause()
            }*/
        },
        error: function(res) {
            console.log('failed to get currentLyricsCache');
            currentLyricsCache = "";
        }
    });

    $.ajax({
        url: json,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            playSong(res, url, id);
            play()
        }
    });
}

function playSong(meta, url, id) {
    currentSong = id;

    $('.songRow').removeClass('playing');

    const row = $('.songRow[data-id="' + id + '"]');
    row.addClass('playing');

    const audio = $('.audio_js')[0];
    audio.src = url;
    audio.load();
    audio.play();

    $('#seek').attr('max', Math.floor(meta.metadata.duration_seconds));

    $('.artist_js').html(meta.metadata.artist ?? "<i>unknown</i>");
    $('.title_js').html(meta.metadata.title ?? "<i>" + meta.metadata.filename + "</i>");

    $('.logo').attr('src', baseUrl + '/meta/cover/' + id);

    if ('mediaSession' in navigator) {

    navigator.mediaSession.metadata = new MediaMetadata({
        title: meta.metadata.title ?? "unknown title",
        artist: meta.metadata.artist ?? "unknown artist",
        album: meta.metadata.album ?? "unknown title",
        artwork: [
        { src: baseUrl + '/meta/cover/' + id, sizes: '512x512', type: 'image/png' },
        ]
    });
    }

    navigator.mediaSession.setActionHandler('play', () => { $('.play_js').trigger('click') });
    navigator.mediaSession.setActionHandler('pause', () => { $('.pause_js').trigger('click') });
    navigator.mediaSession.setActionHandler('stop', () => { $('.pause_js').trigger('click') });
    navigator.mediaSession.setActionHandler('seekforward', () => { /* Code excerpted. */ });
    navigator.mediaSession.setActionHandler('seekto', () => { /* Code excerpted. */ });
    navigator.mediaSession.setActionHandler('previoustrack', () => { triggerPreviousSong() });
    navigator.mediaSession.setActionHandler('nexttrack', () => { triggerNextSong() });
}

const audio = $('.audio_js')[0];
const seek = $('#seek');

audio.addEventListener('timeupdate', () => {
    seek.val(audio.currentTime);
    localStorage.setItem("lastTimestamp", audio.currentTime);
    localStorage.setItem("lastSong", currentSong);
    $('.timestamp_js').text(audio.currentTime.toString().toHHMMSS())
});

seek.on('input', function () {
    if (currentLyricsCache != "") {
        lrc.play(audio.currentTime * 1000)
    }
    console.log(audio.currentTime * 1000)
    audio.currentTime = this.value;
});

audio.addEventListener('loadedmetadata', () => {
    seek.attr('max', audio.duration);
});

function getRandomArbitrary(min, max) {
    return Math.random() * (max - min) + min;
}

function printQueuePosition() {
    // Ugggghhhhhhhhhhhhhhhhhhhhhhhh
    for (let i = 0; i < queue.length; i++) {
        queueIndex == i ? console.warn(queue[i]) : console.log(queue[i]);
    }
}

function triggerPreviousSong() {
    console.log('GOING BACK', queueIndex, queue)
    // go back into the queue, if there's no more songs before
    if (queueIndex > 0)
    {
        queueIndex--;
        localStorage.setItem("queueIndex", queueIndex);
        loadSong(queue[queueIndex], false);
    }
    else
    {
        loadSong(queue[0], false);
    }
}

function triggerNextSong() {
    console.log('GOING NEXT', queueIndex, queue)

    // check, does the queue have a song that's already in front??
    if (queueIndex + 1 < queue.length) {
        queueIndex++;
        loadSong(queue[queueIndex], false);
        return;
    }

    if (playStatus == "sequential") {
        let trNext = $('.songRow[data-id="' + currentSong + '"]')
            .nextAll('.songRow')
            .first();

        let nextSong = trNext.data('id');

        loadSong(nextSong);
    } else if (playStatus == "sequentialUp") {
        let trPrev = $('.songRow[data-id="' + currentSong + '"]')
            .prevAll('.songRow')
            .first();

        let nextSong = trPrev.data('id');

        loadSong(nextSong);
    } else if (playStatus == "shuffle") {
        $.ajax({
            url: baseUrl + '/meta/song_count',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                console.log(res, 'res result');
                let nextSong = Math.floor(
                    Math.random() * res.files
                );
                console.log(nextSong, "random next song");
                loadSong(nextSong);
            }
        });
    } else {
        let nextSong = Number(currentSong) + 1;
        loadSong(nextSong);
    }
}

audio.addEventListener('ended', () => {
    triggerNextSong();
});

$('#volume').on('input', function () {
    localStorage.setItem("lastGain", $(this).val());
    $('.audio_js')[0].volume = $(this).val() / 100;
});

$('.play_js').on('click', function() {
    play()
});

function play() {
    let audio = $('.audio_js')[0];
    audio.play();
    if (currentLyricsCache != "") {
        console.log('playing with lyrics')
        lrc.play(audio.currentTime * 1000)
    }
}

$('.pause_js').on('click', function() {
    let audio = $('.audio_js')[0];
    audio.pause();
    if (currentLyricsCache != "") {
        // console.log('playing with lyrics')
        lrc.pause()
    }
});

$('.next_js').on('click', function() {
    triggerNextSong();
})

$('.previous_js').on('click', function() {
    triggerPreviousSong();
})

