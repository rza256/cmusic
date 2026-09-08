let currentSong = -1;
let pageStates = {};
let playStatus = "sequential";

// this is really shitty but for now it should work
// fine when traversing different pages. SPA todo
$().ready(function() {
    const audio = $('.audio_js')[0];
    const seek = $('#seek');
    const volume = $('#volume');

    // sound related
    const lastTs = localStorage.getItem("lastTimestamp");
    const lastSong = localStorage.getItem("lastSong");
    const lastGain = localStorage.getItem("lastGain");

    // search related
    const searchTerm = localStorage.getItem("searchTerm");
    const searchType = localStorage.getItem("searchType");
    const playOrder = localStorage.getItem("playOrder");

    if (playOrder === undefined) {
        playStatus = "sequential";
    }

    volume.val(lastGain);
    audio.volume = lastGain / 100;

    currentSong = lastSong;
    let id = lastSong;
    let url = 'http://localhost/meta/file/' + id;
    let json = 'http://localhost/meta/json/' + id;

    // get metadata
    $.ajax({
        url: json,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            console.log(res);
            playSong(res, url, id);

            let audio = $('.audio_js')[0];
            audio.currentTime = lastTs;
            audio.play();
        }
    });

    // set & trigger
    console.log(playOrder)
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

    let url = new URL('http://localhost/d/songs')
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

            $('.pagination-dynamic').html('');
            let pag = $('.pagination-default').detach();
            $('.pagination-dynamic').append(pag);

            $('.playSong_js').on('click', function() {
                //alert("Hi")

                let id = $(this).data('id');
                currentSong = id;
                let url = 'http://localhost/meta/file/' + id;
                let json = 'http://localhost/meta/json/' + id;

                // get metadata
                $.ajax({
                    url: json,
                    type: 'GET',
                    dataType: 'json', // added data type
                    success: function(res) {
                        console.log(res);
                        playSong(res, url, id);
                    }
                });
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

function playSong(meta, url, id) {
    console.log(meta);

    $('tr').each(function(i, obj) {
        $(this).removeClass('playing');
    });

    let row = $('tr[data-id="' + id + '"]')
    $(row).addClass('playing');

    let audio = $('.audio_js')[0];

    audio.src = url;
    audio.load();
    audio.play();

    $('#seek').attr('max', Math.floor(meta.metadata.duration_seconds))

    $('.artist_js').html(meta.metadata.artist ?? "<i>unknown</i>");
    $('.title_js').html(meta.metadata.title ?? "<i>unknown</i>");

    let tr = $('tr[data-id="' + id + '"]');

    console.log($('.logo'));
    $('.logo').attr('src', 'http://localhost/meta/cover/' + id)
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
    audio.currentTime = this.value;
});

audio.addEventListener('loadedmetadata', () => {
    seek.attr('max', audio.duration);
});

audio.addEventListener('ended', () => {
    // GENERALLY the way the files are processed
    // allows for it to be sequential. but it might not work well for
    // when there are more than two queue workers

    // also check queue endpoint when its added

    console.log("song ended")
    console.log(currentSong);

    // handle playStatus
    if (playStatus == "sequential") {
        let trNext = $('.songRow[data-id="' + currentSong + '"]').next()
        console.log('---------------')
        console.log(currentSong)
        console.log('.songRow[data-id="' + currentSong + '"]', trNext)
        console.log('.songRow[data-id="' + currentSong + '"]', $('.songRow[data-id="' + currentSong + '"]'))
        console.log($(trNext))
        console.log('---------------')
        currentSong = $(trNext).data('id');
        // alert(currentSong)
    } else if (playStatus == "sequentialUp") {
        let trNext = $('.songRow[data-id="' + currentSong + '"]').prev()
        console.log('---------------')
        console.log(currentSong)
        console.log('.songRow[data-id="' + currentSong + '"]', trNext)
        console.log('.songRow[data-id="' + currentSong + '"]', $('.songRow[data-id="' + currentSong + '"]'))
        console.log($(trNext))
        console.log('---------------')
        currentSong = $(trNext).data('id');
        // alert(currentSong)
    } else {
        currentSong = Number(currentSong) + 1;
    }
    
    let id = currentSong;
    let url = 'http://localhost/meta/file/' + id;
    let json = 'http://localhost/meta/json/' + id;

    // get metadata
    $.ajax({
        url: json,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            console.log(res);
            playSong(res, url, id);

            let audio = $('.audio_js')[0];
            audio.play();
        }
    });
});

$('#volume').on('input', function () {
    localStorage.setItem("lastGain", $(this).val());
    $('.audio_js')[0].volume = $(this).val() / 100;
});

$('.play_js').on('click', function() {
    let audio = $('.audio_js')[0];
    audio.play();
});

$('.pause_js').on('click', function() {
    let audio = $('.audio_js')[0];
    audio.pause();
});
