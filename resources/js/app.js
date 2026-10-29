const baseUrl = window.location.protocol + "//" + window.location.host;

let currentSong = -1;
let playStatus = "sequential";
let queue = [];
let queueIndex = 0;
let currentLyricsCache = "";
let g_shouldPush = false;
let lrcLines = [];
var lrc = null; // new Lyric();
const QUEUE_MAX = 20;
let queueMeta = {};
let queueMetaRequested = new Set(); // ids we already asked the server about this session
let afterLoad = null;
let resumeAt = 0; // timestamp to jump to once the restored song loads
let dynamicRequest = null; // AbortController for the in-flight loadDynamic
if ("audioSession" in navigator) {
    navigator.audioSession.type = "playback";
}

const audio = document.querySelector('.audio_js');
const seek = document.querySelector('#seek');
const volume = document.querySelector('#volume');

// little helpers so the rest doesn't need jquery

// click handler that also works for stuff that gets swapped in later (the dynamic table)
function onClick(selector, handler) {
    document.addEventListener('click', function(e) {
        const el = e.target.closest(selector);
        if (!el) return;
        e.preventDefault();
        handler(el, e);
    });
}

async function getJson(url, options = {}) {
    const res = await fetch(url, options);
    if (!res.ok) throw new Error(res.status + ' ' + url);
    return res.json();
}

async function getText(url, options = {}) {
    const res = await fetch(url, options);
    if (!res.ok) throw new Error(res.status + ' ' + url);
    return res.text();
}

// sets text, or an <i> placeholder if there's nothing. never use innerHTML for metadata
function setTextOr(el, value, placeholder, placeholderClass = '') {
    if (value != null && value !== '') {
        el.textContent = value;
        return;
    }
    const i = document.createElement('i');
    if (placeholderClass) i.className = placeholderClass;
    i.textContent = placeholder;
    el.replaceChildren(i);
}

function setHeaderText(text) {
    document.querySelector('.header-top-text').textContent = text;
}

document.addEventListener('DOMContentLoaded', function() {
    // sound related
    const lastTs = Number(localStorage.getItem("lastTimestamp") ?? 0);
    const lastSong = Number(localStorage.getItem("lastSong") ?? -1);
    const lastGain = localStorage.getItem("lastGain") ?? 100;

    // search related
    const searchTerm = localStorage.getItem("searchTerm") ?? "";
    const searchType = localStorage.getItem("searchType") ?? "all";
    const playOrder = localStorage.getItem("playOrder") ?? "sequential";

    // queue
    const queueOrder = localStorage.getItem("queueOrder");
    const queueIndex_ = localStorage.getItem("queueIndex");

    queue = queueOrder == null ? [] : JSON.parse(queueOrder);
    queueMeta = JSON.parse(localStorage.getItem("queueMeta") ?? "{}");

    // if the stored queue was longer than the cap, trim it and shift the index to match
    const trimmed = Math.max(0, queue.length - QUEUE_MAX);
    queue = queue.slice(trimmed);

    playStatus = playOrder;
    queueIndex = queueIndex_ === null ? 0 : Math.max(0, Number(queueIndex_) - trimmed);

    saveQueue();
    renderQueue(); // only fetches meta for ids we don't already have cached

    volume.value = lastGain;
    audio.volume = lastGain / 100;

    if (lastSong >= 0)
    {
        resumeAt = lastTs;
        loadSong(lastSong, false, true);
    }

    // set
    document.querySelector('.playSequenceJs').value = playOrder;
    document.querySelector('.searchTypeJs').value = searchType;
    document.querySelector('.searchQueryJs').value = searchTerm;

    searchDynamic();

    document.querySelector('.playSequenceJs').addEventListener('input', function() {
        playStatus = this.value;
        localStorage.setItem("playOrder", playStatus);
    });

    document.querySelector('.searchTypeJs').addEventListener('input', function() {
        localStorage.setItem("searchType", this.value);
        searchDynamic();
    });

    document.querySelector('.searchQueryJs').addEventListener('input', function() {
        localStorage.setItem("searchTerm", this.value);
        searchDynamic();
    });

    // the search box is a <form>, don't let enter reload the page
    document.querySelector('.searchJs').addEventListener('submit', e => e.preventDefault());

    if ('mediaSession' in navigator) {
        navigator.mediaSession.setActionHandler('play', () => { play() });
        navigator.mediaSession.setActionHandler('pause', () => { pause() });
        navigator.mediaSession.setActionHandler('stop', () => { pause() });
        navigator.mediaSession.setActionHandler('seekforward', (details) => { audio.currentTime += details.seekOffset ?? 10 });
        navigator.mediaSession.setActionHandler('seekbackward', (details) => { audio.currentTime -= details.seekOffset ?? 10 });
        navigator.mediaSession.setActionHandler('seekto', (details) => { audio.currentTime = details.seekTime });
        navigator.mediaSession.setActionHandler('previoustrack', () => { triggerPreviousSong() });
        navigator.mediaSession.setActionHandler('nexttrack', () => { triggerNextSong() });
    }

    // spectrum

    // only the songs page has somewhere to put it
    const container = document.querySelector('#audio-container');
    if (!container) return;

    // instantiate analyzer
    const audioMotion = new AudioMotionAnalyzer (
    container,
    {
        source: audio,
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
    const version = document.getElementById('version');
    if (version) version.innerText = `v${AudioMotionAnalyzer.version}`;

    // play stream
    document.getElementById('live')?.addEventListener( 'click', () => {
    audio.src = 'https://icecast2.ufpel.edu.br/live';
    audio.play();
    });

    // file upload
    document.getElementById('upload')?.addEventListener( 'change', e => {
        const fileBlob = e.target.files[0];

        if ( fileBlob ) {
            audio.src = URL.createObjectURL( fileBlob );
            audio.play();
        }
    });
});

function pageOf(href) {
    return Number(new URL(href).searchParams.get('page')) || 1;
}


function goToAdjacentPage(direction, onLoaded) {
    const sel = document.querySelector('.pagination-dynamic .selected');
    const links = [...document.querySelectorAll('.pagination-dynamic a.passthrough')];

    if (!sel || !links.length) return false;

    const current = Number(sel.textContent.trim());
    const target = current + direction;

    const max = Math.max(current, ...links.map(a => pageOf(a.href)));
    if (target < 1 || target > max) return false;

    afterLoad = onLoaded;

    const link = links.find(a => pageOf(a.href) === target);

    if (link) {
        link.click();
    } else {
        const url = new URL(links[0].href);
        url.searchParams.set('page', target);
        url.pathname = '/d/songs';
        loadDynamic(url, 'songs');
    }
    return true;
}

function playEdgeRow(first) {
    const rows = document.querySelectorAll('.songRow');
    if (!rows.length) return;
    loadSong((first ? rows[0] : rows[rows.length - 1]).dataset.id);
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

async function fetchQueueMeta(id) {
    // renderQueue calls this a lot, so don't ask twice for the same id
    if (queueMeta[id] || queueMetaRequested.has(id)) return;
    queueMetaRequested.add(id);

    try {
        const res = await getJson(baseUrl + '/meta/json/' + id);
        cacheQueueMeta(id, res);
        saveQueue();
        renderQueue();
    } catch (err) {
        console.error('failed to get queue meta for', id, err);
    }
}

function renderQueue() {
    const tpl = document.querySelector('.queue-template');
    document.querySelectorAll('.queue-row').forEach(row => row.remove());

    // queue table only exists on the songs page
    if (!tpl) return;

    queue.forEach(function(id, i) {
        fetchQueueMeta(id);

        const meta = queueMeta[id];
        const row = tpl.cloneNode(true);
        row.classList.remove('queue-template');
        row.classList.add('queue-row');
        row.dataset.index = i;
        row.dataset.id = id;

        setTextOr(row.querySelector('td.author'), meta?.artist, 'unknown', 'sub');
        setTextOr(row.querySelector('td.title'), meta?.title, 'loading...', 'sub');

        const playCell = row.querySelector('td.play');
        let link = playCell.querySelector('a, button');
        if (!link) {
            link = document.createElement('a');
            link.href = '#';
            link.innerHTML = '&#9654;';
            playCell.replaceChildren(link);
        }
        link.dataset.id = id;
        link.classList.add('queue_play_js');

        if (i === queueIndex) row.classList.add('playing');

        tpl.parentElement.append(row);
    });
}

onClick('.queue_play_js', function(el) {
    const i = Number(el.closest('.queue-row').dataset.index);
    queueIndex = i;
    saveQueue();
    loadSong(queue[i], false);
});

// these all live inside the dynamic table, so they're delegated instead of
// rebound every time loadDynamic runs (that used to stack up handlers on .passthrough)
onClick('.playSong_js', function(el) {
    loadSong(el.dataset.id);
});

onClick('.passthrough', function(el) {
    // check if page
    let url = new URL(el.href);
    url.pathname = '/d/songs'

    loadDynamic(url, 'songs');
});

onClick('.js_searchAlbum', function(el) {
    setSearch(el.dataset.term, 'album');
});

onClick('.js_searchArtist', function(el) {
    setSearch(el.dataset.term, 'author');
});

function setSearch(term, type) {
    const query = document.querySelector('.searchQueryJs');
    const searchType = document.querySelector('.searchTypeJs');

    query.value = term;
    searchType.value = type;
    localStorage.setItem("searchTerm", term);
    localStorage.setItem("searchType", type);
    searchDynamic();
}

function searchDynamic() {
    // nothing to put results in on other pages
    if (!document.querySelector('.dynamic')) return;

    let q = document.querySelector('.searchQueryJs').value;
    let searchType = document.querySelector('.searchTypeJs').value;

    let url = new URL(baseUrl + '/d/songs')
    url.searchParams.append('q', q);
    url.searchParams.append('t', searchType);

    loadDynamic(url, 'songs')
}

async function loadDynamic(url, type) {
    // typing fast fires a bunch of these, only the latest one should win
    dynamicRequest?.abort();
    const request = dynamicRequest = new AbortController();

    let res;
    try {
        res = await getText(url, { signal: request.signal });
    } catch (err) {
        if (err.name === 'AbortError') return;
        console.error('loadDynamic failed', err);
        afterLoad = null;
        return;
    }

    // grab pagination (if it exists) and put
    // it in the thing

    document.querySelector('.dynamic[data-name="' + type + '"]').innerHTML = res;
    document.querySelector('.songRow[data-id="' + currentSong + '"]')?.classList.add('playing');

    const pagination = document.querySelector('.pagination-dynamic');
    pagination.replaceChildren();
    pagination.append(...document.querySelectorAll('.pagination-default'));

    if (afterLoad) {
        const cb = afterLoad;
        afterLoad = null;
        cb();
    }
}

function toHHMMSS(secs) {
    var sec_num = parseInt(secs, 10); // don't forget the second param
    var hours   = Math.floor(sec_num / 3600);
    var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
    var seconds = sec_num - (hours * 3600) - (minutes * 60);

    if (hours   < 10) {hours   = "0"+hours;}
    if (minutes < 10) {minutes = "0"+minutes;}
    if (seconds < 10) {seconds = "0"+seconds;}
    return hours+':'+minutes+':'+seconds;
}

function setLyrics(text) {
    lrc?.pause();
    currentLyricsCache = text;

    lrc = new Lyric({
        onPlay: function (line, text) {
            // console.log(lrc.lines[lrc.curLineNum].time - lrc.offset - dom_audio.currentTime * 1000)
            // dom_lyric.innerHTML = text + '<br>' + lrcLines[line].extendedLyrics.join('<br>')

            setHeaderText(lrcLines[line].text);
        },
        onSetLyric: function (lines) {
            lrcLines = lines
        }
    })
    // lrc.setLyric(b64DecodeUnicode(encodeLrc), b64DecodeUnicode(encodeLrc))
    lrc.setLyric(text)

    // lyrics can show up after the song already started
    if (!audio.paused) lrc.play(audio.currentTime * 1000);
}

function clearLyrics() {
    lrc?.pause();
    lrc = null;
    currentLyricsCache = "";
    setHeaderText('songs : cmusic');
}

async function loadSong(id, shouldPush = true, resume = false) {
    g_shouldPush = shouldPush;
    id = Number(id);

    currentSong = id;

    let url = baseUrl + '/meta/file/' + id;
    let json = baseUrl + '/meta/json/' + id;

    fetch(baseUrl + '/plugins/hooks/play/' + id + "?resume=" + (resume ? "true" : "false"), { method: 'POST' })
        .catch(err => console.error('play hook failed', err));

    let res;
    try {
        res = await getJson(json);
    } catch (err) {
        console.error('failed to get metadata for', id, err);
        return;
    }

    // someone clicked another song while we were waiting
    if (id !== currentSong) return;

    document.querySelectorAll('.meta-row').forEach(function(el) {
        setTextOr(el, null, 'unknown', 'sub');
    });

    cacheQueueMeta(id, res);
    saveQueue();
    renderQueue();

    for (const [key, value] of Object.entries(res.metadata)) {
        const el = document.querySelector('td[data-row="' + key + '"]');
        if (el) el.textContent = value;
    }

    playSong(res, url, id, shouldPush);
    play()

    // lyrics

    // grab lyrics. does it exist? if not, it returns a 404.
    // but ALSO metadata can contain lyrics
    if (res.metadata.lyrics != null) {
        setLyrics(res.metadata.lyrics);
        return;
    }

    clearLyrics();
    try {
        const lyrics = await getText(baseUrl + '/meta/lyrics/' + id);
        if (id === currentSong) setLyrics(lyrics);
    } catch (err) {
        if (id === currentSong) clearLyrics();
    }
}

function playSong(meta, url, id, shouldPush = false) {
    currentSong = id;

    document.querySelectorAll('.songRow.playing').forEach(row => row.classList.remove('playing'));
    document.querySelector('.songRow[data-id="' + id + '"]')?.classList.add('playing');

    audio.src = url;
    audio.load();

    if (resumeAt) {
        audio.currentTime = resumeAt;
        resumeAt = 0;
    }

    seek.max = Math.floor(meta.metadata.duration_seconds);

    setTextOr(document.querySelector('.artist_js'), meta.metadata.artist, 'unknown');
    setTextOr(document.querySelector('.title_js'), meta.metadata.title, meta.metadata.filename);

    document.querySelector('.logo').src = baseUrl + '/meta/cover/' + id;

    if ('mediaSession' in navigator) {

    navigator.mediaSession.metadata = new MediaMetadata({
        title: meta.metadata.title ?? "unknown title",
        artist: meta.metadata.artist ?? "unknown artist",
        album: meta.metadata.album ?? "unknown album",
        artwork: [
        { src: baseUrl + '/meta/cover/' + id, sizes: '512x512', type: 'image/png' },
        { src: baseUrl + '/meta/cover/' + id, sizes: '1024x1024', type: 'image/png' },
        ]
    });
    }
}

audio.addEventListener('timeupdate', () => {
    seek.value = audio.currentTime;
    localStorage.setItem("lastTimestamp", audio.currentTime);
    localStorage.setItem("lastSong", currentSong);
    document.querySelector('.timestamp_js').textContent = toHHMMSS(audio.currentTime);
});

seek.addEventListener('input', function () {
    audio.currentTime = this.value;
});

// keep the lyrics in sync with whatever the audio element is doing
audio.addEventListener('playing', () => { lrc?.play(audio.currentTime * 1000) });
audio.addEventListener('pause', () => { lrc?.pause() });
audio.addEventListener('seeked', () => {
    if (!audio.paused) lrc?.play(audio.currentTime * 1000);
});

audio.addEventListener('loadedmetadata', () => {
    renderQueue();
    seek.max = audio.duration;
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
    // go back into the queue, if there's no more songs before
    if (queueIndex > 0)
    {
        queueIndex--;
        localStorage.setItem("queueIndex", queueIndex);
        loadSong(queue[queueIndex], false);
    }
    else if (queue.length)
    {
        loadSong(queue[0], false);
    }
}

// next/previous .songRow after `row`, skipping the album header rows
function siblingRow(row, forward) {
    let el = forward ? row.nextElementSibling : row.previousElementSibling;
    while (el && !el.classList.contains('songRow')) {
        el = forward ? el.nextElementSibling : el.previousElementSibling;
    }
    return el;
}

async function triggerNextSong() {
    // check, does the queue have a song that's already in front??
    if (queueIndex + 1 < queue.length) {
        queueIndex++;
        loadSong(queue[queueIndex], false);
        return;
    }

    if (playStatus == "sequential") {
        let row = document.querySelector('.songRow[data-id="' + currentSong + '"]');
        if (!row) return;

        let trNext = siblingRow(row, true);

        if (trNext) {
            loadSong(trNext.dataset.id);
        } else {
            goToAdjacentPage(+1, function() { playEdgeRow(true); });
        }
    } else if (playStatus == "sequentialUp") {
        let row = document.querySelector('.songRow[data-id="' + currentSong + '"]');
        if (!row) return;

        let trPrev = siblingRow(row, false);

        if (trPrev) {
            loadSong(trPrev.dataset.id);
        } else {
            goToAdjacentPage(-1, function() { playEdgeRow(false); });
        }
    } else if (playStatus == "shuffle") {
        try {
            const res = await getJson(baseUrl + '/meta/song_count');
            let nextSong = Math.floor(
                Math.random() * res.files
            );
            loadSong(nextSong);
        } catch (err) {
            console.error('failed to get song count', err);
        }
    } else {
        let nextSong = Number(currentSong) + 1;
        loadSong(nextSong);
    }
}

audio.addEventListener('ended', () => {
    triggerNextSong();
});

audio.addEventListener('loadeddata', (event) => {
    if (g_shouldPush) {
        queue.push(currentSong);
        trimQueue();
        queueIndex = queue.length - 1;
    }

    saveQueue();
    renderQueue();
});

audio.addEventListener('canplaythrough', (event) => {
    renderQueue();
})

audio.addEventListener('error', function failed(e) {
    // audio playback failed - show a message saying why
    // to get the source of the audio element use this.currentSrc

    console.error(this.currentSrc)

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

volume.addEventListener('input', function () {
    localStorage.setItem("lastGain", this.value);
    audio.volume = this.value / 100;
});

function play() {
    // browsers block autoplay until you've clicked something, that's fine
    audio.play().catch(err => console.log('play() blocked:', err.message));
}

function pause() {
    audio.pause();
}

document.querySelector('.play_js').addEventListener('click', play);
document.querySelector('.pause_js').addEventListener('click', pause);
document.querySelector('.next_js').addEventListener('click', triggerNextSong);
document.querySelector('.previous_js').addEventListener('click', triggerPreviousSong);
