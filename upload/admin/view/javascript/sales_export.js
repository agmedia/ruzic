(function ($) {
    'use strict';
    $(function () {
        var element = document.getElementById('sales-export-config');
        if (!element) { return; }
        var config = JSON.parse(element.textContent);
        var job = null;
        var running = false;
        var pending = false;
        var timer;
        var retryTimer = null;
        var failures = 0;

        function render() {
            $('#export-start').toggle(!job).prop('disabled', pending || retryTimer !== null);
            $('#export-resume').toggle(!!job && !job.complete && !running).prop('disabled', pending || retryTimer !== null);
            $('#export-pause').toggle(running);
            $('#export-delete').toggle(!!job).prop('disabled', pending || running || retryTimer !== null);
            $('#export-counts').toggle(!!job);
            $('#export-status').text(config.messages[job ? (job.complete ? 'text_complete' : (running ? 'text_running' : 'text_paused')) : 'text_ready']);
            $('#export-progress, #export-created').text('');
            $('#export-counts tbody').empty();
            if (job) {
                $('#export-progress').text(config.messages.text_progress
                    .replace('{done}', job.tables_done).replace('{total}', job.tables_total)
                    .replace('{table}', job.table).replace('{rows}', job.rows)
                    .replace('{size}', (job.bytes / 1048576).toFixed(1)));
                $('#export-created').text(job.created);
                $.each(job.counts, function (table, count) {
                    var download = $('<td>');
                    if (job.complete) {
                        $('<a>').addClass('btn btn-success btn-sm')
                            .attr('href', config.urls.download + '&job=' + encodeURIComponent(job.id) + '&table=' + encodeURIComponent(table))
                            .text(config.messages.button_download + ' — ' + table + '.csv').appendTo(download);
                    }
                    $('<tr>').append($('<td>').text(table), $('<td>').text(count), download).appendTo('#export-counts tbody');
                });
            }
        }

        function stop(message) {
            running = false;
            clearTimeout(timer);
            clearTimeout(retryTimer);
            retryTimer = null;
            $('#export-error').text(message).show();
        }

        function retry(action, callback) {
            if (action === 'step' && !running) { return; }
            retryTimer = setTimeout(function () {
                retryTimer = null;
                if (action !== 'step' || running) { request(action, callback); }
            }, 1500);
        }

        function request(action, callback) {
            pending = true;
            render();
            $.ajax({
                url: config.urls[action], type: action === 'status' ? 'GET' : 'POST',
                dataType: 'json', data: job ? { job: job.id } : {}, timeout: 20000
            }).done(function (response) {
                if (response.error) {
                    if (response.retry && failures++ < 5) {
                        retry(action, callback);
                    } else {
                        stop(response.error);
                    }
                    return;
                }
                if (!Object.prototype.hasOwnProperty.call(response, 'job')) {
                    stop(config.messages.error_network);
                    return;
                }
                failures = 0;
                job = response.job;
                $('#export-error').hide();
                callback();
            }).fail(function (xhr) {
                // Retrying uses the server checkpoint; no browser cursor can duplicate rows.
                if ((xhr.status === 0 || xhr.status >= 500) && failures++ < 3) {
                    retry(action, callback);
                } else {
                    stop(xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : config.messages.error_network);
                }
            }).always(function () {
                pending = false;
                if (job && job.complete) { running = false; }
                render();
            });
        }

        function next() {
            if (running && job && !job.complete) {
                timer = setTimeout(function () {
                    if (running) { request('step', next); }
                }, 100);
            }
        }

        $('#export-start').on('click', function () {
            failures = 0;
            running = true;
            request('start', next);
        });
        $('#export-resume').on('click', function () {
            failures = 0;
            running = true;
            $('#export-error').hide();
            render();
            next();
        });
        $('#export-pause').on('click', function () {
            running = false;
            clearTimeout(timer);
            clearTimeout(retryTimer);
            retryTimer = null;
            render();
        });
        $('#export-delete').on('click', function () {
            if (window.confirm(config.messages.text_confirm_delete)) {
                failures = 0;
                request('delete', function () {});
            }
        });
        request('status', function () {});
    });
})(jQuery);
