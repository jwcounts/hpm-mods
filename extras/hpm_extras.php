<?php
/**
 * Functions or modifications related to plugins or things that aren't directly theme-related
 */

/**
 * Adds ability for anyone who can edit others' posts to be able to create and manage guest authors
 */
add_filter( 'coauthors_guest_author_manage_cap', 'capx_filter_guest_author_manage_cap' );
function capx_filter_guest_author_manage_cap( $cap ) {
	return 'edit_others_posts';
}

/**
 * Anyone who can publish can publish to Apple News
 */
add_filter( 'apple_news_publish_capability', 'publish_to_apple_news_cap' );
function publish_to_apple_news_cap( $cap ) {
	return 'publish_posts';
}

add_action( 'publish_post', 'hpm_apple_news_exclude', 10, 2 );
add_action( 'save_post', 'hpm_apple_news_exclude', 10, 2 );
add_action( 'owf_update_published_post', 'hpm_apple_news_exclude', 10, 2 );

function hpm_apple_news_exclude( $post_id, $post ) {
	$cats = get_the_category( $post_id );
	foreach ( $cats as $c ) :
		if ( $c->term_id == 27876 ) :
			apply_filters( 'apple_news_skip_push', true, $post_id );
		endif;
	endforeach;
}

function hpm_versions() {
	$transient = get_transient( 'hpm_versions' );
	if ( !empty( $transient ) ) :
		return $transient;
	else :
		$remote = wp_remote_get( esc_url_raw( "https://s3-us-west-2.amazonaws.com/hpmwebv2/assets/version.json" ) );
		if ( is_wp_error( $remote ) ) :
			return false;
		else :
			$api = wp_remote_retrieve_body( $remote );
			$json = json_decode( $api, TRUE );
		endif;

		set_transient( 'hpm_versions', $json, 3 * 60 );
		return $json;
	endif;
}

/*
 * Add script so that javascript is detected and saved as a class on the body element
 */
function hpmv2_javascript_detection() {
	echo "<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>\n";
}
add_action( 'wp_head', 'hpmv2_javascript_detection', 0 );

/*
 * Removes unnecessary metadata from the document head
 */
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );

/**
 * Disable support for Wordpress Emojicons, because we will never use them and don't need the extra overhead
 */
function disable_wp_emojicons() {
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	add_filter( 'tiny_mce_plugins', 'disable_emojicons_tinymce' );
}

function disable_emojicons_tinymce( $plugins ) {
	if ( is_array( $plugins ) ) :
		return array_diff( $plugins, [ 'wpemoji' ] );
	else :
		return [];
	endif;
}
add_action( 'init', 'disable_wp_emojicons' );

/*
 * Adding variables to the Wordpress query setup for special sections and external data pulls
 */
function add_query_vars($aVars) {
	$aVars[] = "sched_station";
	$aVars[] = "sched_year";
	$aVars[] = "sched_month";
	$aVars[] = "sched_day";
	$aVars[] = "npr_id";
	return $aVars;
}
add_filter('query_vars', 'add_query_vars');

/*
 * Creating new rewrite rules to feed those special sections and external data pulls
 */
function add_rewrite_rules($aRules) {
	$aNewRules = [
		'^(news887|classical)/schedule/([0-9]{4})/([0-9]{2})/([0-9]{2})/?$' => 'index.php?pagename=$matches[1]&sched_station=$matches[1]&sched_year=$matches[2]&sched_month=$matches[3]&sched_day=$matches[4]',
		'^(news887|classical)/schedule/([0-9]{4})/([0-9]{2})/?$' => 'index.php?pagename=$matches[1]&sched_station=$matches[1]&sched_year=$matches[2]&sched_month=$matches[3]&sched_day=01',
		'^(news887|classical)/schedule/([0-9]{4})/?$' => 'index.php?pagename=$matches[1]&sched_station=$matches[1]&sched_year=$matches[2]&sched_month=01&sched_day=01',
		'^(news887|classical)/schedule/?$' => 'index.php?pagename=$matches[1]&sched_station=$matches[1]',
		'^(news887|classical)/?$' => 'index.php?pagename=$matches[1]&sched_station=$matches[1]',
		'^npr/([0-9]{4})/([0-9]{2})/([0-9]{2})/([0-9]{9})/([a-z0-9\-]+)/?' => 'index.php?pagename=npr-articles&npr_id=$matches[4]'
	];
	$aRules = $aNewRules + $aRules;
	return $aRules;
}
add_filter('rewrite_rules_array', 'add_rewrite_rules');

/**
 *  Add new options for Cron Schedules
 */

add_filter( 'cron_schedules', 'hpm_cron_updates', 10, 2 );

function hpm_cron_updates( $schedules ) {
	$schedules['hpm_1min'] = [
		'interval' => 60,
		'display' => __( 'Every Minute' )
	];
	$schedules['hpm_2min'] = [
		'interval' => 120,
		'display' => __( 'Every Other Minute' )
	];
	$schedules['hpm_2hours'] = [
		'interval' => 7200,
		'display' => __( 'Every Two Hours' )
	];
	$schedules['hpm_weekly'] = [
		'interval' => 604800,
		'display' => __( 'Every Week' )
	];
	return $schedules;
}

/*
 * Save local copies of today's schedule JSON from NPR Composer2 into site transients
 */
function hpmv2_schedules( $station, $date ) {
	if ( empty( $station ) || empty( $date ) ) :
		return false;
	endif;
	$api = get_transient( 'hpm_' . $station . '_' . $date );
	if ( !empty( $api ) ) :
		return $api;
	endif;
	$remote = wp_remote_get( esc_url_raw( "https://api.composer.nprstations.org/v1/widget/".$station."/day?date=".$date."&format=json" ) );
	if ( is_wp_error( $remote ) ) :
		return false;
	else :
		$api = wp_remote_retrieve_body( $remote );
		$json = json_decode( $api, TRUE );
	endif;
	$c = time();
	$offset = get_option( 'gmt_offset' ) * 3600;
	$c = $c + $offset;
	$now = getdate( $c );
	$old = $now[0] - 86400;
	$new = $now[0] + 432000;
	$date_exp = explode( '-', $date );
	$dateunix = mktime( 0, 0, 0, $date_exp[1], $date_exp[2], $date_exp[0] );
	if ( $dateunix > $old && $dateunix < $new ) :
		set_transient( 'hpm_' . $station . '_' . $date, $json, 300 );
	endif;
	return $json;
}

/*
 * Log errors in wp-content/debug.log when debugging is enabled.
 */
if ( !function_exists( 'log_it' ) ) :
	function log_it( $message ) {
		if( WP_DEBUG === true ) :
			if ( is_array( $message ) || is_object( $message ) ) :
				error_log( print_r( $message, true ) );
			else :
				error_log( $message );
			endif;
		endif;
	}
endif;

/*
 * Add checkbox to post editor in order to hide last modified time in the post display (single.php)
 */
add_action( 'post_submitbox_misc_actions', 'hpm_no_mod_time' );
function hpm_no_mod_time() {
	global $post;
	if ( ! current_user_can( 'edit_others_posts', $post->ID ) ) return false;
	if ( $post->post_type == 'post' ) {
		$value = get_post_meta( $post->ID, 'hpm_no_mod_time', true );
		$checked = ! empty( $value ) ? ' checked="checked" ' : '';
		echo '<div class="misc-pub-section misc-pub-section-last"><input type="checkbox"' . $checked . 'value="1" name="hpm_no_mod_time" /><label for="hpm_no_mod_time">Hide Last Modified Time?</label></div>';
	}
}

add_action( 'save_post', 'save_hpm_no_mod_time');
function save_hpm_no_mod_time( ) {
	global $post;
	if ( empty( $post ) || $post->post_type != 'post' ) return false;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return false;
	if ( ! current_user_can( 'edit_others_posts', $post->ID ) ) return false;
	if ( empty( $post->ID ) ) return false;
	$value = ( !empty( $_POST['hpm_no_mod_time'] )  ? 1 : 0 );

	update_post_meta( $post->ID, 'hpm_no_mod_time', $value );
}

/*
 * Disallow certain MIME types from being accepted by the media uploader
 */
function custom_upload_mimes ( $existing_mimes = [] ) {
	unset( $existing_mimes['exe'] );
	unset( $existing_mimes['wav'] );
	unset( $existing_mimes['ra|ram'] );
	unset( $existing_mimes['mid|midi'] );
	unset( $existing_mimes['wma'] );
	unset( $existing_mimes['wax'] );
	unset( $existing_mimes['swf'] );
	unset( $existing_mimes['class'] );
	unset( $existing_mimes['js'] );
	return $existing_mimes;
}
add_filter('upload_mimes', 'custom_upload_mimes');

/*
 * Finds the last 5 entries in the specified YouTube playlist and saves into a site transient
 */
function hpm_youtube_playlist( $key, $num = 5 ) {
	$list = get_transient( 'hpm_yt_'.$key.'_'.$num );
	if ( !empty( $list ) ) :
		return $list;
	endif;
	$remote = wp_remote_get( esc_url_raw( 'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=50&playlistId='.$key.'&key=AIzaSyBHSGTRPfGElaMTniNCtHNbHuGHKcjPRxw' ) );
	if ( is_wp_error( $remote ) ) :
		return false;
	else :
		$yt = wp_remote_retrieve_body( $remote );
		$json = json_decode( $yt, TRUE );
	endif;
	$totalResults = $json['pageInfo']['totalResults'];
	$resultsPerPage = $json['pageInfo']['resultsPerPage'];
	$times = [ strtotime( $json['items'][0]['snippet']['publishedAt'] ), strtotime( $json['items'][1]['snippet']['publishedAt'] ), strtotime( $json['items'][2]['snippet']['publishedAt'] ) ];
	if ( $times[0] > $times[1] && $times[1] > $times[2] ) :
		$new2old = TRUE;
	elseif ( $times[2] > $times[1] && $times[1] > $times[0] ) :
		$new2old = FALSE;
	else :
		$new2old = TRUE;
	endif;
	if ( $new2old ) :
		$items = $json['items'];
	else :
		if ( $totalResults > $resultsPerPage ) :
			$pages = floor( $totalResults / $resultsPerPage );
			for ( $i=0; $i < $pages; $i++ ) :
				if ( !empty( $json['nextPageToken'] ) ) :
					$remote = wp_remote_get( esc_url_raw( 'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=50&playlistId='.$key.'&pageToken='.$json['nextPageToken'].'&key=AIzaSyBHSGTRPfGElaMTniNCtHNbHuGHKcjPRxw' ) );
					if ( is_wp_error( $remote ) ) :
						return false;
					else :
						$yt = wp_remote_retrieve_body( $remote );
						$json = json_decode( $yt, TRUE );
					endif;
				endif;
			endfor;
		endif;
		$items = array_reverse( $json['items'] );
	endif;
	$json_r = array_slice( $items, 0, $num );
	set_transient( 'hpm_yt_'.$key.'_'.$num, $json_r, 300 );
	return $json_r;
}

/*
 * Ping Facebook's OpenGraph servers whenever a post is published, in order to prime their cache
 */
function hpm_facebook_ping( $arg1 ) {
	$perma = get_permalink( $arg1 );
	$url = 'http://graph.facebook.com';
	$data = [ 'id' => $perma, 'scrape' => 'true' ];
	$options = [
		'headers' => [
			"Content-type" => "application/x-www-form-urlencoded"
		],
		'body' => $data
	];
	$remote = wp_remote_get( esc_url_raw( $url ), $options );
	if ( is_wp_error( $remote ) ) :
		return false;
	else :
		return true;
	endif;
}
function hpm_facebook_ping_schedule( $post_id, $post ) {
	if ( WP_ENV == 'production' ) :
		wp_schedule_single_event( time() + 60, 'hpm_facebook_ping', [ $post_id ] );
	endif;
}

add_action( 'publish_post', 'hpm_facebook_ping_schedule', 10, 2 );

add_action( 'owf_update_published_post', 'update_post_meta_info', 10, 2 );

/**
 * @param $original_post_id
 * @param $revised_post
 *
 * Copy over any metadata from an article revision to its original
 */
function update_post_meta_info( $original_post_id, $revised_post ) {
	$post_meta_keys = get_post_custom_keys( $revised_post->ID );
	if ( empty( $post_meta_keys ) ) :
		return;
	endif;

	foreach ( $post_meta_keys as $meta_key ) :
		$meta_key_trim = trim( $meta_key );
		if ( '_' == $meta_key_trim[0] || strpos( $meta_key_trim, 'oasis' ) !== false ) :
			continue;
		endif;
		$revised_meta_values = get_post_custom_values( $meta_key, $revised_post->ID );
		$original_meta_values = get_post_custom_values( $meta_key, $original_post_id );

		// find the bigger array of the two
		$meta_values_count = count( $revised_meta_values ) > count( $original_meta_values ) ? count( $revised_meta_values ) : count( $original_meta_values );

		// loop through the meta values to find what's added, modified and deleted.
		for( $i = 0; $i < $meta_values_count; $i++) :
			$new_meta_value = "";
			// delete if the revised post doesn't have that key
			if ( count( $revised_meta_values ) >= $i+1 ) :
				$new_meta_value = maybe_unserialize( $revised_meta_values[$i] );
			else :
				$old_meta_value = maybe_unserialize( $original_meta_values[$i] );
				delete_post_meta( $original_post_id, $meta_key, $old_meta_value );
				continue;
			endif;

			// old meta values got updated, so simply update it
			if ( count( $original_meta_values ) >= $i+1 ) :
				$old_meta_value = maybe_unserialize( $original_meta_values[$i] );
				update_post_meta( $original_post_id, $meta_key, $new_meta_value, $old_meta_value );
			endif;

			// new meta values got added, so add it
			if ( count( $original_meta_values ) < $i+1 ) :
				add_post_meta( $original_post_id, $meta_key, $new_meta_value );
			endif;
		endfor;
	endforeach;
}

/**
 * Authorization function for accessing Google Analytics API
 * @return Google_Service_Analytics
 */
function initializeAnalytics()
{
	$KEY_FILE_LOCATION = SITE_ROOT . '/client_secrets.json';

	// Create and configure a new client object.
	$client = new Google_Client();
	$client->setApplicationName("Hello Analytics Reporting");
	$client->setAuthConfig($KEY_FILE_LOCATION);
	$client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
	$analytics = new Google_Service_Analytics($client);

	return $analytics;
}

/**
 * Cron task to pull top 5 most-viewed stories from the last 3 days
 */
function analyticsPull_update() {
	require_once SITE_ROOT . '/vendor/autoload.php';
	$analytics = initializeAnalytics();
	$t = time();
	$offset = get_option('gmt_offset')*3600;
	$t = $t + $offset;
	$now = getdate($t);
	$then = $now[0] - 172800;
	$match = [];
	$result = $analytics->data_ga->get(
		'ga:142153354',
		date( "Y-m-d", $then ),
		date( "Y-m-d", $now[0] ),
		'ga:visits',
		[
			'filters' => 'ga:pagePath=@/articles',
			'dimensions'  => 'ga:pagePath',
			'metrics'     => 'ga:pageviews,ga:uniquePageviews',
			'sort'        => '-ga:pageviews,-ga:uniquePageviews',
			'max-results' => '5',
			'output'      => 'json'
		]
	);
	$output = "<ul>";
	foreach ( $result->rows as $row ) :
		preg_match( '/\/articles\/([a-z0-9\-\/]+)\/[0-9]{4}\/[0-9]{2}\/[0-9]{2}\/([0-9]{1,6})\/.+/', $row[0], $match );
		if ( !empty( $match ) ) :
			$output .= '<li><h2 class="entry-title"><a href="'.$row[0].'" rel="bookmark">'.get_the_title( $match[2] ).'</a></h2></li>';
		endif;
	endforeach;
	$output .= "</ul>";
	update_option( 'hpm_most_popular', $output );
}

function analyticsPull() {
	return get_option( 'hpm_most_popular' );
}

add_action( 'hpm_analytics', 'analyticsPull_update' );
$timestamp = wp_next_scheduled( 'hpm_analytics' );
if ( empty( $timestamp ) ) :
	wp_schedule_event( time(), 'hourly', 'hpm_analytics' );
endif;

/**
 * @return mixed|string
 * Pull NPR API articles and save them to a transient
 */
function hpm_nprapi_output() {
	$npr = get_transient( 'hpm_nprapi' );
	if ( !empty( $npr ) ) :
		return $npr;
	endif;
	$output = '';
	$api_key = get_option( 'ds_npr_api_key' );
	$remote = wp_remote_get( esc_url_raw( "https://api.npr.org/query?id=1001&fields=title,teaser,image,storyDate&requiredAssets=image,audio,text&startNum=0&dateType=story&output=JSON&numResults=4&apiKey=" . $api_key ) );
	if ( is_wp_error( $remote ) ) :
		return "<p></p>";
	else :
		$npr = wp_remote_retrieve_body( $remote );
		$npr_json = json_decode( $npr, TRUE );
	endif;
	foreach ( $npr_json['list']['story'] as $story ) :
		$npr_date = strtotime($story['storyDate']['$text']);
		$output .= '<article class="national-content">';
		if ( !empty( $story['image'][0]['src'] ) ) :
			$output .= '<div class="national-image" style="background-image: url('.$story['image'][0]['src'].')"><a href="/npr/'.date('Y/m/d/',$npr_date).$story['id'].'/'.sanitize_title($story['title']['$text']).'/" class="post-thumbnail"></a></div><div class="national-text">';
		else :
			$output .= '<div class="national-text-full">';
		endif;
		$output .= '<h2><a href="/npr/'.date('Y/m/d/',$npr_date).$story['id'].'/'.sanitize_title($story['title']['$text']).'/">'.$story['title']['$text'].'</a></h2><p class="screen-reader-text">'
		           .$story['teaser']['$text'].'</p></div></article>';
	endforeach;
	set_transient( 'hpm_nprapi', $output, 300 );
	return $output;
}

/**
 * Hide the Comments menu in Admin because we don't use it
 */
function remove_menus(){
	remove_menu_page( 'edit-comments.php' );
}
add_action( 'admin_menu', 'remove_menus' );

function hpm_election_night() {
	$args = [
		'p' => 248126,
		'post_type'  => 'page',
		'post_status' => 'publish'
	];
	$election = new WP_Query( $args );
	return $election->post->post_content;
}
add_shortcode( 'election_night', 'hpm_election_night' );

function wpdocs_set_html_mail_content_type() {
	return 'text/html';
}

add_action( 'admin_footer-post-new.php', 'hpm_https_check' );
add_action( 'admin_footer-post.php', 'hpm_https_check' );
add_action( 'admin_footer-post-new.php', 'hpm_npr_api_contributor' );
add_action( 'admin_footer-post.php', 'hpm_npr_api_contributor' );

function hpm_https_check() {
	if ( 'post' !== $GLOBALS['post_type'] ) :
		return;
	endif;
	global $post; ?>
	<script>
		jQuery(document).ready(function($){
			$('#publish, #save-post, #workflow_submit').on('click', function(e){
				var content = $('#content').val();
				if ( content.includes('src="http://') ) {
					e.preventDefault();
					alert( 'This post contains an embed or image from an insecure source.\nPlease check and see if that embed is available via HTTPS.\n\nTo check this:\n\n\t1.  Look for any <img> or <iframe> tags in your HTML\n\t2.  Find the src="" attribute and copy the URL\n\t3.  Change \'http:\' to \'https:\' and paste it into your browser\n\t4.  If it loads correctly, then great! Update the URL in your HTML\n\nIf you have any questions, email jcounts@houstonpublicmedia.org' );
					return false;
				} else {
					return true;
				}
			});
			$('#postimagediv .inside').append( '<p class="hide-if-no-js"><a href="/wp/wp-admin/edit.php?page=hpm-image-preview&p=<?php echo $post->ID; ?>" id="hpm-image-preview" style="color: white; font-weight: bolder; background-color: #0085ba; padding: 5px; text-decoration: none;">Preview featured image</a></p>' );
			$('#hpm-image-preview').on('click', function(e){
				e.preventDefault();
				var href = $(this).attr('href');
				var myWindow = window.open(href, 'HPM Featured Image Preview', "width=850,height=800");
			});
		});
	</script>
	<?php
}

function hpm_npr_api_contributor() {
	if ( 'post' !== $GLOBALS['post_type'] ) :
		return;
	endif;
	$user = wp_get_current_user();
	if ( !in_array( 'contributor', $user->roles ) ) :
		return;
	endif; ?>
	<script>
		jQuery(document).ready(function($){
			$('#send_to_api').prop('checked', false);
		});
	</script>
	<?php
}

if ( !function_exists('hpm_add_allowed_tags' ) ) {
	function hpm_add_allowed_tags( $tags ) {
		$tags['script'] = [
			'src' => true,
		];
		$tags['iframe'] = [
			'src' => true,
		];
		return $tags;
	}
	add_filter( 'wp_kses_allowed_html', 'hpm_add_allowed_tags' );
}

if ( empty( wp_next_scheduled( 'oasiswf_auto_delete_history_schedule' ) ) ) :
	wp_schedule_event(time(), 'daily', 'oasiswf_auto_delete_history_schedule');
endif;

add_action( 'rest_api_init', 'custom_register_coauthors' );
function custom_register_coauthors() {
	register_rest_field( 'post',
		'coauthors',
		[
			'get_callback'    => 'custom_get_coauthors',
			'update_callback' => null,
			'schema'          => null,
		]
	);
}

function custom_get_coauthors( $object, $field_name, $request ) {
	$coauthors = get_coauthors($object['id']);

	$authors = [];
	foreach ( $coauthors as $author ) {
		$authors[] = [
			'display_name' => $author->display_name,
			'user_nicename' => $author->user_nicename
		];
	};
	return $authors;
}

function hpm_segments( $name, $date ) {
	$shows = [
		'Morning Edition' => [
			'source' => 'npr',
			'id' => 3
		],
		'1A' => [
			'source' => 'npr',
			'id' => 65
		],
		'Texas Standard' => [
			'source' => 'wp-rss',
			'id' => 'https://www.texasstandard.org/'
		],
		'Fresh Air' => [
			'source' => 'npr',
			'id' => 13
		],
		'Houston Matters' => [
			'source' => 'local'
		],
		'Think' => [
			'source' => 'wp',
			'id' => 'https://think.kera.org/wp-json/wp/v2/posts'
		],
		'Here and Now' => [
			'source' => 'npr',
			'id' => 60
		],
		'All Things Considered' => [
			'source' => 'npr',
			'id' => 2
		],
		'BBC World Service' => [
			'source' => 'regex',
			'id' => 'https://www.bbc.co.uk/schedules/p00fzl9p/'
		],
		'Weekend Edition Saturday' => [
			'source' => 'npr',
			'id' => 7
		],
		'Weekend Edition Sunday' => [
			'source' => 'npr',
			'id' => 10
		],
		'TED Radio Hour' => [
			'source' => 'npr',
			'id' => 57
		],
		'Ask Me Another' => [
			'source' => 'npr',
			'id' => 58
		],
		'Wait Wait... Don\'t Tell Me!' => [
			'source' => 'npr',
			'id' => 35
		],
		'Latino USA' => [
			'source' => 'npr',
			'id' => 22
		]
	];
	$output = '';
	$dx = explode( '-', $date );
	$du = mktime( 0,0,0, $dx[1], $dx[2], $dx[0] );
	$dt = date( 'Y-m-d', $du + DAY_IN_SECONDS );
	$trans = 'hpm_'.sanitize_title( $name ).'-'.$date;
	if ( empty( $shows[$name] ) ) :
		return $output;
	else :
		if ( $shows[$name]['source'] == 'npr' ) :
			$transient = get_transient( $trans );
			if ( !empty( $transient ) ) :
				return $transient;
			else :
				$api_key = get_option( 'ds_npr_api_key' );
				$url = "https://api.npr.org/query?id={$shows[$name]['id']}&fields=title&output=JSON&numResults=20&date={$date}&apiKey={$api_key}";
				$remote = wp_remote_get( esc_url_raw( $url ) );
				if ( is_wp_error( $remote ) ) :
					return $output;
				else :
					$api = wp_remote_retrieve_body( $remote );
					$json = json_decode( $api, TRUE );
					if ( !empty( $json['list']['story'] ) ) :
						$output .= "<div class=\"progsegment\"><h4>Segments for {$date}</h4><ul>";
						foreach ( $json['list']['story'] as $j ) :
							foreach ( $j['link'] as $jl ) :
								if ( $jl['type'] == 'html' ) :
									$link = $jl['$text'];
								endif;
							endforeach;
							$output .= '<li><a href="'.$link.'" target="_blank">'.$j['title']['$text'].'</a></li>';
						endforeach;
						$output .= "</ul></div>";
					endif;
				endif;
				set_transient( $trans, $output, HOUR_IN_SECONDS );
			endif;
		elseif ( $shows[$name]['source'] == 'regex' ) :
			if ( $name == 'BBC World Service' ) :
				$offset = str_replace( '-', '', get_option( 'gmt_offset' ) );
				$output .= "<div class=\"progsegment\"><h4>Schedule</h4><ul><li><a href=\"{$shows[$name]['id']}{$dx[0]}/{$dx[1]}/{$dx[2]}?utcoffset=-0{$offset}:00\" target=\"_blank\">BBC Schedule for {$date}</a></li></ul></div>";
				return $output;
			endif;
		elseif ( $shows[$name]['source'] == 'wp-rss' ) :
			$transient = get_transient( $trans );
			if ( !empty( $transient ) ) :
				return $transient;
			else :
				$url = $shows[$name]['id']. str_replace( '-', '/', $date ) . "/feed/";
				$remote = wp_remote_get( esc_url_raw( $url ) );
				if ( is_wp_error( $remote ) ) :
					return $output;
				else :
					$dom = simplexml_load_string( wp_remote_retrieve_body( $remote ) );
					$json = json_decode( json_encode( $dom ), true );
					$title = strtolower( 'Texas Standard For ' . date( 'F j, Y', $du ) );
					$set = false;
					if ( !empty( $json ) ) :
						foreach ( $json['channel']['item'] as $item ) :
							if ( !$set ) :
								if ( strtolower( $item['title'] ) === $title ) :
									$output .= '<div class="progsegment"><h4>Program for '. $date . '</h4><ul><li><a href="'.$item['link'].'" target="_blank">' . $item['title'] .'</a></li></ul></div>';
									$set = true;
								endif;
							endif;
						endforeach;
					endif;
				endif;
				set_transient( $trans, $output, HOUR_IN_SECONDS );
			endif;
		elseif ( $shows[$name]['source'] == 'wp' ) :
			$transient = get_transient( $trans );
			if ( !empty( $transient ) ) :
				return $transient;
			else :
				$url = $shows[$name]['id']."?before=".$dt."T00:00:00&after=".$date."T00:00:00";
				$remote = wp_remote_get( esc_url_raw( $url ) );
				if ( is_wp_error( $remote ) ) :
					return $output;
				else :
					$api = wp_remote_retrieve_body( $remote );
					$json = json_decode( $api );
					if ( !empty( $json ) ) :
						$output .= "<div class=\"progsegment\"><h4>Segments for {$date}</h4><ul>";
						foreach ( $json as $j ) :
							$output .= '<li><a href="'.$j->link.'" target="_blank">'.$j->title->rendered.'</a></li>';
						endforeach;
						$output .= "</ul></div>";
					endif;
				endif;
				set_transient( $trans, $output, HOUR_IN_SECONDS );
			endif;
		elseif ( $shows[$name]['source'] == 'local' ) :
			if ( $name == 'Houston Matters' ) :
				$hm = new WP_Query( [
					'year' => $dx[0],
					'monthnum' => $dx[1],
					'day' => $dx[2],
					'cat' => 58,
					'post_type' => 'post',
					'post_status' => 'publish',
					'ignore_sticky_posts' => 1
				] );
				if ( $hm->have_posts() ) :
					$output .= "<div class=\"progsegment\"><h4>Segments for {$date}</h4><ul>";
					while( $hm->have_posts() ) :
						$hm->the_post();
						$output .= '<li><a href="'.get_the_permalink().'">'.get_the_title().'</a></li>';
					endwhile;
					$output .= '</ul></div>';
				endif;
				wp_reset_query();
			else :
				return $output;
			endif;
		else :
			return $output;
		endif;
	endif;
	return $output;
}

add_filter( 'xmlrpc_enabled', '__return_false' );

function hpm_reset_password_message( $message, $key ) {
	if ( strpos( $_POST['user_login'], '@' ) ) :
		$user_data = get_user_by( 'email', trim( $_POST['user_login'] ) );
	else :
		$login = trim( $_POST['user_login'] );
		$user_data = get_user_by( 'login', $login );
	endif;

	$user_login = $user_data->user_login;

	$msg = __( 'The password for the following account has been requested to be reset:' ). "\r\n\r\n";
	$msg .= network_site_url() . "\r\n\r\n";
	$msg .= sprintf( __( 'Username: %s' ), $user_login ) . "\r\n\r\n";
	$msg .= __( 'If this message was sent in error, please ignore this email.' ) . "\r\n\r\n";
	$msg .= __( 'To reset your password, visit the following address:' ) . "\r\n\r\n";
	$msg .= network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . "\r\n";
	return $msg;

}
add_filter( 'retrieve_password_message', 'hpm_reset_password_message', null, 2 );

function hpm_image_preview_page() {
	$hook = add_submenu_page( 'edit.php', 'Featured Image Preview', 'Featured Image Preview', 'edit_posts', 'hpm-image-preview', function() {} );
	add_action('load-' . $hook, function() {
		$post_id = sanitize_text_field( $_GET['p'] );
		$versions = hpm_versions();
		$top_cat = hpm_top_cat( $post_id );
		$img = [
			'thumb' => get_the_post_thumbnail_url( $post_id, 'thumbnail' ),
			'large' => get_the_post_thumbnail_url( $post_id, 'large' )
		];
		$title = get_the_title( $post_id );
		$postClass = get_post_class( '', $post_id ); ?>
<!DOCTYPE html>
<html lang="en-US" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
	<head>
		<meta charset="UTF-8">
		<link rel="profile" href="http://gmpg.org/xfn/11">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<title>HPM Featured Image Preview</title>
		<link rel="stylesheet" id="hpmv2-style-css"  href="https://cdn.hpm.io/assets/css/style.css?ver=<?php echo $versions['css']; ?>" type="text/css" media="all" />
		<style>
			@media screen and (min-width: 52.5em) {
				#float-wrap {
    				max-width: 50em;
					margin: 1em auto;
				}
				#float-wrap article.felix-type-a, #float-wrap article.felix-type-b, #float-wrap article.felix-type-c {
					width: 95%;
    				margin: 0 2.5% 1em 2.5%;
				}
				#float-wrap article.felix-type-d {
					margin: 0 auto 1em;
					width: 45%;
					padding: 1em 1.5em;
				}
			}
		</style>
	</head>
	<body class="home blog">
		<div id="page" class="hfeed site">
			<div id="content" class="site-content">
				<div id="primary" class="content-area">
					<main id="main" class="site-main" role="main">
						<div id="float-wrap">
<?php
		if ( empty( $post_id ) ) : ?>
							<h2 style="width: 100%;">Enter the ID number of the post you want to preview</h2>
							<form action="" method="GET">
								<input type="hidden" name="page" value="hpm-image-preview" />
								<input type="number" name="p" value="" />
								<input type="submit" value="Submit" />
							</form>
<?php
		elseif ( ! in_array( 'has-post-thumbnail', $postClass ) ) : ?>
							<h2 style="width: 100%;">The article you're previewing doesn't have a featured image. Set one in the editor and refresh this page.</h2>
<?php
		elseif ( is_user_logged_in() && current_user_can( 'edit_post', $post_id ) ) : ?>
							<article class="<?php echo implode( ' ', $postClass ); ?> felix-type-a grid-item grid-item--width2">
								<div class="thumbnail-wrap" style="background-image: url(<?php echo $img['large']; ?>)">
									<a class="post-thumbnail" href="#" aria-hidden="true"></a>
								</div>
								<header class="entry-header">
									<h3><?PHP echo $top_cat; ?></h3>
									<h2 class="entry-title"><a href="#" rel="bookmark"><?php echo $title; ?></a></h2>
								</header>
							</article>
							<article class="<?php echo implode( ' ', $postClass ); ?> felix-type-b grid-item grid-item--width2">
								<div class="thumbnail-wrap" style="background-image: url(<?php echo $img['thumb']; ?>)">
									<a class="post-thumbnail" href="#" aria-hidden="true"></a>
								</div>
								<header class="entry-header">
									<h3><?PHP echo $top_cat; ?></h3>
									<h2 class="entry-title"><a href="#" rel="bookmark"><?php echo $title; ?></a></h2>
								</header>
							</article>
							<article class="<?php echo implode( ' ', $postClass ); ?> felix-type-d grid-item grid-item--width2">
								<div class="thumbnail-wrap" style="background-image: url(<?php echo $img['thumb']; ?>)">
									<a class="post-thumbnail" href="#" aria-hidden="true"></a>
								</div>
								<header class="entry-header">
									<h3><?PHP echo $top_cat; ?></h3>
									<h2 class="entry-title"><a href="#" rel="bookmark"><?php echo $title; ?></a></h2>
								</header>
							</article>
<?php
		endif; ?>
						</div>
					</main><!-- .site-main -->
				</div><!-- .content-area -->
			</div><!-- .site-content -->
		</div><!-- .site -->
	</body>
</html><?php
		exit;
	});
}

add_action('admin_menu', 'hpm_image_preview_page');


/* ------------------------------------------------------------------------ *
 * post_class() and body_class support pulled from PostScript plugin
 *   by Barrett Golding (https://rjionline.org/)
 * ------------------------------------------------------------------------ */

/**
 * Displays meta box on post editor screen (both new and edit pages).
 */
function postscript_meta_box_setup() {
    $user    = wp_get_current_user();
    $roles   = [ 'administrator' ];

    // Add meta boxes only for allowed user roles.
    if ( array_intersect( $roles, $user->roles ) ) {
        // Add meta box.
        add_action( 'add_meta_boxes', 'postscript_add_meta_box' );

        // Save post meta.
        add_action( 'save_post', 'postscript_save_post_meta', 10, 2 );
    }
}
add_action( 'load-post.php', 'postscript_meta_box_setup' );
add_action( 'load-post-new.php', 'postscript_meta_box_setup' );


function postscript_metabox_admin_notice() {
    $postscript_meta = get_post_meta( get_the_id(), 'postscript_meta', true );
    ?>
    <div class="error">
    <?php var_dump( $_POST ) ?>
        <p><?php _e( 'Error!', 'postscript' ); ?></p>
    </div>
    <?php
}

/**
 * Creates meta box for the post editor screen (for user-selected post types).
 *
 * Passes array of user-setting options to callback.
 *
 * @uses postscript_get_options()   Safely gets option from database.
 */
function postscript_add_meta_box() {
    $options = [
		'user_roles' => [ 'administrator' ],
		'post_types' => [ 'post', 'page', 'shows' ],
		'allow' => [ 'class_body' => 'on', 'class_post' => 'on' ]
	];

    add_meta_box(
        'postscript-meta',
        esc_html__( 'Postscript', 'postscript' ),
        'postscript_meta_box_callback',
        $options['post_types'],
        'side',
        'default',
        $options
    );
}

/**
 * Builds HTML form for the post meta box.
 *
 * Form elements are text fields for entering body/post classes (stored in same post-meta array).
 *
 * Form elements are printed only if allowed on Setting page.
 *
 * @param  Object $post Object containing the current post.
 * @param  array  $box  Array of meta box id, title, callback, and args elements.
 */
function postscript_meta_box_callback( $post, $box ) {
    $post_id = $post->ID;
    wp_nonce_field( basename( __FILE__ ), 'postscript_meta_nonce' );

    // Display text fields for: URLs (style/script) and classes (body/post).
    $opt_allow       = $box['args']['allow'];
    $postscript_meta = get_post_meta( $post_id, 'postscript_meta', true );

    if ( isset ( $opt_allow['class_body'] ) ) { // Admin setting allows body_class() text field. ?>
    <p>
        <label for="postscript-class-body"><?php _e( 'Body class:', 'postscript' ); ?></label><br />
        <input class="widefat" type="text" name="postscript_meta[class_body]" id="postscript-class-body" value="<?php if ( isset ( $postscript_meta['class_body'] ) ) { echo sanitize_html_class( $postscript_meta['class_body'] ); } ?>" size="30" />
    </p>
    <?php } ?>
    <?php if ( isset ( $opt_allow['class_post'] ) ) { // Admin setting allows post_class() text field. ?>
    <p>
        <label for="postscript-class-post"><?php _e( 'Post class:', 'postscript' ); ?></label><br />
        <input class="widefat" type="text" name="postscript_meta[class_post]" id="postscript-class-post" value="<?php if ( isset ( $postscript_meta['class_post'] ) ) { echo sanitize_html_class( $postscript_meta['class_post'] ); } ?>" size="30" />
    </p>
    <?php
    }
}

/**
 * Saves the meta box form data upon submission.
 *
 * @uses  postscript_sanitize_data()    Sanitizes $_POST array.
 *
 * @param int     $post_id    Post ID.
 * @param WP_Post $post       Post object.
 */
function postscript_save_post_meta( $post_id, $post ) {
    // Checks save status
    $is_autosave = wp_is_post_autosave( $post_id );
    $is_revision = wp_is_post_revision( $post_id );
    $is_valid_nonce = ( isset( $_POST[ 'postscript_meta_nonce' ] ) && wp_verify_nonce( $_POST[ 'postscript_meta_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';

    // Exits script depending on save status
    if ( $is_autosave || $is_revision || ! $is_valid_nonce ) {
        return;
    }

    // Get the post type object (to match with current user capability).
    $post_type = get_post_type_object( $post->post_type );

    // Check if the current user has permission to edit the post.
    if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
        return $post_id;
    }

    $meta_key   = 'postscript_meta';
    $meta_value = get_post_meta( $post_id, $meta_key, true );

    // If any user-submitted form fields have a value.
    // (implode() reduces array values to a string to do the check).
    if ( isset( $_POST['postscript_meta'] ) && implode( $_POST['postscript_meta'] ) ) {
        $form_data  = postscript_sanitize_data( $_POST['postscript_meta'] );
    } else {
        $form_data  = null;
    }

    // Add post-meta, if none exists, and if user entered new form data.
    if ( $form_data && '' == $meta_value ) {
        add_post_meta( $post_id, $meta_key, $form_data, true );

    // Update post-meta if user changed existing post-meta values in form.
    } elseif ( $form_data && $form_data != $meta_value ) {
        update_post_meta( $post_id, $meta_key, $form_data );

    // Delete existing post-meta if user cleared all post-meta values from form.
    } elseif ( null == $form_data && $meta_value ) {
        delete_post_meta( $post_id, $meta_key );

    // Any other possibilities?
    } else {
        return;
    }
}



/**
 * Sanitizes values in an one- and multi- dimensional arrays.
 *
 * Used by post meta-box form before writing post-meta to database
 * and by Settings API before writing option to database.
 *
 * @link https://tommcfarlin.com/input-sanitization-with-the-wordpress-settings-api/
 *
 * @since    0.4.0
 *
 * @param    array    $input        The address input.
 * @return   array    $input_clean  The sanitized input.
 */
function postscript_sanitize_data( $data = [] ) {
    // Initialize a new array to hold the sanitized values.
    $data_clean = [];

    // Check for non-empty array.
    if ( ! is_array( $data ) || ! count( $data )) {
        return [];
    }

    // Traverse the array and sanitize each value.
    foreach ( $data as $key => $value) {
        // For one-dimensional array.
        if ( ! is_array( $value ) && ! is_object( $value ) ) {
            // Remove blank lines and whitespaces.
            $value = preg_replace( '/^\h*\v+/m', '', trim( $value ) );
            $value = str_replace( ' ', '', $value );
            $data_clean[ $key ] = sanitize_text_field( $value );
        }

        // For multidimensional array.
        if ( is_array( $value ) ) {
            $data_clean[ $key ] = postscript_sanitize_data( $value );
        }
    }

    return $data_clean;
}

/**
 * Sanitizes values in an one-dimensional array.
 * (Used by post meta-box form before writing post-meta to database.)
 *
 * @link https://tommcfarlin.com/input-sanitization-with-the-wordpress-settings-api/
 *
 * @since    0.4.0
 *
 * @param    array    $input        The address input.
 * @return   array    $input_clean  The sanitized input.
 */
function postscript_sanitize_array( $input ) {
    // Initialize a new array to hold the sanitized values.
    $input_clean = [];

    // Traverse the array and sanitize each value.
    foreach ( $input as $key => $val ) {
        $input_clean[ $key ] = sanitize_text_field( $val );
    }

    return $input_clean;
}

function postscript_remove_empty_lines( $string ) {
    return preg_replace( "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $string );
}

/**
 * Adds user-entered class(es) to the body tag.
 *
 * @uses postscript_get_options()   Safely gets option from database.
 * @return  array $classes  WordPress defaults and user-added classes
 */
function postscript_class_body( $classes ) {
    $post_id = get_the_ID();
    $options = [
		'user_roles' => [ 'administrator' ],
		'post_types' => [ 'post', 'page', 'shows' ],
		'allow' => [ 'class_body' => 'on', 'class_post' => 'on' ]
	];

    if ( ! empty( $post_id ) && isset( $options['allow']['class_body'] ) ) {
        // Get the custom post class.
        $postscript_meta = get_post_meta( $post_id, 'postscript_meta', true );

        // If a post class was input, sanitize it and add it to the body class array.
        if ( ! empty( $postscript_meta['class_body'] ) ) {
            $classes[] = sanitize_html_class( $postscript_meta['class_body'] );
        }
    }

    return $classes;
}
add_filter( 'body_class', 'postscript_class_body' );


/**
 * Adds user-entered class(es) to the post class list.
 *
 * @uses postscript_get_options()   Safely gets option from database.
 * @return  array $classes  WordPress defaults and user-added classes
 */
function postscript_class_post( $classes ) {
    $post_id = get_the_ID();
    $options = [
		'user_roles' => [ 'administrator' ],
		'post_types' => [ 'post', 'page', 'shows' ],
		'allow' => [ 'class_body' => 'on', 'class_post' => 'on' ]
	];

    if ( ! empty( $post_id ) && isset( $options['allow']['class_post'] ) ) {
        // Get the custom post class.
        $postscript_meta = get_post_meta( $post_id, 'postscript_meta', true );

        // If a post class was input, sanitize it and add it to the post class array.
        if ( ! empty( $postscript_meta['class_post'] ) ) {
            $classes[] = sanitize_html_class( $postscript_meta['class_post'] );
        }
    }

    return $classes;
}
add_filter( 'post_class', 'postscript_class_post' );