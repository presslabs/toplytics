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
    xmlhttp.open('GET', toplytics.json_url, true);
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
                //var post_id   = results[ index ].post_id;
                var views     = results[ index ].views;
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
