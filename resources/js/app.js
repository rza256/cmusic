let currentSong = -1;

// this is really shitty but for now it should work
// fine when traversing different pages. SPA todo
$().ready(function() {
    const lastTs = localStorage.getItem("lastTimestamp");
    const lastSong = localStorage.getItem("lastSong");

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
})

$('.playSong_js').on('click', function() {
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
    currentSong = Number(currentSong) + 1;
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

$('.filter_js').on('click', function() {
    let type = $(this).data('type');
    alert(type);
})