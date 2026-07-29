<?php
/**
 * 
 *     !!! IMPORTANT !!!
 *     IF YOU WISH TO UPDATE THIS DEFAULT TEMPLATE, MAKE A COPY OF IT
 *     AND NAME IT "custom.template.php". THE COPY SHOULD BE IN THE SAME FOLDER
 *     AS THIS FILE (views/frontend/custom.template.php) AND IT WILL BE LOADED
 *     BY THE PLUGIN PROPERLLY AT RUNTIME. THIS WAY YOU WILL NOT LOSE ANY
 *     OTHER MODIFICATION YOU DID TO THIS FILE WHEN WE RELEASE ANOTHER UPDATE.
 * 
 * This file contains the default template for the frontend Widget that will be
 * displayed by toplytics. You can update this template by copy-pasting it in
 * this same folder and name it `custom.template.php`.
 * 
 * Here is the data that is being passed for this template:
 * (The same data will be passed to the custom.template.php template as well)
 * 
 * (object) $posts - all the posts to be displayed in the top with the following format
 *     {
 *         'permalink'      = 'https://permalink.com/',
 *         'title'          = 'This is the post title',
 *         'views'          = 123,
 *         'featured_image' = 'https://permalink.com/image.jpg'
 *     } => default: none / required
 *
 * (boolean) $showviews - if we should display the view count or not
 *     true / false => default: false
 *
 * (boolean) $showimage - if we should display the featured image or not
 *     true / false => default: false
 *
 * (boolean) $isThumbnailImageSize - whether the configured featured image size is
 *     the small built-in "thumbnail" size, in which case the image is capped at
 *     100px wide; any other configured size just gets a responsive max-width: 100%.
 *     true / false => default: false
 *
 * (string) $target - the target for the url window to open on
 *     blank / self / parent / top => default: self
 * 
 * (bool) $loadViaJS - if JS loading was requested by the user or not.
 *     true / false => default: false
 * 
 */
?>

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

<?php if ( $loadViaJS ) : ?>
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
                var counter = 0;
                var results = toplytics_json_data[ args.period ];
                // If filtering by category is enabled, check if enough posts.
                if ( args.category ) {
                    for ( var index in toplytics_json_data[args.period] ) {
                        if ( results.hasOwnProperty( index ) &&
                                results[ index ].hasOwnProperty( 'category' ) &&
                                ( results[ index ].categories.indexOf( args.category ) != -1  ) ) {
                            counter ++;
                            if ( counter > args.numberposts ) {
                                break;
                            }
                        }
                    }

                    if ( counter < args.numberposts ) {
                        switch ( args.fallback_not_enough_ga_posts ) {
                            case 'recent' :
                                if ( toplytics_json_data.categories.hasOwnProperty( args.category )  ) {
                                    results = toplytics_json_data.categories[ args.category ];
                                } else {
                                    // Nothing to render for the widget, don't continue.
                                    return;
                                }
                                break;
                            case 'top' :
                                results = toplytics_json_data.top_posts;
                                break;
                            case 'none' :
                            default :
                                // No posts must be rendered, don't continue.
                                return;
                        }
                    }
                }
                
                counter = 0;
                // Object keys are not guaranteed to preserve the pageviews-descending order
                // the server sent them in (JS enumerates integer-like keys in ascending order),
                // so sort explicitly by pageviews before rendering.
                var indexes = Object.keys( results ).sort( function( a, b ) {
                    return ( results[ b ].pageviews || 0 ) - ( results[ a ].pageviews || 0 );
                } );
                for ( var i = 0; i < indexes.length; i++ ) {
                    var index = indexes[ i ];
                    if ( results.hasOwnProperty( index ) ) {
                        var permalink = results[ index ].permalink;
                        var title     = results[ index ].title;
                        var views     = results[ index ].pageviews;
                        var image     = results[ index ].featured_image;
                        counter++;
                        if ( counter > args.numberposts ) { break; }

                        var views_html = '';
                        if ( args.showviews ) {
                            views_html = '<br><span class="post-views">' + views + ' views</span>';
                        }

                        var image_html = '';
                        if ( args.showimage && image ) {
                            var image_style = args.isThumbnailImageSize ? 'max-width:100px;height:auto;' : 'max-width:100%;height:auto;';
                            image_html = '<a href="' + permalink + '"><img class="toplytics-featured-image" src="' + image + '" alt="' + title + '" style="' + image_style + '"></a>';
                        }

                        if ( permalink && title ) {
                            html = html + '<li class="toplytics-list-item" style="margin-bottom:10px;display:flex;align-items:center;justify-content:space-between;gap:10px;">' + image_html + '<span style="text-align:right;"><a href="' + permalink + '" style="text-decoration:none;">' + title + '</a>' + views_html + '</span></li>';
                        }
                    }
                }
                document.getElementById( args.widget_id ).innerHTML = '<ul class="toplytics-list" style="list-style:none;margin:0;padding:0;">' + html + '</ul>';
            });
        }

        window.onload = function(){toplytics_results(toplytics_args);}

    </script>

    <div id="<?php echo $widget_id . '-inner'; ?>" class="toplytics-widget-inner"></div>

<?php else : ?>

    <div id="<?php echo $widget_id . '-inner'; ?>" class="toplytics-widget-inner">
        <ul class="toplytics-list" style="list-style:none;margin:0;padding:0;">
            <?php foreach ( $posts as $post ) : ?>
                <li class="toplytics-list-item" style="margin-bottom:10px;display:flex;align-items:center;justify-content:space-between;gap:10px;">
                    <?php if ( isset( $showimage ) && $showimage && ! empty( $post['featured_image'] ) ) : ?>
                        <a href="<?php echo $post['permalink']; ?>" title="<?php echo $post['title']; ?>" target="<?php echo ( isset( $target ) && $target ) ? $target : 'self'; ?>">
                            <img class="toplytics-featured-image" src="<?php echo esc_url( $post['featured_image'] ); ?>" alt="<?php echo esc_attr( $post['title'] ); ?>" style="<?php echo ( isset( $isThumbnailImageSize ) && $isThumbnailImageSize ) ? 'max-width:100px;height:auto;' : 'max-width:100%;height:auto;'; ?>">
                        </a>
                    <?php endif; ?>
                    <span style="text-align:right;">
                        <a class="toplytics-anchor" href="<?php echo $post['permalink']; ?>" title="<?php echo $post['title']; ?>" target="<?php echo ( isset( $target ) && $target ) ? $target : 'self'; ?>" style="text-decoration:none;">
                            <?php echo $post['title']; ?>
                        </a>

                        <?php if ( isset( $showviews ) && $showviews && ! ( isset( $using_fallback_posts ) && $using_fallback_posts ) ) : ?>
                            <br><span class="toplytics-views-count"><?php echo $post['pageviews'] . __( ' Views', TOPLYTICS_DOMAIN ); ?></span>
                        <?php endif; ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

<?php endif;