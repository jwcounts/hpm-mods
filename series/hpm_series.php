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

	function hpm_series_add_meta() {
		add_meta_box(
			'hpm-series-meta-class',
			esc_html__( 'Series Category', 'example' ),
			'hpm_series_meta_box',
			'page',
			'normal',
			'core'
		);
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
	<?php }

	function hpm_series_save_meta( $post_id, $post ) {
		if ( $post->post_type == 'page' ) :
			if ( !isset( $_POST['hpm_series_class_nonce'] ) || !wp_verify_nonce( $_POST['hpm_series_class_nonce'], basename( __FILE__ ) ) ) :
				return $post_id;
			endif;

			$post_type = get_post_type_object( $post->post_type );

			if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ) :
				return $post_id;
			endif;

			$hpm_series_embeds = [];

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
	}