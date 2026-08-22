$('.playSong_js').on('click', function() {
    let id = $(this).data('id');
    let url = 'http://localhost/meta/file/' + id;
    let json = 'http://localhost/meta/json/' + id;

    // get metadata
    $.ajax({
        url: json,
        type: 'GET',
        dataType: 'json', // added data type
        success: function(res) {
            console.log(res);
            playSong(res, url);
        }
    });
});

function playSong(meta, url) {
    console.log(meta);

    let audio = $('.audio_js')[0];

    audio.src = url;
    audio.load();
    // audio.play();

    $('.artist_js').html(meta.metadata.artist ?? "<i>unknown</i>");
    $('.title_js').html(meta.metadata.title ?? "<i>unknown</i>");
}

