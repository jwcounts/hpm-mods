<?php
/**
 * Support for Staff Directory, departments/categories, and staff bios
 */
add_action( 'init', 'create_staff_post' );
add_action( 'init', 'create_staff_taxonomies' );
function create_staff_post() {
	register_post_type( 'staff', [
		'labels' => [
			'name' => __( 'Staff' ),
			'singular_name' => __( 'Staff' ),
			'menu_name' => __( 'Staff' ),
			'add_new_item' => __( 'Add New Staff' ),
			'edit_item' => __( 'Edit Staff' ),
			'new_item' => __( 'New Staff' ),
			'view_item' => __( 'View Staff' ),
			'search_items' => __( 'Search Staff' ),
			'not_found' => __( 'Staff Not Found' ),
			'not_found_in_trash' => __( 'Staff not found in trash' )
		],
		'description' => 'Staff Members of Houston Public Media',
		'public' => true,
		'menu_position' => 20,
		'menu_icon' => 'dashicons-groups',
		'has_archive' => true,
		'rewrite' => [
			'slug' => __( 'staff' ),
			'with_front' => false,
			'feeds' => false,
			'pages' => true
		],
		'supports' => [ 'title', 'editor', 'thumbnail', 'author' ],
		'taxonomies' => [ 'staff_category' ],
		'capability_type' => [ 'hpm_staffer','hpm_staffers' ],
		'map_meta_cap' => true,
		'show_in_graphql' => true,
		'graphql_single_name' => 'Staff',
		'graphql_plural_name' => 'Staff'
	]);
}

function create_staff_taxonomies() {
	register_taxonomy('staff_category', 'staff', [
		'hierarchical' => true,
		'labels' => [
			'name' => _x( 'Staff Category', 'taxonomy general name' ),
			'singular_name' => _x( 'staff-category', 'taxonomy singular name' ),
			'search_items' =>  __( 'Search Staff Categories' ),
			'all_items' => __( 'All Staff Categories' ),
			'parent_item' => __( 'Parent Staff Category' ),
			'parent_item_colon' => __( 'Parent Staff Category:' ),
			'edit_item' => __( 'Edit Staff Category' ),
			'update_item' => __( 'Update Staff Category' ),
			'add_new_item' => __( 'Add New Staff Category' ),
			'new_item_name' => __( 'New Staff Category Name' ),
			'menu_name' => __( 'Staff Categories' )
		],
		'public' => true,
		'rewrite' => [
			'slug' => 'staff-category',
			'with_front' => false,
			'hierarchical' => true
		]
	]);
}

add_action( 'admin_init', 'hpm_staff_add_role_caps', 999 );
function hpm_staff_add_role_caps() {
	// Add the roles you'd like to administer the custom post types
	$roles = [ 'editor', 'administrator', 'author' ];

	// Loop through each role and assign capabilities
	foreach($roles as $the_role) :
		$role = get_role($the_role);
        if ( $the_role !== 'author' ) :
            $role->add_cap( 'read' );
            $role->add_cap( 'read_hpm_staffer');
	        $role->add_cap( 'add_hpm_staffer' );
	        $role->add_cap( 'add_hpm_staffers' );
            $role->add_cap( 'read_private_hpm_staffers' );
            $role->add_cap( 'edit_hpm_staffer' );
            $role->add_cap( 'edit_hpm_staffers' );
            $role->add_cap( 'edit_others_hpm_staffers' );
            $role->add_cap( 'edit_published_hpm_staffers' );
            $role->add_cap( 'publish_hpm_staffers' );
            $role->add_cap( 'delete_others_hpm_staffers' );
            $role->add_cap( 'delete_private_hpm_staffers' );
            $role->add_cap( 'delete_published_hpm_staffers' );
        else :
	        $role->add_cap( 'read' );
	        $role->add_cap( 'read_hpm_staffer');
	        $role->remove_cap( 'add_hpm_staffer' );
	        $role->remove_cap( 'add_hpm_staffers' );
	        $role->remove_cap( 'read_private_hpm_staffers' );
	        $role->add_cap( 'edit_hpm_staffer' );
	        $role->add_cap( 'edit_hpm_staffers' );
	        $role->remove_cap( 'edit_others_hpm_staffers' );
	        $role->remove_cap( 'edit_published_hpm_staffers' );
	        $role->remove_cap( 'publish_hpm_staffers' );
	        $role->remove_cap( 'delete_others_hpm_staffers' );
	        $role->remove_cap( 'delete_private_hpm_staffers' );
	        $role->remove_cap( 'delete_published_hpm_staffers' );
        endif;
	endforeach;
}

add_action( 'load-post.php', 'hpm_staff_setup' );
add_action( 'load-post-new.php', 'hpm_staff_setup' );
function hpm_staff_setup() {
	add_action( 'add_meta_boxes', 'hpm_staff_add_meta' );
	add_action( 'save_post', 'hpm_staff_save_meta', 10, 2 );
}

function hpm_staff_add_meta() {
	add_meta_box(
		'hpm-staff-meta-class',
		esc_html__( 'Title, Social Media, Etc.', 'example' ),
		'hpm_staff_meta_box',
		'staff',
		'normal',
		'core'
	);
}

function hpm_staff_meta_box( $object, $box ) {
	wp_nonce_field( basename( __FILE__ ), 'hpm_staff_class_nonce' );

	$hpm_staff_meta = get_post_meta( $object->ID, 'hpm_staff_meta', true );
	if ( empty( $hpm_staff_meta ) ) :
		$hpm_staff_meta = [ 'title' => '', 'email' => '', 'twitter' => '', 'facebook' => '', 'linkedin' => '', 'phone' => '' ];
	endif;

	$hpm_staff_alpha = get_post_meta( $object->ID, 'hpm_staff_alpha', true );
	if ( empty( $hpm_staff_alpha ) ) :
		$hpm_staff_alpha = [ '', '' ];
	else :
		$hpm_staff_alpha = explode( '|', $hpm_staff_alpha );
	endif;

	$hpm_staff_authid = get_post_meta( $object->ID, 'hpm_staff_authid', true ); ?>
	<p><?PHP _e( "Enter the staff member's details below", 'example' ); ?></p>
	<ul>
		<li><label for="hpm-staff-name-first"><?php _e( "First Name: ", 'example' ); ?></label> <input type="text" id="hpm-staff-name-first" name="hpm-staff-name-first" value="<?PHP echo $hpm_staff_alpha[1]; ?>" placeholder="Kenny" style="width: 60%;" /></li>
		<li><label for="hpm-staff-name-last"><?php _e( "Last Name: ", 'example' ); ?></label> <input type="text" id="hpm-staff-name-last" name="hpm-staff-name-last" value="<?PHP echo $hpm_staff_alpha[0]; ?>" placeholder="Loggins" style="width: 60%;" /></li>
		<li><label for="hpm-staff-title"><?php _e( "Job Title: ", 'example' ); ?></label> <input type="text" id="hpm-staff-title" name="hpm-staff-title" value="<?PHP echo $hpm_staff_meta['title']; ?>" placeholder="Top Gun" style="width: 60%;" /></li>
		<li><label for="hpm-staff-email"><?php _e( "Email: ", 'example' ); ?></label> <input type="text" id="hpm-staff-email" name="hpm-staff-email" value="<?PHP echo $hpm_staff_meta['email']; ?>" placeholder="highway@thedanger.zone" style="width: 60%;" /></li>
		<li><label for="hpm-staff-fb"><?php _e( "Facebook: ", 'example' ); ?></label> <input type="text" id="hpm-staff-fb" name="hpm-staff-fb" value="<?PHP echo $hpm_staff_meta['facebook']; ?>" placeholder="https://facebook.com/first.last" style="width: 60%;" /></li>
		<li><label for="hpm-staff-twitter"><?php _e( "Twitter: ", 'example' ); ?></label> <input type="text" id="hpm-staff-twitter" name="hpm-staff-twitter" value="<?PHP echo $hpm_staff_meta['twitter']; ?>" placeholder="https://twitter.com/houpubmedia" style="width: 60%;" /></li>
			<li><label for="hpm-staff-linkedin"><?php _e( "LinkedIn: ", 'example' ); ?></label> <input type="text" id="hpm-staff-linkedin" name="hpm-staff-linkedin" value="<?PHP echo $hpm_staff_meta['linkedin']; ?>" placeholder="https://linkedin.com/in/example" style="width: 60%;" /></li>
		<li><label for="hpm-staff-phone"><?php _e( "Phone: ", 'example' ); ?></label> <input type="text" id="hpm-staff-phone" name="hpm-staff-phone" value="<?PHP echo $hpm_staff_meta['phone']; ?>" placeholder="(713) 555-5555" style="width: 60%;" /></li>
		<li><label for="hpm-staff-author"><?php _e( "Author ID:", 'example' ); ?></label> <?php
			wp_dropdown_users([
				'show_option_none' => 'None',
				'show' => 'display_name',
				'echo' => true,
				'selected' => $hpm_staff_authid,
				'include_selected' => true,
				'name' => 'hpm-staff-author',
				'id' => 'hpm-staff-author'
			]); ?></li>
	</ul>
<?php }

function hpm_staff_save_meta( $post_id, $post ) {
	if ($post->post_type == 'staff') :
		/* Verify the nonce before proceeding. */
		if ( !isset( $_POST['hpm_staff_class_nonce'] ) || !wp_verify_nonce( $_POST['hpm_staff_class_nonce'], basename( __FILE__ ) ) )
		return $post_id;

		/* Get the post type object. */
		$post_type = get_post_type_object( $post->post_type );

		/* Check if the current user has permission to edit the post. */
		if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
			return $post_id;

		/* Get the posted data and sanitize it for use as an HTML class. */
		$hpm_staff = [
			'title'		=> ( isset( $_POST['hpm-staff-title'] ) ? sanitize_text_field( $_POST['hpm-staff-title'] ) : '' ),
			'email'		=> ( isset( $_POST['hpm-staff-email'] ) ? sanitize_text_field( $_POST['hpm-staff-email'] ) : '' ),
			'facebook'	=> ( isset( $_POST['hpm-staff-fb'] ) ? sanitize_text_field( $_POST['hpm-staff-fb'] ) : '' ),
			'twitter'	=> ( isset( $_POST['hpm-staff-twitter'] ) ? sanitize_text_field( $_POST['hpm-staff-twitter'] ) : '' ),
			'linkedin'	=> ( isset( $_POST['hpm-staff-linkedin'] ) ? sanitize_text_field( $_POST['hpm-staff-linkedin'] ) : '' ),
			'phone'	=> ( isset( $_POST['hpm-staff-phone'] ) ? sanitize_text_field( $_POST['hpm-staff-phone'] ) : '')
		];
		$hpm_first = ( isset( $_POST['hpm-staff-name-first'] ) ? sanitize_text_field( $_POST['hpm-staff-name-first'] ) : '' );
		$hpm_last = ( isset( $_POST['hpm-staff-name-last'] ) ? sanitize_text_field( $_POST['hpm-staff-name-last'] ) : '' );
		$hpm_staff_alpha = $hpm_last."|".$hpm_first;
		$hpm_staff_authid = ( isset( $_POST['hpm-staff-author'] ) ? sanitize_text_field( $_POST['hpm-staff-author'] ) : '' );

		update_post_meta( $post_id, 'hpm_staff_authid', $hpm_staff_authid );
		update_post_meta( $post_id, 'hpm_staff_meta', $hpm_staff );
		update_post_meta( $post_id, 'hpm_staff_alpha', $hpm_staff_alpha );
	endif;
}

add_filter( 'manage_edit-staff_columns', 'hpm_edit_staff_columns' ) ;
function hpm_edit_staff_columns( $columns ) {
	$columns = [
		'cb' => '<input type="checkbox" />',
		'title' => __( 'Name' ),
		'job_title' => __( 'Title' ),
		'staff_category' => __( 'Departments' ),
		'authorship' => __( 'Author?' )
	];
	return $columns;
}

add_action( 'manage_staff_posts_custom_column', 'hpm_manage_staff_columns', 10, 2 );
function hpm_manage_staff_columns( $column, $post_id ) {
	global $post;
	$staff_meta = get_post_meta( $post_id, 'hpm_staff_meta', true );
	$staff_authid = get_post_meta( $post_id, 'hpm_staff_authid', true );
	switch( $column ) {
		case 'job_title' :
			if ( empty( $staff_meta['title'] ) ) :
				echo __( 'None' );
			else :
				echo __( $staff_meta['title'] );
			endif;
			break;
		case 'authorship' :
			if ( empty( $staff_authid ) || $staff_authid < 0 ) :
				echo __( 'No' );
			else :
				echo __( 'Yes' );
			endif;
			break;
		case 'staff_category' :
			$terms = get_the_terms( $post_id, 'staff_category' );
			if ( !empty( $terms ) ) :
				$out = [];
				foreach ( $terms as $term ) :
					$out[] = sprintf( '<a href="%s">%s</a>',
						esc_url( add_query_arg( [ 'post_type' => $post->post_type, 'staff_category' => $term->slug ], 'edit.php' ) ),
						esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'staff_category', 'display' ) )
					);
				endforeach;
				echo join( ', ', $out );
			else :
				_e( 'No Department Affiliations' );
			endif;
			break;
		default :
			break;
	}
}

add_action('restrict_manage_posts', 'hpm_filter_post_type_by_taxonomy');
function hpm_filter_post_type_by_taxonomy() {
	global $typenow;
	$taxonomy  = 'staff_category';
	if ( $typenow == 'staff' ) :
		$selected      = isset( $_GET[ $taxonomy ] ) ? $_GET[$taxonomy ] : '';
		$info_taxonomy = get_taxonomy( $taxonomy );
		wp_dropdown_categories([
			'show_option_all' => __("Show All {$info_taxonomy->label}"),
			'taxonomy'        => $taxonomy,
			'name'            => $taxonomy,
			'orderby'         => 'name',
			'selected'        => $selected,
			'hierarchical'    => true,
			'depth'           => 3,
			'show_count'      => true,
			'hide_empty'      => true,
		]);
	endif;
}

add_filter('parse_query', 'hpm_convert_id_to_term_in_query');
function hpm_convert_id_to_term_in_query( $query ) {
	global $pagenow;
	$taxonomy  = 'staff_category';
	$q_vars    = &$query->query_vars;
	if ( $pagenow == 'edit.php' && isset( $q_vars['post_type'] ) && $q_vars['post_type'] == 'staff' && isset( $q_vars[ $taxonomy ] ) && is_numeric( $q_vars[ $taxonomy ] ) && $q_vars[ $taxonomy ] != 0 ) {
		$term = get_term_by('id', $q_vars[ $taxonomy ], $taxonomy );
		$q_vars[ $taxonomy ] = $term->slug;
	}
}

/*
 * Changes number of posts loaded when viewing the staff directory
 */
function staff_meta_query( $query ) {
	if (
		$query->is_archive() &&
		$query->is_main_query() &&
		(
			$query->get( 'post_type' ) == 'staff' ||
			!empty( $query->get( 'staff_category' ) )
		)
	) :
		$query->set( 'meta_query', [ 'hpm_staff_alpha' => [ 'key' => 'hpm_staff_alpha' ] ] );
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', 'ASC' );
		if ( !is_admin() ) :
			$query->set( 'posts_per_page', 30 );
		endif;
		if ( !is_admin() && empty( $query->get( 'staff_category' ) ) ) :
			$query->set( 'tax_query', [[
				'taxonomy' => 'staff_category',
				'field' => 'slug',
				'terms' => [ 'department-leaders', 'executive-team' ],
				'operator' => 'NOT IN'
			]] );
		endif;
	endif;
}
add_action( 'pre_get_posts', 'staff_meta_query' );

function hpm_staff_single_template( $single ) {
	global $post;
	if ( $post->post_type == "staff" ) :
		if ( file_exists( get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'single-staff.php' ) ) :
			return get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'single-staff.php';
		else :
			return HPM_MODS_DIR . 'staff' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'single.php';
		endif;
	endif;
	return $single;
}
add_filter( 'single_template', 'hpm_staff_single_template' );

function hpm_staff_archive_template( $archive_template ) {
	global $post;
	if ( is_post_type_archive( 'staff' ) ) :
		if ( file_exists( get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'archive-staff.php' ) ) :
			return get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'archive-staff.php';
		else :
			return HPM_MODS_DIR . 'staff' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'archive.php';
		endif;
	endif;
	return $archive_template;
}
add_filter( 'archive_template', 'hpm_staff_archive_template' );

function hpm_staff_tax_template( $taxonomy_template ) {
	global $post;
	if ( is_tax( 'staff_category' ) ) :
		if ( file_exists( get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'taxonomy-staff_category.php' ) ) :
			return get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'taxonomy-staff_category.php';
		else :
			return HPM_MODS_DIR . 'staff' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'archive.php';
		endif;
	endif;
	return $taxonomy_template;
}
add_filter( 'taxonomy_template', 'hpm_staff_tax_template' );

function hpm_staff_out() {
	global $wp_query;
	$staff = get_post_meta( get_the_ID(), 'hpm_staff_meta', true );
	$author_bio = get_the_content();
	if ( $author_bio == "<p>Biography pending.</p>" || $author_bio == "<p>Biography pending</p>" || $author_bio == '' ) :
		$bio_link = false;
	else :
		$bio_link = true;
	endif; ?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<?php	if ( has_post_thumbnail() ) : ?>
			<div class="staff-thumb">
		<?php echo ( $bio_link ? '<a href="' . get_the_permalink() . '" aria-hidden="true">' : ''); ?>
				<img src="<?php the_post_thumbnail_url( 'medium' ); ?>" alt="<?php echo get_the_title() . ': ' . $staff['title'] ?>" />
		<?php echo ( $bio_link ? '</a>' : '' ); ?>
			</div>
<?php
		endif; ?>
		<div class="staff-wrap">
			<header class="entry-header">
				<h2 class="entry-title"><?php echo ( $bio_link ? '<a href="' . get_the_permalink() . '" rel="bookmark">' . get_the_title() . '</a>' : get_the_title() ); ?></h2>
		<?php
		if ( !empty( $staff['email'] ) ) : ?>
				<div class="social-icon">
					<a href="mailto:<?php echo $staff['email']; ?>" target="_blank"><span class="fas fa-envelope" aria-hidden="true"></span></a>
				</div>
<?php	endif;
		if ( !empty( $staff['twitter'] ) ) : ?>
				<div class="social-icon">
					<a href="<?php echo $staff['twitter']; ?>" target="_blank"><span class="fab fa-twitter" aria-hidden="true"></span></a>
				</div>
<?php	endif;
		if (!empty( $staff['facebook'] ) ) : ?>
				<div class="social-icon">
					<a href="<?php echo $staff['facebook']; ?>" target="_blank"><span class="fab fa-facebook-f" aria-hidden="true"></span></a>
				</div>
<?php	endif;
		if (!empty( $staff['linkedin'] ) ) : ?>
				<div class="social-icon">
					<a href="<?php echo $staff['linkedin']; ?>" target="_blank"><span class="fab fa-linkedin-in" aria-hidden="true"></span></a>
				</div>
<?php	endif; ?>
			</header>
			<div class="entry-summary">
				<p><?php echo $staff['title']; ?></p>
			</div>
		</div>
	</article>
<?php
}

function hpm_staff_echo( $query ) {
	$main_query = $query;
	$cat = $main_query->get( 'staff_category' );
	$exempt = [ 'hosts', 'executive-team', 'department-leaders' ];
	if ( empty( $main_query->query['paged'] ) && empty( $cat ) ) :
		echo '<h2>Executive Team</h2>';
		$args = [
			'post_type' => 'staff',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'ignore_sticky_posts' => 1,
			'tax_query' => [[
				'taxonomy' => 'staff_category',
				'field' => 'slug',
				'terms' => [ 'executive-team' ]
			]],
			'meta_query' => [ 'hpm_staff_alpha' => [ 'key' => 'hpm_staff_alpha' ] ],
			'orderby' => 'meta_value',
			'order' => 'ASC'
		];
		$el = new WP_Query( $args );
		while ( $el->have_posts() ) : $el->the_post();
			hpm_staff_out();
		endwhile;
		echo '<h2 class="top-pad">Department Leaders</h2>';

		$args['tax_query'][0]['terms'] = [ 'department-leaders' ];
		$dh = new WP_Query($args);
		while ( $dh->have_posts() ) : $dh->the_post();
			hpm_staff_out();
		endwhile;
		echo '<h2 class="top-pad">HPM Staff</h2>';
	elseif ( !empty( $cat ) && !in_array( $cat, $exempt ) ) :
		$main_query->posts = hpm_staff_sort( $main_query->posts );
	endif;
	while ( $main_query->have_posts() ) :
		$main_query->the_post();
		hpm_staff_out();
	endwhile;
	wp_reset_query();
}

function hpm_staff_sort( $posts ) {
	$out = $first = [];
	$exempt = [ 'hosts', 'executive-team', 'department-leaders' ];
	foreach ( $posts as $p ) :
		$lead = false;
		$cat = get_terms( [ 'taxonomy' => 'staff_category', 'object_ids' => $p->ID ] );
		foreach ( $cat as $c ) :
			if ( in_array( $c->slug, $exempt ) ) :
				$lead = true;
			endif;
		endforeach;
		if ( $lead ) :
			$first[] = $p;
		else :
			$out[] = $p;
		endif;
	endforeach;
	return array_merge( $first, $out );
}