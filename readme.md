
## Example

config/assets.php

    'socail-feed' => array(
        'src' => 'assets/js/socail-feed.js', 
        'deps' => array('jquery', 'wp-util', 'underscore')
    )

social-feed.php

    <div id="social-feed" per-page
        data-per-page="<?php echo sw_get_config('social.per_page'); ?>">
        data-action="<?php echo sw_get_config('social.action'); ?>">
        <div class="content">Loading...</div>
        <div class="pagination">
            <a href="#">Prev</a>
            <a href="#">Next</a>
        </div>
    </div>

    <script type="text/html" id="tmpl-social-feed">
    <# for (var i = 0; i < data.streams.length; i++) {
        var stream = data.streams[i]; #>
        <div class="stream">
            <div class="header">
                <a class="username" href="{{ stream.url }}"><i class="icon-{{ stream.type }}"></i> {{ stream.username }}</a>
            </div>
            <# if (stream.thumbnail) { #>
            <div class="thumbnail"><img src="{{ stream.thumbnail }}"></div>
            <# } #>
            <div class="text">
                {{ stream.text }}
            </div>
        </div>
    <# } #>
    </script>

    <script>
        jQuery(document).ready(function($) {
            var $social_feed = $('#social-feed');
            wp.ajax.post($feed.data('action')).done(function(arr){
                var template = wp.template('social-feed');
                var per_page = Math.round($stream.data('per-page'));
                var count = arr.length;
                var page = 1;
                var pages = Math.ceil(count/page);
                var load_feed = function(paged) {
                    var streams = [];
                    var offset = per_page * (paged - 1);
                    for (i = offset; i < count; i++) { 
                        if (i >= offset + per_page) {
                            break;
                        }
                        streams.push(arr[i]);
                    }
                    var html = template({streams: streams});
                    $social_feed.find('.content').html(html);
                }
                load_feed(page);
                $social_feed.on('click', '.pagination .prev', function(event) {
                    event.preventDefault();
                    page = page - 1;
                    page = page < 1 ? 1 : page;
                    load_feed(page);
                }).on('click', '.pagination .next', function(event) {
                    event.preventDefault();
                    page = page + 1;
                    page = page > pages ? pages : page;
                    load_feed(page);
                });
            }).fail( function() {
                $social_feed.find('.content').html('Loaded fail!');
            });
        });
    </script>
