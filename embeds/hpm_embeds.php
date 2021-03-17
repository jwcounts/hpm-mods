<?php
/**
 * Allows for creating a podcast feed from any category, along with templating, caching, and uploading the media files to an external server
 */

class HPM_Embeds {
	public function __construct() {
		define( 'HPM_EMBEDS_PLUGIN_DIR', plugin_dir_path(__FILE__) );
		define( 'HPM_EMBEDS_PLUGIN_URL', plugin_dir_url(__FILE__) );
		add_action( 'plugins_loaded', [ $this, 'init' ] );
		add_action( 'init', [ $this, 'create_type' ] );
	}

	public function init() {
		// Add edit capabilities to selected roles
		add_action( 'admin_init', [ $this, 'add_role_caps' ], 999 );

		// Setup metadata for podcast feeds
		add_action( 'load-post.php', [ $this, 'meta_setup' ] );
		add_action( 'load-post-new.php', [ $this, 'meta_setup' ] );

		// Register page templates
		add_filter( 'single_template', [ $this, 'single_template' ] );

		// Register WP-REST API endpoints
		add_action( 'rest_api_init', function() {
			register_rest_route( 'hpm-embeds/v1', '/list', [
				'methods'  => 'GET',
				'callback' => [ $this, 'list' ]
			]);
		});
	}

	public function single_template( $single ) {
		global $post;
		if ( $post->post_type == "embeds" ) :
			return HPM_EMBEDS_PLUGIN_DIR . 'templates' . DIRECTORY_SEPARATOR . 'single-embeds.php';
		endif;
		return $single;
	}

	public function meta_setup() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta' ] );
		add_action( 'save_post', [ $this, 'save_meta' ], 10, 2 );
	}

	public function add_meta() {
		add_meta_box(
			'hpm-embeds-meta-class',
			esc_html__( 'Embed Metadata', 'hpm-embeds' ),
			[ $this, 'embeds_meta' ],
			'embeds',
			'normal',
			'core'
		);
	}

	/**
	 * Set up metadata for this embed: responsiveness and branding.
	 *
	 * @param $object
	 * @param $box
	 *
	 * @return mixed
	 */
	public function embeds_meta( $object, $box ) {
		wp_nonce_field( basename( __FILE__ ), 'hpm_embeds_class_nonce' );
		$hpm_embed = get_post_meta( $object->ID, 'hpm_embed', true );
		if ( empty( $hpm_embed ) ) :
			$hpm_embed = [
				'responsive' => 0,
				'branding' => 0
			];
		endif; ?>
		<p><strong><label for="hpm-embed-respond"><?PHP _e( "Is this a responsive embed? I.e. Do you want to use pym.js to dynamically manage the height of the iframe?", 'hpm-embeds' ); ?></label></strong> <select name="hpm-embed-respond" id="hpm-embed-respond">
				<option value="0"<?PHP selected( $hpm_embed['responsive'], 0, TRUE ); ?>><?PHP _e( "No", 'hpm-embeds' ); ?></option>
				<option value="1"<?PHP selected( $hpm_embed['responsive'], 1, TRUE ); ?>><?PHP _e( "Yes", 'hpm-embeds' ); ?></option>
			</select>
		</p>
		<p><strong><label for="hpm-embed-brand"><?PHP _e( "Do you want to display HPM's branding?", 'hpm-embeds' ); ?></label></strong> <select name="hpm-embed-brand" id="hpm-embed-brand">
				<option value="0"<?PHP selected( $hpm_embed['branding'], 0, TRUE ); ?>><?PHP _e( "No", 'hpm-embeds' ); ?></option>
				<option value="1"<?PHP selected( $hpm_embed['branding'], 1, TRUE ); ?>><?PHP _e( "Yes", 'hpm-embeds' ); ?></option>
			</select>
		</p>
		<h3>Embed Code</h3>
		<div><pre><code id="hpm-embed-pym" class="hpm-embed-codes"<?php echo ( $hpm_embed['responsive'] == 0 ? ' style="display: none;"' : '' ); ?>><?php
			echo htmlentities( "<div id=\"hpm-embed\"></div>\n<script type=\"text/javascript\" src=\"https://pym.nprapps.org/pym.v1.min.js\"></script>\n<script>var pymParent = new pym.Parent('hpm-embed', '" . get_the_permalink( $object->ID ) . "', {});</script>", ENT_QUOTES, 'UTF-8' );
			?></code><code id="hpm-embed-iframe" class="hpm-embed-codes"<?php echo ( $hpm_embed['responsive'] == 1 ? ' style="display: none;"' : '' ); ?>><?php
			echo htmlentities( "<p><iframe src=\"" . get_the_permalink( $object->ID ) . "\" width=\"100%\" height=\"500\" frameborder=\"0\" allowfullscreen></iframe></p>", ENT_QUOTES, 'UTF-8' );
			?></code></pre></div>
		<style>
			.postbox .inside pre {
				border-left: 3px solid #00458b;
				font-size: 1rem;
				line-height: 1.4;
				margin: 0 0 24px;
				max-width: 100%;
				overflow: auto;
				padding: 24px;
				width: 100%;
				box-sizing: border-box;
			}
			.postbox .inside code {
				margin: 0;
				padding: 0;
			}
			code, pre {
				background-color: #eee;
			}
		</style>
		<script>
			jQuery(document).ready(function($){
				$(document).on('change', '#hpm-embed-respond', function(){
					$('.hpm-embed-codes').hide();
					var select = $('#hpm-embed-respond').children('option:selected').val();
					if ( select == 0 ) {
						$('#hpm-embed-iframe').show();
					} else {
						$('#hpm-embed-pym').show();
					}
				});
			});
		</script>
	<?php
	}

	/**
	 * Save the above metadata in postmeta
	 *
	 * @param $post_id
	 * @param $post
	 *
	 * @return mixed
	 */
	public function save_meta( $post_id, $post ) {
		$post_type = get_post_type_object( $post->post_type );
		if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ) :
			return $post_id;
		endif;
		if ( $post->post_type == 'embeds' ) :
			if ( empty( $_POST['hpm_embeds_class_nonce'] ) || !wp_verify_nonce( $_POST['hpm_embeds_class_nonce'], basename( __FILE__ ) ) ) :
				return $post_id;
			endif;
			$hpm_embed = [
				'responsive' => $_POST['hpm-embed-respond'],
				'branding' => $_POST['hpm-embed-brand']
			];

			update_post_meta( $post_id, 'hpm_embed', $hpm_embed );
		endif;
	}

	/**
	 * Create custom post type to house our embeds
	 */
	public function create_type() {
		register_post_type( 'embeds', [
			'labels' => [
				'name' => __( 'Embeds' ),
				'singular_name' => __( 'Embed' ),
				'menu_name' => __( 'Embeds' ),
				'add_new_item' => __( 'Add New Embed' ),
				'edit_item' => __( 'Edit Embed' ),
				'new_item' => __( 'New Embed' ),
				'view_item' => __( 'View Embed' ),
				'search_items' => __( 'Search Embeds' ),
				'not_found' => __( 'Embed Not Found' ),
				'not_found_in_trash' => __( 'Embed not found in trash' )
			],
			'description' => 'Embeds for posting on our site or others',
			'public' => true,
			'menu_position' => 20,
			'menu_icon' => 'dashicons-media-code',
			'has_archive' => false,
			'rewrite' => [
				'slug' => __( 'embeds' ),
				'with_front' => false,
				'feeds' => false,
				'pages' => true
			],
			'supports' => [ 'title', 'editor', 'thumbnail', 'author' ],
			'taxonomies' => [],
			'capability_type' => [ 'hpm_embed', 'hpm_embeds' ],
			'map_meta_cap' => true,
			'show_in_graphql' => true,
			'graphql_single_name' => 'Embed',
			'graphql_plural_name' => 'Embeds'
		]);
	}

	/**
	 * Add capabilities to the selected roles (default is admin/editor)
	 */
	public function add_role_caps() {
		$roles = [ 'editor', 'administrator' ];
		foreach( $roles as $the_role ) :
			$role = get_role( $the_role );
			$role->add_cap( 'read' );
			$role->add_cap( 'read_hpm_embed');
			$role->add_cap( 'read_private_hpm_embeds' );
			$role->add_cap( 'edit_hpm_embed' );
			$role->add_cap( 'edit_hpm_embeds' );
			$role->add_cap( 'edit_others_hpm_embeds' );
			$role->add_cap( 'edit_published_hpm_embeds' );
			$role->add_cap( 'publish_hpm_embeds' );
			$role->add_cap( 'delete_others_hpm_embeds' );
			$role->add_cap( 'delete_private_hpm_embeds' );
			$role->add_cap( 'delete_published_hpm_embeds' );
		endforeach;
	}

	/**
	 * Return list of active podcast feeds with feed URLs and most recent files
	 *
	 * @return string
	 */
	public function list( WP_REST_Request $request = null ) {
		$list = get_transient( 'hpm_embeds_list' );
		if ( !empty( $list ) ) :
			return rest_ensure_response( [ 'code' => 'rest_api_success', 'message' => esc_html__( 'Embeds list', 'hpm-embeds' ), 'data' => [ 'list' => $list, 'status' =>	200 ] ] );
		endif;
		$protocol = 'https://';
		$_SERVER['HTTPS'] = 'on';
		$list = [];

		$embeds = new WP_Query([
			'post_type' => 'embeds',
			'post_status' => 'publish',
			'posts_per_page' => -1,
		]);
		if ( $embeds->have_posts() ) :
			while ( $embeds->have_posts() ) :
				$temp = [
					'name' => '',
					'responsive' => '',
					'branded' => '',
					'code' => ''
				];
				$embeds->the_post();
				$id = get_the_ID();
				$embed = get_post_meta( $id, 'hpm_embed', true );
				$temp['name'] = get_the_title();
				$temp['responsive'] = $embed['responsive'];
				$temp['branded'] = $embed['branding'];
				$code = '';
				if ( $embed['responsive'] ) :
					$code .= '<div id="hpm-embed"></div><script type="text/javascript" src="https://pym.nprapps.org/pym.v1.min.js"></script><script>var pymParent = new pym.Parent(\'hpm-embed\', \'' . get_the_permalink() . '\', {});</script>';
				else :
					$code .= '<p><iframe src="' . get_the_permalink() . '" width="100%" height="500" frameborder="0" allowfullscreen></iframe>"></iframe></p>';
				endif;
				$temp['code'] = $code;
				$list[] = $temp;
			endwhile;
		endif;
		set_transient( 'hpm_embeds_list', $list, 86400 );
		return rest_ensure_response( [ 'code' => 'rest_api_success', 'message' => esc_html__( 'Embeds list', 'hpm-embeds' ), 'data' => [ 'list' => $list, 'status' => 200 ] ] );
	}
}

new HPM_Embeds();