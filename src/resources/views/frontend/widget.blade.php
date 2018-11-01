{{--

    !!! IMPORTANT !!!
    IF YOU WISH TO UPDATE THIS DEFAULT TEMPLATE, MAKE A COPY OF IT
    AND NAME IT "custom.blade.php". THE COPY SHOULD BE IN THE SAME FOLDER
    AS THIS FILE (views/frontend/custom.blade.php) AND IT WILL BE LOADED
    BY THE PLUGIN PROPERLLY AT RUNTIME. THIS WAY YOU WILL NOT LOSE ANY
    OTHER MODIFICATION YOU DID TO THIS FILE WHEN WE RELEASE ANOTHER UPDATE.

This file contains the default template for the frontend Widget that will be
displayed by toplytics. You can update this template by copy-pasting it in
this same folder and name it `custom.blade.php`.

Here is the data that is being passed for this template:
(The same data will be passed to the custom.blade.php template as well)

(object) $posts - all the posts to be displayed in the top with the following format
    {
        'permalink' = 'https://permalink.com/',
        'title'     = 'This is the post title',
        'views'     = 123
    } => default: none / required

(boolean) $showviews - if we should display the view count or not
    true / false => default: false

(string) $target - the target for the url window to open on
    blank / self / parent / top => default: self

(bool) $loadViaJS - if JS loading was requested by the user or not.
    true / false => default: false

--}}

<style>
    .toplytics-list {
        display: block;
        clear:both;
    }

    .toplytics-list.toplytics-anchor {
        float: left;
    }

    .toplytics-list.toplytics-views-count {
        float: right;
    }
</style>

@if ($loadViaJS)
    <script type="application/javascript">
        function toplytics_get_data( args, callback ) {
            var xmlhttp;
            if ( window.XMLHttpRequest ) {
                xmlhttp = new XMLHttpRequest(); // code for IE7+, Firefox, Chrome, Opera, Safari
            } else {
                xmlhttp = new ActiveXObject('Microsoft.XMLHTTP'); // code for IE6, IE5
            }

            xmlhttp.onreadystatechange = function() {
                if ( xmlhttp.readyState === 4 && xmlhttp.status === 200 ) {
                    var toplytics_json_data = JSON.parse(xmlhttp.responseText);
                    callback( args, toplytics_json_data );
                }
            };
            xmlhttp.open('GET', args.json_url, true);
            xmlhttp.send();
        }

        function toplytics_results( toplytics_args ) {
            toplytics_get_data( toplytics_args, function( args, toplytics_json_data ) {
                var html    = '';
                var results = toplytics_json_data[args.period];
                var counter = 0;
                for ( var index in results ) {
                    if ( results.hasOwnProperty( index ) ) {
                        var permalink = results[ index ].permalink;
                        var title     = results[ index ].title;
                        console.log(title);
                        //var post_id   = results[ index ].post_id;
                        var views     = results[ index ].pageviews;
                        counter++;
                        if ( counter > args.numberposts ) { break; }

                        var views_html = '';
                        if ( args.showviews ) {
                            views_html = '<span class="post-views">' + views + ' views</span>';
                        }

                        if ( permalink && title ) {
                            html = html + '<li><a href="' + permalink + '">' + title + '</a>&nbsp;' + views_html + '</li>';
                        }
                    }
                }
                document.getElementById( args.widget_id ).innerHTML = '<ol>' + html + '</ol>';
            });
        }

        window.onload = function(){toplytics_results(toplytics_args);}

    </script>

    <div id="{{ $widget_id . '-inner' }}"></div>

@else

    <ol>
        @foreach($posts as $post)
            <li class="toplytics-list">
                <a class="toplytics-anchor" href="{{ $post['permalink'] }}" title="{{ $post['title'] }}" target="_{{ (isset($target) && $target) ? $target : 'self' }}">
                    {{ $post['title'] }}
                </a>

                @if ( isset($showviews) && $showviews )
                    <span class="toplytics-views-count">&nbsp;{{ var_export($post['pageviews'], true).__(' Views', TOPLYTICS_DOMAIN) }}</span>
                @endif

            </li>
        @endforeach
    </ol>

@endif
