<?php
/**
 * Debug Page shows the data before GA api request, the api response and after toplytics process
 */

function toplytics_debug_save_ga_api_url_today( $url ) {
	set_transient( 'toplytics_ga_api_url_today', $url );
	return $url;
}

function toplytics_debug_save_ga_api_url_2weeks( $url ) {
	set_transient( 'toplytics_ga_api_url_2weeks', $url );
	return $url;
}

function toplytics_debug_save_ga_api_url_week( $url ) {
	set_transient( 'toplytics_ga_api_url_week', $url );
	return $url;
}

function toplytics_debug_save_ga_api_url_month( $url ) {
	set_transient( 'toplytics_ga_api_url_month', $url );
	return $url;
}

function toplytics_debug_save_ga_api_result_simplexml_today( $result_simplexml ) {
	set_transient( 'toplytics_ga_api_result_simplexml_today', $result_simplexml );
	return $result_simplexml;
}

function toplytics_debug_save_ga_api_result_simplexml_2weeks( $result_simplexml ) {
	set_transient( 'toplytics_ga_api_result_simplexml_2weeks', $result_simplexml );
	return $result_simplexml;
}

function toplytics_debug_save_ga_api_result_simplexml_week( $result_simplexml ) {
	set_transient( 'toplytics_ga_api_result_simplexml_week', $result_simplexml );
	return $result_simplexml;
}

function toplytics_debug_save_ga_api_result_simplexml_month( $result_simplexml ) {
	set_transient( 'toplytics_ga_api_result_simplexml_month', $result_simplexml );
	return $result_simplexml;
}

function toplytics_debug_init() {
	$ranges = array( 'today', '2weeks', 'week', 'month' );
	foreach ( $ranges as $item ) :
		add_filter( "toplytics_ga_api_url_$item", "toplytics_debug_save_ga_api_url_$item", 10, 1 );
		add_filter( "toplytics_ga_api_result_simplexml_$item", "toplytics_debug_save_ga_api_result_simplexml_$item", 10, 1 );
	endforeach;
}
add_action( 'init', 'toplytics_debug_init' );

function toplytics_debug_admin_head() {
	?>
	<script>
		function printDiv(divName) {
			var printContents = document.getElementById(divName).innerHTML;
			var originalContents = document.body.innerHTML;
			document.body.innerHTML = printContents;
			window.print();
			document.body.innerHTML = originalContents;
		}
	</script>
	<?php
}
add_action( 'admin_head', 'toplytics_debug_admin_head' );

function toplytics_debug_menu_page() {
	?>
		<h2>Toplytics Debug: <?php echo date( 'd-m-Y h:i:s' ) ?></h2>&nbsp;
		<input type='button' value='Print This Result' onclick='printDiv("wpbody");'/>
		<hr>
	<?php
	$ranges = array( 'today', '2weeks', 'week', 'month' );
	foreach ( $ranges as $period ) :
		?>
		<h3>GA request[<?php echo $period; ?>]:</h3>
		<textarea cols="70" rows="5" readonly="readonly"><?php print_r( get_transient( "toplytics_ga_api_url_$period" ) ); ?></textarea><hr>

		<h3>GA result[<?php echo $period; ?>]:</h3>
		<pre><?php print_r( get_transient( "toplytics_ga_api_result_simplexml_$period" ) ); ?></pre><hr>

		<h3>Toplytics result: get_transient(toplytics.cache[<?php echo $period; ?>])</h3>
		<pre><?php $toplytics_result = get_transient( 'toplytics.cache' ); print_r( array( 'date(_ts)' => date( 'd-m-Y h:i:s', $toplytics_result['_ts'] ), $period => $toplytics_result[ $period ] ) ); ?></pre><hr>

		<?php if ( function_exists( 'toplytics_get_results' ) ) { ?>
			<?php $results = toplytics_get_results( array( 'period' => $period ) ); ?>
			<h3>toplytics_get_results( array( 'period' => '[<?php echo $period; ?>]' ) ):</h3>
			<pre><?php print_r( $results ); ?></pre><hr>

			<h3>Widget results([<?php echo $period; ?>]):</h3>
			<?php
			foreach ( $results as $post_id => $post_views ) :
				$widget_results[ $post_id ]['permalink'] = get_permalink( $post_id );
				$widget_results[ $post_id ]['title'] = get_the_title( $post_id );
				$widget_results[ $post_id ]['pageviews'] = $post_views;
			endforeach;
			?>
			<pre><?php print_r( $widget_results ); ?></pre><hr>
		<?php
		}
	endforeach;
}

function toplytics_debug_menu_slug() {
	return 'toplytics_debug';
}

function toplytics_debug_register_menu_page() {
	add_options_page( 'Toplytics Debug', 'Toplytics Debug', 'manage_options', toplytics_debug_menu_slug(), 'toplytics_debug_menu_page', 6 );
}
add_action( 'admin_menu', 'toplytics_debug_register_menu_page' );

function topytics_debug_adjust_the_menu() {
	$page = remove_submenu_page( 'options-general.php', toplytics_debug_menu_slug() );
}
add_action( 'admin_menu', 'topytics_debug_adjust_the_menu', 999  );

function toplytics_debug_link() {
	$debug_link = admin_url( 'options-general.php?page=' . toplytics_debug_menu_slug() );
	echo '<p><a href="' . $debug_link . '">Click here if you want to go to `Toplytics Debug` page!</a></p>';
}
add_action( 'toplytics_options_general_page', 'toplytics_debug_link' );

