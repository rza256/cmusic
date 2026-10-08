const baseUrl = window.location.protocol + "//" + window.location.host;

let currentSong = -1;
let pageStates = {};
let playStatus = "sequential";
let queue = [];
let queueIndex = 0;
let currentLyricsCache = "";
let lrcLines = [];
var lrc = null; // new Lyric();
const QUEUE_MAX = 20;
let queueMeta = {};
let afterLoad = null; 
if ("audioSession" in navigator) {
    navigator.audioSession.type = "playback";
}

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

    queue = queueOrder == null ? [] : JSON.parse(queueOrder);
    queueMeta = JSON.parse(localStorage.getItem("queueMeta") ?? "{}");

    // if the stored queue was longer than the cap, trim it and shift the index to match
    const trimmed = Math.max(0, queue.length - QUEUE_MAX);
    queue = queue.slice(trimmed);

    playStatus = playOrder === null ? "sequential" : playOrder;
    currentSong = lastSong === null ? -1 : lastSong;
    queueIndex = queueIndex_ === null ? 0 : Math.max(0, Number(queueIndex_) - trimmed);

    saveQueue();
    renderQueue();
    queue.forEach(fetchQueueMeta); // only fetches ids we don't already have cached
    
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

    // spectrum

    // audio source
    const audioEl = $('.audio_js')[0];

    // instantiate analyzer
    console.log(AudioMotionAnalyzer)
    const audioMotion = new AudioMotionAnalyzer (
    $('#audio-container')[0],
    {
        source: audioEl,
        height: 100,
        // you can set other options below - check the docs!
        mode: 3,
        barSpace: 4,
        ledBars: true,
        ansiBands: true,
        overlay: false,
        loRes: true,
        maxFps: 60,
        frequencyScale: 'linear',
        trueLeds: true,
    }
    );

    // display module version
    document.getElementById('version').innerText = `v${AudioMotionAnalyzer.version}`;

    // play stream
    document.getElementById('live').addEventListener( 'click', () => {
    audioEl.src = 'https://icecast2.ufpel.edu.br/live';
    audioEl.play();
    });

    // file upload
    document.getElementById('upload').addEventListener( 'change', e => {
        const fileBlob = e.target.files[0];

        if ( fileBlob ) {
            audioEl.src = URL.createObjectURL( fileBlob );
            audioEl.play();
        }
    });
    
})

function pageOf(href) {
    return Number(new URL(href).searchParams.get('page')) || 1;
}


function goToAdjacentPage(direction, onLoaded) {
    const sel = $('.pagination-dynamic .selected');
    const links = $('.pagination-dynamic a.passthrough');

    if (!sel.length || !links.length) return false;

    const current = Number(sel.text().trim());
    const target = current + direction;

    const max = Math.max(current, ...links.map(function() { return pageOf(this.href); }).get());
    if (target < 1 || target > max) return false;

    afterLoad = onLoaded;

    const link = links.filter(function() { return pageOf(this.href) === target; }).first();

    if (link.length) {
        link.trigger('click'); 
    } else {
        const url = new URL(links.first().attr('href'));
        url.searchParams.set('page', target);
        url.pathname = '/d/songs';
        loadDynamic(url, 'songs');
    }
    return true;
}

function playEdgeRow(first) {
    const rows = $('.songRow');
    if (!rows.length) return;
    loadSong((first ? rows.first() : rows.last()).data('id'));
}

function saveQueue() {
    // only keep meta for songs still in the queue
    const keep = {};
    queue.forEach(id => { if (queueMeta[id]) keep[id] = queueMeta[id]; });
    queueMeta = keep;

    localStorage.setItem("queueOrder", JSON.stringify(queue));
    localStorage.setItem("queueIndex", queueIndex);
    localStorage.setItem("queueMeta", JSON.stringify(queueMeta));
}


function trimQueue() {
    while (queue.length > QUEUE_MAX) {
        queue.shift();
        queueIndex = Math.max(0, queueIndex - 1);
    }
}

function cacheQueueMeta(id, res) {
    const m = res.metadata ?? {};
    queueMeta[id] = {
        artist: m.artist ?? res.artist ?? null,
        title: m.title ?? res.title ?? m.filename ?? null,
    };
}

function fetchQueueMeta(id) {
    if (queueMeta[id]) return;

    $.ajax({
        url: baseUrl + '/meta/json/' + id,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            cacheQueueMeta(id, res);
            saveQueue();
            renderQueue();
        }
    });
}

function renderQueue() {
    const tpl = $('.queue-template');
    $('.queue-row').remove();

    queue.forEach(function(id, i) {
        const meta = queueMeta[id];
        const row = tpl.clone()
            .removeClass('queue-template')
            .addClass('queue-row')
            .attr('data-index', i)
            .attr('data-id', id);

        row.find('td.author').html(meta?.artist ?? '<i class="sub">unknown</i>');
        row.find('td.title').html(meta?.title ?? '<i class="sub">loading…</i>');

        const playCell = row.find('td.play');
        const link = playCell.find('a, button').first();
        if (link.length) {
            link.attr('data-id', id).addClass('queue_play_js');
        } else {
            playCell.html('<a href="#" class="queue_play_js" data-id="' + id + '">&#9654;</a>');
        }

        if (i === queueIndex) row.addClass('playing');

        tpl.parent().append(row);
    });
}

$(document).on('click', '.queue_play_js', function(e) {
    e.preventDefault();
    const i = Number($(this).closest('.queue-row').data('index'));
    queueIndex = i;
    saveQueue();
    loadSong(queue[i], false);
});

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


            if (afterLoad) {
                const cb = afterLoad;
                afterLoad = null;
                cb();
            }
        },
        error: function() {
            afterLoad = null;
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
        trimQueue();
        queueIndex = queue.length - 1;
    }

    saveQueue();
    renderQueue();

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
    // but ALSO metadata can contain lyrics
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
            $('.header-top-text').text('songs : cmusic');
            console.log('failed to get currentLyricsCache');
            currentLyricsCache = "";
        }
    });

    $.ajax({
        url: json,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            $('.meta-row').each(function() {
                $(this).html('<i class="sub">unknown</i>');
            })

            console.log(res.metadata);

            cacheQueueMeta(id, res);
            saveQueue();
            renderQueue();

            for (const [key, value] of Object.entries(res.metadata)) {
                $('td[data-row="' + key + '"]').text(value);
            }

            // lyrics

            if (res.metadata.lyrics !== null) {
                currentLyricsCache = res.metadata.lyrics;

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
                lrc.setLyric(res.metadata.lyrics)
            } else {
                $('.header-top-text').text('songs : cmusic');
            }

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
        { src: baseUrl + '/meta/cover/' + id, sizes: '1024x1024', type: 'image/png' },
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
        let row = $('.songRow[data-id="' + currentSong + '"]');
        if (!row.length) return;

        let trNext = row.nextAll('.songRow').first();

        if (trNext.length) {
            loadSong(trNext.data('id'));
        } else {
            goToAdjacentPage(+1, function() { playEdgeRow(true); });
        }
    } else if (playStatus == "sequentialUp") {
        let row = $('.songRow[data-id="' + currentSong + '"]');
        if (!row.length) return;

        let trPrev = row.prevAll('.songRow').first();

        if (trPrev.length) {
            loadSong(trPrev.data('id'));
        } else {
            goToAdjacentPage(-1, function() { playEdgeRow(false); });
        }
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


audio.addEventListener('error', function failed(e) {
    // audio playback failed - show a message saying why
    // to get the source of the audio element use $(this).src

    console.error($(this).src)

    switch (e.target.error.code) {
        case e.target.error.MEDIA_ERR_ABORTED:
        console.log('You aborted the video playback.');
        // triggerNextSong();
        break;
        case e.target.error.MEDIA_ERR_NETWORK:
        console.log('A network error caused the audio download to fail.');
        triggerNextSong();
        break;
        case e.target.error.MEDIA_ERR_DECODE:
        console.log('The audio playback was aborted due to a corruption problem or because the video used features your browser did not support.');
        triggerNextSong();
        break;
        case e.target.error.MEDIA_ERR_SRC_NOT_SUPPORTED:
            triggerNextSong();
        console.log('The video audio not be loaded, either because the server or network failed or because the format is not supported.');
        break;
        default:
            triggerNextSong();
        console.log('An unknown error occurred.');
        break;
    }
}, true);

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

