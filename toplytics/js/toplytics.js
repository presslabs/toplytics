function toplytics_results( args ) {
  var xmlhttp;
  if ( window.XMLHttpRequest ) {
    xmlhttp = new XMLHttpRequest(); // code for IE7+, Firefox, Chrome, Opera, Safari
  } else {
    xmlhttp = new ActiveXObject("Microsoft.XMLHTTP"); // code for IE6, IE5
  }

  xmlhttp.onreadystatechange = function() {
    if ( xmlhttp.readyState === 4 && xmlhttp.status === 200 ) {
      var toplytics_json_data = JSON.parse(xmlhttp.responseText);

      var results = toplytics_json_data[args.period];
      var k = 0;
      var html = '';
      for ( var index in results ) {
        var permalink = results[ index ].permalink;
        var title     = results[ index ].title;
        var post_id   = results[ index ].post_id;
        var views     = results[ index ].views;
        k++;
        if ( k > args.numberposts ) { break };

        views_html = "";
        if ( args.showviews ) {
          views_html = '<span class="post-views">' + views + ' views</span>';
        }

        if ( permalink && title ) {
          html = html + '<li><a href="' + permalink + '">' + title + '</a>&nbsp;' + views_html + '</li>';
        }
      }
      var element = document.createElement('ol');
      element.innerHTML = html;
      document.getElementById( args.widget_id ).appendChild( element );
    }
  }
  xmlhttp.open("GET", "/wp-content/plugins/toplytics/toplytics.json?ver=" + Math.floor(new Date().getTime() / 1000), true);
  xmlhttp.send();
}
