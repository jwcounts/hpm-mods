<?php
/**
 * Series support for pages
 */
add_action( 'load-post.php', 'hpm_series_setup' );
add_action( 'load-post-new.php', 'hpm_series_setup' );
function hpm_series_setup() {
	add_action( 'add_meta_boxes', 'hpm_series_add_meta' );
	add_action( 'save_post', 'hpm_series_save_meta', 10, 2 );
}

$hpm_templates = [ 'default', 'page-wide.php', 'page-wide-w-post.php', 'page-wide-w-post-poll.php', 'page-series.php', 'page-series-tiles.php' ];
function hpm_series_add_meta() {
	global $wp_query;
	global $hpm_templates;
	$template = get_post_meta( get_the_ID(), '_wp_page_template', true );
	if ( in_array( $template, $hpm_templates ) ) :
		add_meta_box(
			'hpm-header-image-meta-class',
			esc_html__( 'Header Images', 'example' ),
			'hpm_header_image_meta_box',
			'page',
			'normal',
			'high'
		);
	endif;
	if ( preg_match( '/series/', $template ) || preg_match( '/wide\-w\-post/', $template ) ) :
		add_meta_box(
			'hpm-series-meta-class',
			esc_html__( 'Series Category', 'example' ),
			'hpm_series_meta_box',
			'page',
			'normal',
			'high'
		);
	endif;
}

function hpm_header_image_meta_box( $object, $box ) {
	wp_nonce_field( basename( __FILE__ ), 'hpm_series_class_nonce' );
	$hpm_page_options = get_post_meta( $object->ID, 'hpm_page_options', true );
	if ( empty( $hpm_page_options ) ) :
		$hpm_page_options = [
			'banner' => [
				'mobile' => '',
				'tablet' => '',
				'desktop' => ''
			]
		];
	endif;?>
	<h3><?PHP _e( "Banner Images", 'hpm-podcasts' ); ?></h3>
	<p>Use the buttons below to select your mobile, tablet, and desktop banner images</p>
	<?php
		$hpm_mobile_url = $hpm_tablet_url = $hpm_desktop_url = '';
		if ( !empty( $hpm_page_options['banner']['mobile'] ) ) :
			$hpm_mobile_temp = wp_get_attachment_image_src( $hpm_page_options['banner']['mobile'], 'medium' );
			$hpm_mobile_url = ' style="background-image: url('.$hpm_mobile_temp[0].')"';
		endif;
		if ( !empty( $hpm_page_options['banner']['tablet'] ) ) :
			$hpm_tablet_temp = wp_get_attachment_image_src( $hpm_page_options['banner']['tablet'], 'medium' );
			$hpm_tablet_url = ' style="background-image: url('.$hpm_tablet_temp[0].')"';
		endif;
		if ( !empty( $hpm_page_options['banner']['desktop'] ) ) :
			$hpm_desktop_temp = wp_get_attachment_image_src( $hpm_page_options['banner']['desktop'], 'medium' );
			$hpm_desktop_url = ' style="background-image: url('.$hpm_desktop_temp[0].')"';
		endif;
	?>
	<div class="hpm-page-banner-wrap">
		<div class="hpm-page-banner">
			<div class="hpm-page-banner-image" id="hpm-page-banner-mobile"<?php echo $hpm_mobile_url; ?>></div>
			<button class="hpm-page-banner-select button button-primary" data-show="mobile">Mobile</button>
			<input value="<?php echo $hpm_page_options['banner']['mobile']; ?>" type="hidden" id="hpm-page-banner-mobile-id" name="hpm-page-banner-mobile-id" />
		</div>
		<div class="hpm-page-banner">
			<div class="hpm-page-banner-image" id="hpm-page-banner-tablet"<?php echo $hpm_tablet_url; ?>></div>
			<button class="hpm-page-banner-select button button-primary" data-show="tablet">Tablet</button>
			<input value="<?php echo $hpm_page_options['banner']['tablet']; ?>" type="hidden" id="hpm-page-banner-tablet-id" name="hpm-page-banner-tablet-id" />
		</div>
		<div class="hpm-page-banner">
			<div class="hpm-page-banner-image" id="hpm-page-banner-desktop"<?php echo $hpm_desktop_url; ?>></div>
			<button class="hpm-page-banner-select button button-primary" data-show="desktop">Desktop</button>
			<input value="<?php echo $hpm_page_options['banner']['desktop']; ?>" type="hidden" id="hpm-page-banner-desktop-id" name="hpm-page-banner-desktop-id" />
		</div>
	</div>
	<script>
		function capitalizeFirstLetter(string) {
			return string[0].toUpperCase() + string.slice(1);
		}
		jQuery(document).ready(function($){
			$('.hpm-page-banner-select').click(function(e){
				e.preventDefault();
				var size = $(this).attr('data-show');
				var frame = wp.media({
					title: 'Choose Your ' + capitalizeFirstLetter(size) + ' Banner',
					library: {type: 'image'},
					multiple: false,
					button: {text: 'Set ' + capitalizeFirstLetter(size) + ' Banner'}
				});
				frame.on('select', function(){
					var sizes = frame.state().get('selection').first().attributes.sizes;
					if ( typeof sizes.medium !== 'undefined' ) {
						var thumb = sizes.medium.url;
					} else {
						var thumb = sizes.full.url;
					}
					var attachId = frame.state().get('selection').first().id;
					$('#hpm-page-banner-'+size).css( 'background-image', 'url('+thumb+')' )
					$('#hpm-page-banner-'+size+'-id').val(attachId);
				});
				frame.open();
			});
		});
	</script>
	<style>
		.hpm-page-banner-wrap {
			overflow: hidden;
		}
		.hpm-page-banner {
			width: 20%;
			padding: 1em;
			float: left;
			text-align: center;
		}
		.hpm-page-banner .hpm-page-banner-image {
			height: 0;
			width: 100%;
			padding-bottom: calc(100% / 1.5);
			background-repeat: no-repeat;
			background-size: cover;
			background-position: top center;
			border: 1px dotted #bfbfbf;
			margin-bottom: 0.5em;
		}
	</style>
<?php
}

function hpm_series_meta_box( $object, $box ) {
	wp_nonce_field( basename( __FILE__ ), 'hpm_series_class_nonce' );
	$hpm_series_cat = get_post_meta( $object->ID, 'hpm_series_cat', true );
	if ( empty( $hpm_series_cat ) ) :
		$hpm_series_cat = '';
		$top_story = "<p><em>Please select a Series category and click 'Save' or 'Update'</em></p>";
	else :
		$top = get_post_meta( $object->ID, 'hpm_series_top', true );
		$top_story = '<label for="hpm-series-top">Top Story:</label><select name="hpm-series-top" id="hpm-series-top"><option value="None">No Top Story</option>';
		$cat = new WP_query([
			'cat' => $hpm_series_cat,
			'post_status' => 'publish',
			'posts_per_page' => 25,
			'post_type' => 'post',
			'ignore_sticky_posts' => 1
		]);
		if ( $cat->have_posts() ) :
			while ( $cat->have_posts() ) : $cat->the_post();
				$top_story .= '<option value="'.get_the_ID().'" '.selected( $top, get_the_ID(), FALSE ).'>'.get_the_title().'</option>';
			endwhile;
		endif;
		wp_reset_query();
		$top_story .= '</select><br />';
	endif;

	$hpm_series_embeds = get_post_meta( $object->ID, 'hpm_series_embeds', true );
	if ( empty( $hpm_series_embeds ) ) :
		$hpm_series_embeds = [
			'bottom' => '',
			'twitter' => '',
			'facebook' => '',
			'order' => 'ASC'
		];
	endif; ?>
	<p><?PHP _e( "Select the category for this series", 'example' ); ?></p>
<?php
	wp_dropdown_categories([
		'show_option_all' => __("Select One"),
		'taxonomy'        => 'category',
		'name'            => 'hpm-series-cat',
		'orderby'         => 'name',
		'selected'        => $hpm_series_cat,
		'hierarchical'    => true,
		'depth'           => 5,
		'show_count'      => false,
		'hide_empty'      => false,
	]); ?>
	<p><?PHP _e( "What order would you like the articles to be displayed in?", 'example' ); ?></p>
	<label for="hpm-series-order"><?php _e( "Article Order:", 'example' ); ?></label>
	<select name="hpm-series-order" id="hpm-series-order">
		<option value="ASC"<?PHP if ('ASC' == $hpm_series_embeds['order']) { echo " selected"; } ?>>Oldest to Newest</option>
		<option value="DESC"<?PHP if ('DESC' == $hpm_series_embeds['order']) { echo " selected"; } ?>>Newest to Oldest</option>
	</select><br />
	<p><?PHP _e( "Which story should appear first?", 'example' ); ?></p>
	<?php echo $top_story; ?>
	<p>&nbsp;</p>
	<h4>Embeds</h4>
	<p>Any elements you include in this box will be placed below the article stream.</p>
	<label for="hpm-series-embeds"><?php _e( "Embeds:", 'example' ); ?></label><br />
	<textarea id="hpm-series-embeds" name="hpm-series-embeds" style="height: 200px; width: 100%;"><?php echo $hpm_series_embeds['bottom']; ?></textarea>

	<p>Twitter embeds for sidebar</p>
	<label for="hpm-series-embeds-twitter"><?php _e( "Twitter Embeds:", 'example' ); ?></label><br />
	<textarea id="hpm-series-embeds-twitter" name="hpm-series-embeds-twitter" style="height: 200px; width: 100%;"><?php echo $hpm_series_embeds['twitter']; ?></textarea>

	<p>Facebook embeds for sidebar</p>
	<label for="hpm-series-embeds-facebook"><?php _e( "Facebook Embeds:", 'example' ); ?></label><br />
	<textarea id="hpm-series-embeds-facebook" name="hpm-series-embeds-facebook" style="height: 200px; width: 100%;"><?php echo $hpm_series_embeds['facebook']; ?></textarea>
<?php
}

function hpm_series_save_meta( $post_id, $post ) {
	global $hpm_templates;
	$template = get_post_meta( $post_id, '_wp_page_template', true );
	if ( $post->post_type == 'page' && in_array( $template, $hpm_templates ) ) :
		if ( !isset( $_POST['hpm_series_class_nonce'] ) || !wp_verify_nonce( $_POST['hpm_series_class_nonce'], basename( __FILE__ ) ) ) :
			return $post_id;
		endif;

		$post_type = get_post_type_object( $post->post_type );

		if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ) :
			return $post_id;
		endif;
		if ( preg_match( '/series/', $template ) || preg_match( '/wide\-w\-post/', $template ) ) :
			$hpm_series_embeds = [];
			$hpm_page_options = [
				'banner' => [
					'mobile' => '',
					'tablet' => '',
					'desktop' => ''
				]
			];

			$hpm_series_cat = ( isset( $_POST['hpm-series-cat'] ) ? sanitize_text_field( $_POST['hpm-series-cat'] ) : '' );
			$hpm_series_top = ( isset( $_POST['hpm-series-top'] ) ? sanitize_text_field( $_POST['hpm-series-top'] ) : '' );
			$hpm_series_embeds['bottom'] = ( isset( $_POST['hpm-series-embeds'] ) ? $_POST['hpm-series-embeds'] : '' );
			$hpm_series_embeds['twitter'] = ( isset( $_POST['hpm-series-embeds-twitter'] ) ? $_POST['hpm-series-embeds-twitter'] : '' );
			$hpm_series_embeds['facebook'] = ( isset( $_POST['hpm-series-embeds-facebook'] ) ? $_POST['hpm-series-embeds-facebook'] : '' );
			$hpm_series_embeds['order'] = ( isset( $_POST['hpm-series-order'] ) ? $_POST['hpm-series-order'] : 'ASC' );


			update_post_meta( $post_id, 'hpm_series_cat', $hpm_series_cat );
			update_post_meta( $post_id, 'hpm_series_top', $hpm_series_top );
			update_post_meta( $post_id, 'hpm_series_embeds', $hpm_series_embeds );
		endif;
		$hpm_page_options['banner']['mobile'] = ( isset( $_POST['hpm-page-banner-mobile-id'] ) ? $_POST['hpm-page-banner-mobile-id'] : '' );
		$hpm_page_options['banner']['tablet'] = ( isset( $_POST['hpm-page-banner-tablet-id'] ) ? $_POST['hpm-page-banner-tablet-id'] : '' );
		$hpm_page_options['banner']['desktop'] = ( isset( $_POST['hpm-page-banner-desktop-id'] ) ? $_POST['hpm-page-banner-desktop-id'] : '' );
		update_post_meta( $post_id, 'hpm_page_options', $hpm_page_options );
	endif;
}
function hpm_head_banners( $id ) {
	$postscript = get_post_meta( $id, 'postscript_meta', true );
	if ( !empty( $postscript ) && $postscript['class_body'] == 'support-ssac' ) :
		$page_head_class = ' screen-reader-text';
		echo '<div class="page-banner ssac-banner"><div><h1 class="page-title">' . get_the_title( $id ) . '</div></div>' .
			'<style>.ssac-banner { background-image: url("' . wp_get_attachment_url( get_post_meta( $id, '_thumbnail_id', true ) ) . '"); }</style>';
		return $page_head_class;
	endif;
	$options = get_post_meta( $id, 'hpm_page_options', true );
	$page_head_style = $page_head_class = '';
	$count = 0;
	if ( !empty( $options ) ) :
		foreach ( $options['banner'] as $op ) :
			if ( !empty( $op ) ) :
				$count++;
			endif;
		endforeach;
	endif;
	if ( $count > 1 ) :
		echo '<div class="page-banner"></div>';
		$page_head_class = ' screen-reader-text';
		foreach ( $options['banner'] as $bk => $bv ) :
			if ( !empty( $bv ) ) :
				if ( $bk == 'mobile' ) :
					$page_head_style .= ".page-banner { background-image: url(".wp_get_attachment_url( $bv )."); padding-bottom: calc(100%/1.5); }";
				elseif ( $bk == 'tablet' ) :
					$page_head_style .= " @media screen and (min-width: 34em) { .page-banner { background-image: url(".wp_get_attachment_url( $bv )."); padding-bottom: calc(100%/4); } }";
				elseif ( $bk == 'desktop' ) :
					$page_head_style .= " @media screen and (min-width: 52.5em) { .page-banner { background-image: url(".wp_get_attachment_url( $bv )."); padding-bottom: calc(100%/6); } }";
				endif;
			endif;
		endforeach;
	elseif ( $count == 1 ) :
		echo '<div class="page-banner"></div>';
		$page_head_class = ' screen-reader-text';
		foreach ( $options['banner'] as $bk => $bv ) :
			if ( !empty( $bv ) ) :
				$page_head_style .= ".page-banner { background-image: url(".wp_get_attachment_url( $bv )."); padding-bottom: calc(100%/6); }";
			endif;
		endforeach;
	endif;
	if ( !empty( $page_head_style ) ) :
		echo "<style>".$page_head_style."</style>";
	endif;
	return $page_head_class;
}