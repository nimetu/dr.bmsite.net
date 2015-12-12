$(function () {
    "use strict";

    var state = {};

    function status(text) {
        $('#status').html(text).show();
    }

    function clear_status() {
        $('#status').hide();
    }

    // form submit
    function do_reload(ajax) {
        ajax = ajax || false;
        var form = $('#form')[0];
        var data = $(form).serialize();

        if (ajax) {
            status('Loading...');

            window.history.pushState({}, 'title', '?' + data);
            $.getJSON(form.action, data, function (json) {
                status('Loading image...');

                if (json && json.result == 'success') {
                    state = json.data;

                    $('#preview').attr('src', state.image);
                } else {
                    alert(json);
                }
            });
        } else {
            status('Reloading...');
            window.location = form.action + data;
        }
    }

    function onchange(e) {
        // for language, reload whole page
        if ($(e.target).is('select[name=language]')) {
            do_reload(false);
        } else {
            // submit form with ajax and update image
            do_reload(true);
        }
    }

    clear_status();

    // form element events to trigger reload
    $('#form').change(onchange)
        .find('select').bind('keyup', onchange);

    // img events to show loading status
    $('#preview').load(function () {
        clear_status();
    }).error(function () {
        status('Image error...');
    });

    // history
    $(window).bind('popstate', function (e) {
        window.location.reload(true);
    });
});
