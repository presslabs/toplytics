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
 *         'views'          = 123
 *         'featured_image' = 'https://permalink.com/image.jpg' (only if the "Include Featured
 *                            Image in JSON Output" setting is enabled, otherwise empty)
 *     } => default: none / required
 *
 * (boolean) $showviews - if we should display the view count or not
 *     true / false => default: false
 *
 * (boolean) $showthumbnail - if we should display the featured image or not
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

    .toplytics-list-item {
        overflow: hidden;
    }

    .toplytics-thumbnail {
        float: left;
        width: 50px;
        height: 50px;
        margin-right: 10px;
        object-fit: cover;
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
                for ( var index in results ) {
                    if ( results.hasOwnProperty( index ) ) {
                        var permalink      = results[ index ].permalink;
                        var title          = results[ index ].title;
                        var views          = results[ index ].pageviews;
                        var featured_image = results[ index ].featured_image;
                        counter++;
                        if ( counter > args.numberposts ) { break; }

                        var views_html = '';
                        if ( args.showviews ) {
                            views_html = '<span class="post-views">' + views + ' views</span>';
                        }

                        var thumbnail_html = '';
                        if ( args.showthumbnail && featured_image ) {
                            thumbnail_html = '<img class="toplytics-thumbnail" src="' + featured_image + '" alt="" />';
                        }

                        if ( permalink && title ) {
                            html = html + '<li class="toplytics-list-item">' + thumbnail_html + '<a href="' + permalink + '">' + title + '</a>&nbsp;' + views_html + '</li>';
                        }
                    }
                }
                document.getElementById( args.widget_id ).innerHTML = '<ol class="toplytics-list">' + html + '</ol>';
            });
        }

        window.onload = function(){toplytics_results(toplytics_args);}

    </script>

    <div id="<?php echo $widget_id . '-inner'; ?>" class="toplytics-widget-inner"></div>

<?php else : ?>

    <div id="<?php echo $widget_id . '-inner'; ?>" class="toplytics-widget-inner">
        <ol class="toplytics-list">
            <?php foreach ( $posts as $post ) : ?>
                <li class="toplytics-list-item">
                    <?php if ( isset( $showthumbnail ) && $showthumbnail && ! empty( $post['featured_image'] ) ) : ?>
                        <img class="toplytics-thumbnail" src="<?php echo esc_url( $post['featured_image'] ); ?>" alt="" />
                    <?php endif; ?>

                    <a class="toplytics-anchor" href="<?php echo $post['permalink']; ?>" title="<?php echo $post['title']; ?>" target="<?php echo ( isset( $target ) && $target ) ? $target : 'self'; ?>">
                        <?php echo $post['title']; ?>
                    </a>

                    <?php if ( isset( $showviews ) && $showviews && ! ( isset( $using_fallback_posts ) && $using_fallback_posts ) ) : ?>
                        <span class="toplytics-views-count">&nbsp;<?php echo $post['pageviews'] . __( ' Views', TOPLYTICS_DOMAIN ); ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>

<?php endif;