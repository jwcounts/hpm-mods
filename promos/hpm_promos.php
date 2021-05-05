<?php
/**
 * Internal support for promotional banners and lightboxes
 */

class HPM_Promos {

	protected $options;

	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'init' ] );
		add_action( 'init', [ $this, 'create_type' ] );
	}

	/**
	 * Init
	 */
	public function init() {
		$this->options = get_option( 'hpm_promos_settings' );
		add_action( 'admin_init', [ $this, 'add_role_caps' ], 999 );
		add_action( 'save_post', [ $this, 'save_meta' ], 10, 2 );
		add_action( 'post_submitbox_misc_actions', [ $this, 'unpub_date' ] );
		add_action( 'hpm_promo_cleanup', [ $this, 'cleanup' ] );
		add_filter( 'manage_edit-promos_columns', [ $this, 'edit_columns' ] );
		add_action( 'manage_promos_posts_custom_column', [ $this, 'manage_columns' ], 10, 2 );
		add_action( 'wp_footer', function() {
			echo $this->generate();
		}, 100 );

		// Create menu in Admin Dashboard
		add_action( 'admin_menu', [ $this, 'create_menu' ] );
		add_filter( 'pre_update_option_hpm_promos_settings', [ $this, 'options_clean' ], 10, 2 );

		// Make sure that the proper cron job is scheduled
		if ( ! wp_next_scheduled( 'hpm_promo_cleanup' ) ) :
			wp_schedule_event( time(), 'daily', 'hpm_promo_cleanup' );
		endif;
	}

	public function create_type() {
		register_post_type( 'promos',
			[
				'labels'               => [
					'name'               => __( 'Promo Alerts' ),
					'singular_name'      => __( 'Promo Alert' ),
					'menu_name'          => __( 'Promo Alerts' ),
					'add_new_item'       => __( 'Add New Promo Alert' ),
					'edit_item'          => __( 'Edit Promo Alert' ),
					'new_item'           => __( 'New Promo Alert' ),
					'view_item'          => __( 'View Promo Alert' ),
					'search_items'       => __( 'Search Promo Alerts' ),
					'not_found'          => __( 'Promo Alert Not Found' ),
					'not_found_in_trash' => __( 'Promo Alert not found in trash' )
				],
				'description'          => 'Internal promotional alerts for the homepage and internal page sidebars',
				'public'               => false,
				'show_ui'              => true,
				'show_in_admin_bar'    => true,
				'menu_position'        => 20,
				'menu_icon'            => 'dashicons-warning',
				'has_archive'          => false,
				'rewrite'              => false,
				'supports'             => [ 'title', 'editor' ],
				'can_export'           => false,
				'capability_type'      => [ 'hpm_promo', 'hpm_promos' ],
				'map_meta_cap'         => true,
				'register_meta_box_cb' => [ $this, 'add_meta' ],
				'show_in_graphql' => true,
				'graphql_single_name' => 'Promo',
				'graphql_plural_name' => 'Promos'
			]
		);
	}

	public function add_role_caps() {
		// Add the roles you'd like to administer the custom post types
		$roles = [ 'administrator', 'editor' ];

		// Loop through each role and assign capabilities
		foreach ( $roles as $the_role ) :
			$role = get_role( $the_role );
			$role->add_cap( 'read' );
			$role->add_cap( 'read_hpm_promo' );
			$role->add_cap( 'read_private_hpm_promos' );
			$role->add_cap( 'edit_hpm_promo' );
			$role->add_cap( 'edit_hpm_promos' );
			$role->add_cap( 'edit_others_hpm_promos' );
			$role->add_cap( 'edit_published_hpm_promos' );
			$role->add_cap( 'publish_hpm_promos' );
			$role->add_cap( 'delete_others_hpm_promos' );
			$role->add_cap( 'delete_private_hpm_promos' );
			$role->add_cap( 'delete_published_hpm_promos' );
		endforeach;
	}


	public function add_meta() {
		add_meta_box(
			'hpm-promos-meta-class',
			esc_html__( 'Alert Metadata', 'example' ),
			[ $this, 'meta_box' ],
			'promos',
			'normal',
			'core'
		);
	}

	public function meta_box( $object, $box ) {
		wp_nonce_field( basename( __FILE__ ), 'hpm_promos_class_nonce' );
		$hpm_promo = get_post_meta( $object->ID, 'hpm_promos_meta', true );
		if ( empty( $hpm_promo ) ) :
			$hpm_promo = [
				'location' => 'homepage',
				'type' => 'sidebar',
				'options' => [
					'sidebar' => [
						'mobile' => '',
						'tablet' => '',
						'desktop' => ''
					],
					'fullwidth' => [
						'mobile' => '',
						'tablet' => '',
						'desktop' => ''
					],
					'lightbox' => [
						'a' => [
							'link' => '',
							'image' => '',
							'text' => ''
						],
						'b' => [
							'link' => '',
							'image' => '',
							'text' => ''
						],
						'total' => ''
					],
					'emergency' => [],
					'dont-miss' => []
				]
			];
		endif;
		$editor_opts = [
			'editor_height' => 150,
			'media_buttons' => false,
			'teeny' => true
		]; ?>
		<h3><?PHP _e( "Where do you want your element to show up?", 'hpmv2' ); ?></h3>
		<p><label for="hpm_promo[location]"><?php _e( "Location:", 'hpmv2' ); ?></label>
			<select id="hpm_promo[location]" name="hpm_promo[location]">
				<option value="any" <?PHP selected( $hpm_promo['location'], 'any', TRUE ); ?>>Any Page</option>
				<option value="homepage" <?PHP selected( $hpm_promo['location'], 'homepage', TRUE ); ?>>Homepage Only</option>
			</select>
		</p>
		<h3><?PHP _e( "What type of banner are you creating?", 'hpmv2' ); ?></h3>
		<p><label for="hpm_promo[type]"><?php _e( "Type:", 'hpmv2' ); ?></label>
			<select id="hpm_promo_type" name="hpm_promo[type]">
				<option value="sidebar" <?PHP selected( $hpm_promo['type'], 'sidebar', TRUE ); ?>>Sidebar Banner/Poll</option>
				<option value="dont-miss" <?PHP selected( $hpm_promo['type'], 'dont-miss', TRUE ); ?>>Don't Miss Bullet Point</option>
				<option value="lightbox" <?PHP selected( $hpm_promo['type'], 'lightbox', TRUE ); ?>>Lightbox</option>
				<option value="emergency" <?PHP selected( $hpm_promo['type'], 'emergency', TRUE ); ?>>Emergency Notification</option>
				<option value="fullwidth" <?PHP selected( $hpm_promo['type'], 'fullwidth', TRUE ); ?>>Full-Width Banner</option>
			</select>
		</p>
		<div id="hpm-sidebar" class="hpm-promo-types"<?php echo ( $hpm_promo['type'] == 'sidebar' ? '' : ' style="display: none;"' ); ?>>
			<h3><?php _e( "Sidebar Banner Options", 'hpmv2' ); ?></h3>
			<p><?php _e( "The Sidebar banner allows for alternate image versions for mobile, tablet, and desktop, if
				desired. If you only wish to use a single image size, you can just include it in the HTML. If you
				want to use multiple versions, paste the image URLs in the boxes below, and place [[image]] in the
				image source in your HTML.", 'hpmv2' ); ?></p>
			<ul>
				<li><label for="hpm_promo[options][sidebar][mobile]"><?php _e('Mobile: ', 'hpmv2' ); ?></label><input type="text" name="hpm_promo[options][sidebar][mobile]" value="<?php echo $hpm_promo['options']['sidebar']['mobile']; ?>" style="max-width: 100%; width: 800px;" /></li>
				<li><label for="hpm_promo[options][sidebar][tablet]"><?php _e('Tablet: ', 'hpmv2' ); ?></label><input type="text" name="hpm_promo[options][sidebar][tablet]" value="<?php echo $hpm_promo['options']['sidebar']['tablet']; ?>" style="max-width: 100%; width: 800px;" /></li>
				<li><label for="hpm_promo[options][sidebar][desktop]"><?php _e('Desktop: ', 'hpmv2' ); ?></label><input type="text" name="hpm_promo[options][sidebar][desktop]" value="<?php echo $hpm_promo['options']['sidebar']['desktop']; ?>" style="max-width: 100%; width: 800px;" /></li>
			</ul>
		</div>
		<div id="hpm-fullwidth" class="hpm-promo-types"<?php echo ( $hpm_promo['type'] == 'fullwidth' ? '' : ' style="display: none;"' ); ?>>
			<h3><?php _e( "Full-Width Banner Options", 'hpmv2' ); ?></h3>
			<p><?php _e( "The Full-Width banner allows for alternate image versions for mobile, tablet, and desktop, if
				desired. If you only wish to use a single image size, you can just include it in the HTML. If you
				want to use multiple versions, paste the image URLs in the boxes below, and place [[image]] in the
				image source in your HTML.", 'hpmv2' ); ?></p>
			<ul>
				<li><label for="hpm_promo[options][fullwidth][mobile]"><?php _e('Mobile: ', 'hpmv2' ); ?></label><input type="text" name="hpm_promo[options][fullwidth][mobile]" value="<?php echo $hpm_promo['options']['fullwidth']['mobile']; ?>" style="max-width: 100%; width: 800px;" /></li>
				<li><label for="hpm_promo[options][fullwidth][tablet]"><?php _e('Tablet: ', 'hpmv2' ); ?></label><input type="text" name="hpm_promo[options][fullwidth][tablet]" value="<?php echo $hpm_promo['options']['fullwidth']['tablet']; ?>" style="max-width: 100%; width: 800px;" /></li>
				<li><label for="hpm_promo[options][fullwidth][desktop]"><?php _e('Desktop: ', 'hpmv2' ); ?></label><input type="text" name="hpm_promo[options][fullwidth][desktop]" value="<?php echo $hpm_promo['options']['fullwidth']['desktop']; ?>" style="max-width: 100%; width: 800px;" /></li>
			</ul>
		</div>
		<div id="hpm-lightbox" class="hpm-promo-types"<?php echo ( $hpm_promo['type'] == 'lightbox' ? '' : ' style="display: none;"'); ?>>
			<h3><?php _e( "Lightbox Options", 'hpmv2' ); ?></h3>
			<p><?php _e( "The Lightbox allows for A/B testing of images, text, and links, and has an option for showing a
				pledge total.", 'hpmv2' ); ?></p>
			<p><?php _e( "To use the total, or the A/B testing option, simply put these placeholders into your HTML: [[link]], [[image]], [[text]], [[total]]", 'hpmv2' ); ?></p>
			<p><strong><?php _e( "Version A", 'hpmv2' ); ?></strong></p>
			<ul style="margin-bottom: 2em;">
				<li><label for="hpm_promo[options][lightbox][a][link]"><?php _e('Link: ', 'hpmv2' ); ?></label><input type="text" name="hpm_promo[options][lightbox][a][link]" value="<?php echo $hpm_promo['options']['lightbox']['a']['link']; ?>" style="max-width: 100%; width: 800px;" /></li>
				<li><label for="hpm_promo[options][lightbox][a][text]"><?php _e('Text: ', 'hpmv2' ); ?></label>
					<?php wp_editor( $hpm_promo['options']['lightbox']['a']['text'], 'hpm_promo[options][lightbox][a][text]', $editor_opts ); ?>
				</li>
				<li><label for="hpm_promo[options][lightbox][a][image]"><?php _e('Image: ', 'hpmv2' ); ?></label><input type="text" name="hpm_promo[options][lightbox][a][image]" value="<?php echo $hpm_promo['options']['lightbox']['a']['image']; ?>" style="max-width: 100%; width: 800px;" /></li>
			</ul>
			<p><strong><?php _e( "Version B", 'hpmv2' ); ?></strong></p>
			<ul style="margin-bottom: 2em;">
				<li><label for="hpm_promo[options][lightbox][b][link]"><?php _e('Link: ', 'hpmv2' ); ?></label><input type="text" name="hpm_promo[options][lightbox][b][link]" value="<?php echo $hpm_promo['options']['lightbox']['b']['link']; ?>" style="max-width: 100%; width: 800px;" /></li>
				<li><label for="hpm_promo[options][lightbox][b][text]"><?php _e('Text: ', 'hpmv2' ); ?></label>
					<?php wp_editor( $hpm_promo['options']['lightbox']['b']['text'], 'hpm_promo[options][lightbox][b][text]', $editor_opts ); ?>
				</li>
				<li><label for="hpm_promo[options][lightbox][b][image]"><?php _e('Image: ', 'hpmv2' ); ?></label><input type="text" name="hpm_promo[options][lightbox][b][image]" value="<?php echo $hpm_promo['options']['lightbox']['b']['image']; ?>" style="max-width: 100%; width: 800px;" /></li>
			</ul>
			<p><strong><?php _e( "Pledge Total", 'hpmv2' ); ?></strong></p>
			<ul style="margin-bottom: 2em;">
				<li><label for="hpm_promo[options][lightbox][total]"><?php _e('Link to JSON File: ', 'hpmv2' ); ?></label><input type="text" name="hpm_promo[options][lightbox][total]" value="<?php echo $hpm_promo['options']['lightbox']['total']; ?>" style="max-width: 100%; width: 800px;" /></li>
			</ul>
		</div>
		<div id="hpm-emergency" class="hpm-promo-types"<?php echo ( $hpm_promo['type'] == 'emergency' ? '' : ' style="display: none;"'); ?>></div>
		<div id="hpm-dont-miss" class="hpm-promo-types"<?php echo ( $hpm_promo['type'] == 'dont-miss' ? '' : ' style="display: none;"'); ?>></div>
		<script>
			jQuery(document).ready(function($){
				$( "#hpm_promo_type" ).change(function () {
					var typeVal = $(this).val();
					$('.hpm-promo-types').hide();
					$('#hpm-'+typeVal).show();
				});
			});
		</script>
		<?php
	}

	public function save_meta( $post_id, $post ) {
		if ( $post->post_type == 'promos' ) :
			/* Verify the nonce before proceeding. */
			if ( ! isset( $_POST['hpm_promos_class_nonce'] ) || ! wp_verify_nonce( $_POST['hpm_promos_class_nonce'], basename( __FILE__ ) ) ) :
				return $post_id;
			endif;

			/* Get the post type object. */
			$post_type = get_post_type_object( $post->post_type );

			/* Check if the current user has permission to edit the post. */
			if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) :
				return $post_id;
			endif;

			$hpend = $_POST['hpm_promo']['end'];

			foreach ( $hpend as $hpe ) :
				if ( !is_numeric( $hpe ) || $hpe == '' ) :
					return $post_id;
				endif;
			endforeach;

			$offset = get_option('gmt_offset')*3600;
			$endtime = mktime( $hpend['hour'], $hpend['min'], 0, $hpend['mon'], $hpend['day'], $hpend['year'] ) - $offset;
			update_post_meta( $post_id, 'hpm_promos_end_time', $endtime );

			$options = $_POST['hpm_promo']['options'];
			foreach ( $options as $k => $v ) :
				if ( is_array( $v ) ) :
					foreach ( $v as $vk => $vv ) :
						if ( is_array( $vv ) ) :
							foreach ( $vv as $vvk => $vvv ) :
								if ( $vvk !== 'text' ) :
									$options[$k][$vk][$vvk] = sanitize_text_field( $vvv );
								else :
									$options[$k][$vk][$vvk] = wp_kses_post( $vvv );
								endif;
							endforeach;
						else :
							$options[$k][$vk] = sanitize_text_field( $vv );
						endif;
					endforeach;
				else :
					$options[$k] = sanitize_text_field( $v );
				endif;
			endforeach;

			$hpm_promo_meta = [
				'location' => $_POST['hpm_promo']['location'],
				'type' => $_POST['hpm_promo']['type'],
				'options' => $options
			];

			update_post_meta( $post_id, 'hpm_promos_meta', $hpm_promo_meta );
		endif;
	}

	public function options_clean( $new_value, $old_value ) {
		$find = [ '{/$}', '{^/}' ];
		$replace = [ '', '' ];
		foreach ( $new_value['bans'] as $k => $v ) :
			$new_value['bans'][$k] = preg_replace( '/\s/', '', $v );
		endforeach;
		return $new_value;
	}

	public function unpub_date() {
		global $post;
		if ( ! current_user_can( 'edit_others_posts', $post->ID ) ) return false;
		if ( $post->post_type == 'promos' ) :
			$endtime = get_post_meta( $post->ID, 'hpm_promos_end_time', true );
			$offset = get_option('gmt_offset')*3600;
			if ( empty( $endtime ) ) :
				$t = time() + $offset + ( 24 * HOUR_IN_SECONDS );
			else :
				$t = $endtime + $offset;
			endif;
			$timeend = [
				'mon' => date( 'm', $t),
				'day' => date( 'd', $t),
				'year' => date( 'Y', $t),
				'hour' => date( 'H', $t),
				'min' => date( 'i', $t)
			];

		?>
<div class="misc-pub-section curtime misc-pub-curtime">
	<span id="endtimestamp">End Date:</span>
	<fieldset id="endtimestampdiv">
		<legend class="screen-reader-text">End date and time</legend>
		<div class="timestamp-wrap">
			<label>
				<span class="screen-reader-text">Month</span>
				<select id="hpm_promo_end_mon" name="hpm_promo[end][mon]">
					<option value="01" data-text="Jan" <?PHP selected( $timeend['mon'], '01', TRUE ); ?>>01-Jan</option>
					<option value="02" data-text="Feb" <?PHP selected( $timeend['mon'], '02', TRUE ); ?>>02-Feb</option>
					<option value="03" data-text="Mar" <?PHP selected( $timeend['mon'], '03', TRUE ); ?>>03-Mar</option>
					<option value="04" data-text="Apr" <?PHP selected( $timeend['mon'], '04', TRUE ); ?>>04-Apr</option>
					<option value="05" data-text="May" <?PHP selected( $timeend['mon'], '05', TRUE ); ?>>05-May</option>
					<option value="06" data-text="Jun" <?PHP selected( $timeend['mon'], '06', TRUE ); ?>>06-Jun</option>
					<option value="07" data-text="Jul" <?PHP selected( $timeend['mon'], '07', TRUE ); ?>>07-Jul</option>
					<option value="08" data-text="Aug" <?PHP selected( $timeend['mon'], '08', TRUE ); ?>>08-Aug</option>
					<option value="09" data-text="Sep" <?PHP selected( $timeend['mon'], '09', TRUE ); ?>>09-Sep</option>
					<option value="10" data-text="Oct" <?PHP selected( $timeend['mon'], '10', TRUE ); ?>>10-Oct</option>
					<option value="11" data-text="Nov" <?PHP selected( $timeend['mon'], '11', TRUE ); ?>>11-Nov</option>
					<option value="12" data-text="Dec" <?PHP selected( $timeend['mon'], '12', TRUE ); ?>>12-Dec</option>
				</select>
			</label>
			<label>
				<span class="screen-reader-text">Day</span>
				<input type="text" id="hpm_promo_end_day" name="hpm_promo[end][day]" value="<?php echo $timeend['day']; ?>" size="2" maxlength="2" autocomplete="off">
			</label>,
			<label>
				<span class="screen-reader-text">Year</span>
				<input type="text" id="hpm_promo_end_year" name="hpm_promo[end][year]" value="<?php echo $timeend['year']; ?>" size="4" maxlength="4" autocomplete="off">
			</label> @
			<label>
				<span class="screen-reader-text">Hour</span>
				<input type="text" id="hpm_promo_end_hour" name="hpm_promo[end][hour]" value="<?php echo $timeend['hour']; ?>" size="2" maxlength="2" autocomplete="off">
			</label>:
			<label>
				<span class="screen-reader-text">Minute</span>
				<input type="text" id="hpm_promo_end_min" name="hpm_promo[end][min]" value="<?php echo $timeend['min']; ?>" size="2" maxlength="2" autocomplete="off">
			</label>
		</div>
	</fieldset>
</div>
<style>
	.curtime #endtimestamp {
		padding: 2px 0 1px 0;
		display: inline !important;
		height: auto !important;
	}
	.curtime #endtimestamp:before {
		content: "\f145";
		position: relative;
		top: -1px;
		font: normal 20px/1 dashicons;
		speak: none;
		display: inline-block;
		margin-left: -1px;
		padding-right: 3px;
		vertical-align: top;
		-webkit-font-smoothing: antialiased;
		-moz-osx-font-smoothing: grayscale;
		color: #82878c;
	}
	#hpm_promo_end_day,
	#hpm_promo_end_hour,
	#hpm_promo_end_min {
		width: 2em;
	}
	#hpm_promo_end_year,
	#hpm_promo_end_day,
	#hpm_promo_end_hour,
	#hpm_promo_end_min {
		padding: 6px 1px;
		font-size: 12px;
		line-height: 1.16666666;
		text-align: center;
	}
	#hpm_promo_end_year {
		width: 3.5em;
	}
	.wp-core-ui select#hpm_promo_end_mon {
		font-size: 12px;
		line-height: 2.25;
	}
</style>
<?php
		endif;
	}

	public function cleanup() {
		$t = time();
		$offset = get_option('gmt_offset')*3600;
		$t = $t + $offset;
		$now = getdate($t);
		$args = [
			'post_type' => 'promos',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'meta_query' => [
				[
					'key' => 'hpm_promos_end_time',
					'value' => $now[0],
					'compare' => '<=',
				]
			]
		];
		$promos = new WP_Query( $args );
		if ( $promos->have_posts() ) :
			while ( $promos->have_posts() ) :
				$promos->the_post();
				wp_trash_post( get_the_ID() );
			endwhile;
		endif;
	}

	public function generate() {
		global $wp_query;
		$wp_global = $wp_query;
		$output = '';
		$dont = [];
		$lightbox = $fullwidth = 0;
		if ( $wp_query->post->post_type == 'embeds' ) :
			return $output;
		endif;
		if ( $wp_global->is_page || $wp_global->is_single ) :
			$page_id = $wp_global->get_queried_object_id();
			$anc = get_post_ancestors( $page_id );
			$opts = $this->options;
			$bans = explode( ',', $opts['bans']['ids'] );
			$pt_slug = explode( ',', $opts['bans']['templates'] );
			if ( in_array( 61383, $anc ) || in_array( $page_id, $bans ) ) :
				return $output;
			elseif ( in_array( get_page_template_slug( $page_id ), $pt_slug ) ) :
				return $output;
			endif;
		endif;
		$args = [
			'post_type' => 'promos',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'order' => 'ASC'
		];
		$t = time();
		$now = getdate($t);
		if ( !empty( $_GET['testtime'] ) ) :
			$tt = explode( '-', $_GET['testtime'] );
			$offset = get_option( 'gmt_offset' ) * 3600;
			$now = getdate( mktime( $tt[0], $tt[1], 0, $tt[2], $tt[3], $tt[4] ) + $offset );
			$args['post_status'] = [ 'publish', 'future' ];
			$args['date_query'] = [
				[
					'before' => [
						'year' => $now['year'],
						'month' => $now['mon'],
						'day' => $now['mday']
					],
					'inclusive' => true
				]
			];
		endif;
		$args['meta_query'] = [
			[
				'key'     => 'hpm_promos_end_time',
				'value'   => $now[0],
				'compare' => '>=',
			]
		];
		$promos = new WP_Query( $args );
		if ( $promos->have_posts() ) :
			while ( $promos->have_posts() ) :
				$promos->the_post();
				$meta = get_post_meta( get_the_ID(), 'hpm_promos_meta', true );
				if ( empty( $meta ) ) :
					continue;
				endif;
				if ( $meta['location'] == 'homepage' && ! $wp_global->is_home ) :
					continue;
				endif;
				$content = do_shortcode( get_the_content(), false );
				$content_esc = str_replace( "'", "\'", $content );
				$content_esc = preg_replace( "/\r|\n|\t/", "", $content_esc );
				if ( $meta['type'] == 'sidebar' ) :
					preg_match( '/<script.+>(.+)?<\/script>/', $content, $match );
					if ( !empty( $match[0] ) ) :
						echo $content;
						continue;
					endif;
					$sizing = [];
					if ( !empty( $meta['options']['sidebar']['mobile'] ) ) :
						$sizing[] = "if ( wide <= 480 ) { var image = '".$meta['options']['sidebar']['mobile']."'; }";
					endif;
					if ( !empty( $meta['options']['sidebar']['tablet'] ) ) :
						$sizing[] = "if ( wide > 480 && wide <= 800 ) { var image = '".$meta['options']['sidebar']['tablet']."'; }";
					endif;
					if ( !empty( $meta['options']['sidebar']['desktop'] ) ) :
						$sizing[] = "if ( wide > 800 ) { var image = '".$meta['options']['sidebar']['desktop']."'; }";
					endif;
					if ( !empty( $sizing ) ) :
						$output .= implode( ' else ', $sizing );
					endif;
					$content_esc = str_replace( "[[image]]", "'+image+'", $content_esc  );
					if ( $wp_global->is_home || ( !empty( $page_id ) && get_page_template_slug( $page_id ) == 'page-main-categories.php' ) ) :
						$output .= "if ( document.getElementById('top-schedule-wrap') !== null ) { document.getElementById('top-schedule-wrap').insertAdjacentHTML('afterbegin', '<div class=\"hpm-promo-mobile-hide\">".$content_esc."</div>'); }";
					else :
						$output .= "if ( document.querySelector( 'aside.column-right' ) !== null ) {document.querySelector('aside.column-right').insertAdjacentHTML('afterbegin', '<div class=\"hpm-promo-mobile-hide\">".$content_esc."</div>'); }";
					endif;
					$output .= "document.getElementById('primary').insertAdjacentHTML('afterbegin', '<div class=\"hpm-promo-mobile-show\">".$content_esc ."</div>');";
				elseif ( $meta['type'] == 'fullwidth' ) :
					if ( $fullwidth == 0 ) :
						$sizing = [];
						if ( !empty( $meta['options']['fullwidth']['mobile'] ) ) :
							$sizing[] = "if ( wide <= 480 ) { var image = '".$meta['options']['fullwidth']['mobile']."'; }";
						endif;
						if ( !empty( $meta['options']['fullwidth']['tablet'] ) ) :
							$sizing[] = "if ( wide > 480 && wide <= 800 ) { var image = '".$meta['options']['fullwidth']['tablet']."'; }";
						endif;
						if ( !empty( $meta['options']['fullwidth']['desktop'] ) ) :
							$sizing[] = "if ( wide > 800 ) { var image = '".$meta['options']['fullwidth']['desktop']."'; }";
						endif;
						if ( !empty( $sizing ) ) :
							$output .= implode( ' else ', $sizing );
						endif;
						$content_esc = str_replace( "[[image]]", "'+image+'", $content_esc  );
						$output .= "document.getElementById('primary').insertAdjacentHTML('afterbegin', '".$content_esc ."');";
						$fullwidth++;
					else :
						continue;
					endif;
				elseif ( $meta['type'] == 'lightbox' ) :
					if ( $lightbox == 0 ) :
						$output .= "
		var visited = getCookie('visited');";
						if ( preg_match( '/\[\[(link|image|text)\]\]/', $content_esc ) ) :
							$content_esc = str_replace(
								[ "[[link]]", "[[image]]", "[[text]]" ],
								[ "'+lblink+'", "'+lbimage+'", "'+lbtext+'" ],
								$content_esc );
							$output .= "
		var rand = Math.floor(Math.random() * 20);
		var lbtext, lblink, lbimage, lbox, primary;
		if ( rand > 9 ) {
			lbtext = '".$meta['options']['lightbox']['a']['text']."';
			lblink = '".$meta['options']['lightbox']['a']['link']."';
			lbimage = '".$meta['options']['lightbox']['a']['image']."';
		} else {
			lbtext = '".$meta['options']['lightbox']['b']['text']."';
			lblink = '".$meta['options']['lightbox']['b']['link']."';
			lbimage = '".$meta['options']['lightbox']['b']['image']."';
		}";
						endif;
						if ( !empty( $meta['options']['lightbox']['total'] ) ) :
							$remote = file_get_contents( $meta['options']['lightbox']['total'] );
							$total = json_decode( $remote, true );
							$content_esc = str_replace( "[[total]]", $total['total'], $content_esc );
						endif;
						$output .= "
		var lightBox = '".$content_esc."';";
						$output .= "
		if (visited === null) {
			setCookie('visited','true',4);";
						$output .= "document.getElementById('primary').insertAdjacentHTML('afterbegin', lightBox);";
						$output .= "
			var campaign = document.querySelectorAll('#campaign-splash, #campaign-close');
			for (i = 0; i < campaign.length; ++i) {
				campaign[i].addEventListener('click', function() {
					document.getElementById('campaign-splash').style.display = 'none';
				});
			}
		}
";
						$lightbox++;
					else :
						continue;
					endif;
				elseif ( $meta['type'] == 'emergency' ) :
					$content_esc = str_replace( [ '<p>', '</p>' ], [ '', '' ], $content_esc );
					$output .= "document.getElementById('fb-root').insertAdjacentHTML('afterend', '<div id=\"emergency\"><span class=\"fas fa-exclamation-circle\" aria-hidden=\"true\"></span> ".$content_esc."</div>');";
				elseif ( $meta['type'] == 'dont-miss' ) :
					$dont[] = str_replace( [ '<p>', '</p>' ], [ '', '' ], $content_esc );
				endif;
			endwhile;
		endif;
		if ( !empty( $dont ) ) :
			$output .= "document.getElementById('main').insertAdjacentHTML('beforebegin', '<div id=\"hpm-promo-bullets\"><h2>Don&#39;t Miss:</h2><ul>";
			foreach ( $dont as $d ) :
				$output .= "<li>" . $d . "</li>";
			endforeach;
			$output .= "</ul></div>');";
		endif;
		if ( !empty( $output ) ) :
			$output = "
<script>
	(function(){
		var wide = window.innerWidth;
		".$output."
		var topBanner = document.querySelectorAll('.top-banner');
		Array.from(topBanner).forEach((item) => {
			item.addEventListener('click', () => {
				var attr = item.id;
				if ( typeof attr !== typeof undefined && attr !== false) {
					ga('hpmprod.send', 'event', 'Top Banner', 'click', attr);
					ga('hpmRollupprod.send', 'event', 'Top Banner', 'click', attr);
					ga('hpmWebAmpprod.send', 'event', 'Top Banner', 'click', attr);
				}
			});
		});
		var lBox = document.querySelectorAll('#campaign-splash a');
		Array.from(lBox).forEach((item) => {
			item.addEventListener('click', () => {
				var campaign = document.querySelector('#campaign-splash').getAttribute('data-campaign');
				if ( typeof campaign !== typeof undefined && campaign !== false) {
					ga('hpmprod.send', 'event', 'Lightbox', 'click', campaign);
					ga('hpmRollupprod.send', 'event', 'Lightbox', 'click', campaign);
					ga('hpmWebAmpprod.send', 'event', 'Lightbox', 'click', campaign);
				}
			});
		});
	}());
	jQuery(document).ready(function($){
		$('#emergency').slideDown();
	});
</script>
<style>
	#emergency {
		display: none;
	}
	.hpm-promo-mobile-hide {
		display: none;
	}
	.hpm-promo-mobile-show {
		margin: 0 auto;
		float: left;
		width: 50%;
	}
	#hpm-promo-bullets {
		display: flex;
		justify-content: center;
		justify-items: center;
		align-items: center;
		align-content: center;
		margin: 0 0 1em 0;
		padding: 0;
		clear: both;
		width: 100%;
		flex-flow: row wrap;
	}
	#hpm-promo-bullets h2 {
		color: white;
		background-color: #cc0000;
		margin: 0;
		padding: 0.5em;
		width: 100%;
	}
	#hpm-promo-bullets ul {
		width: 100%;
		padding: 1em;
		margin: 0;
		background-color: white;
		list-style: none;
	}
	#hpm-promo-bullets ul li {
		margin: 0;
		padding: 0 0 0 0.5em;
	}
	#hpm-promo-bullets ul li:before {
		content: '\\2022';
		position: relative;
		left: -0.5em;
	}
	#main {
		clear: both;
	}
	@media screen and (min-width: 34em) {
		#hpm-promo-bullets {
			flex-flow: row nowrap;
		}
		#hpm-promo-bullets h2 {
			width: auto;
		}
		#hpm-promo-bullets ul {
			width: auto;
			flex-grow: 2;
		}
		#hpm-promo-bullets ul li {
			padding: 0 0 0 2em;
			display: inline;
		}
	}
	@media screen and (min-width: 52.5em) {
		.hpm-promo-mobile-hide,
		.column-right div.hpm-promo-mobile-hide {
			display: block;
			margin: 0 auto 1em;
			background-color: white;
		}
		.column-right div.hpm-promo-mobile-hide:nth-last-child(5) {
			margin-bottom: 1em;
		}
		.hpm-promo-mobile-show {
			display: none;
		}
		#hpm-promo-bullets {
			width: 100%;
			margin: 1em 0;
		}
	}
</style>";
		endif;
		return $output;
	}


	public function edit_columns( $columns ) {
		$columns = [
			'cb' => '<input type="checkbox" />',
			'title' => __( 'Name' ),
			'promo_type' => __( 'Type' ),
			'promo_location' => __( 'Location' ),
			'date' => __( 'Date' ),
			'promo_expiration' => __( 'Expiration' )
		];
		return $columns;
	}

	public function manage_columns( $column, $post_id ) {
		global $post;
		$endtime = get_post_meta( $post->ID, 'hpm_promos_end_time', true );
		$offset = get_option('gmt_offset')*3600;
		$t = $endtime + $offset;
		$meta = get_post_meta( $post->ID, 'hpm_promos_meta', true );
		switch( $column ) {
			case 'promo_type' :
				if ( empty( $meta['type'] ) ) :
					echo __( 'None' );
				else :
					echo __( ucwords( $meta['type'] ) );
				endif;
				break;
			case 'promo_location' :
				if ( empty( $meta['location'] ) ) :
					echo __( 'None' );
				else :
					echo __( ucwords( $meta['location'] ) );
				endif;
				break;
			case 'promo_expiration' :
				echo date( 'F j, Y, g:i A', $t );
				break;
			default :
				break;
		}
	}

	/**
	 * Creates the Settings menu in the Admin Dashboard
	 */
	public function create_menu() {
		add_submenu_page( 'edit.php?post_type=promos', 'HPM Promo Settings', 'Settings', 'manage_options', 'hpm-promos-settings', [ $this, 'settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Registers the settings group for HPM Podcasts
	 */
	public function register_settings() {
		register_setting( 'hpm-promos-settings-group', 'hpm_promos_settings' );
	}

	/**
	 * Creates the Settings menu in the Admin Dashboard
	 */
	public function settings_page() {
		$opts = $this->options; ?>
<div class="wrap">
	<h1><?php _e('Promo Banner Administration', 'hpm-promos' ); ?></h1>
	<?php settings_errors(); ?>
	<p><?php _e('The following section will help you administer your promo banners.', 'hpm-promos' ); ?></p>
	<form method="post" action="options.php">
		<?php settings_fields( 'hpm-promos-settings-group' ); ?>
		<?php do_settings_sections( 'hpm-promos-settings-group' ); ?>
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<div class="meta-box-sortables ui-sortable">
						<div class="postbox">
							<div class="handlediv" title="Click to toggle"><br></div>
							<h2 class="hndle"><span><?php _e('Exempted Pages', 'hpm-promos' ); ?></span></h2>
							<div class="inside">
								<p><?php _e('These fields will allow you to exempt certain pages and templates from displaying the promo banners. Please provide comma-separated lists of page/post IDs and page/post templates you would like to exempt.', 'hpm-promos' ); ?></p>
								<table class="form-table">
									<tr valign="top">
										<th scope="row"><label for="hpm_promos_settings[bans][ids]"><?php _e('Exempted Post IDs', 'hpm-promos' ); ?></label></th>
										<td><?php
											$editor_opts = [
												'editor_height' => 150,
												'media_buttons' => false,
												'teeny' => true
											];
											wp_editor( $opts['bans']['ids'], 'hpm_promos_settings[bans][ids]', $editor_opts );
										?></td>
									</tr>
									<tr valign="top">
										<th scope="row"><label for="hpm_promos_settings[bans][templates]"><?php _e('Exempted Post Templates', 'hpm-promos' ); ?></label></th>
										<td><?php
											$editor_opts = [
												'editor_height' => 150,
												'media_buttons' => false,
												'teeny' => true
											];
											wp_editor( $opts['bans']['templates'], 'hpm_promos_settings[bans][templates]', $editor_opts );
										?></td>
									</tr>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<br class="clear" />
		<?php submit_button(); ?>
	</form>
</div><?php
	}
}
new HPM_Promos();