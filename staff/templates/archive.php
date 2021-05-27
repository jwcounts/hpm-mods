<?php
/**
 * @package WordPress
 * @subpackage HPMv2
 * @since HPMv2 1.0
 */
get_header(); ?>
	<section id="primary" class="content-area">
		<main id="main" class="site-main" role="main">
		<?php if ( have_posts() ) : ?>

			<header class="page-header">
				<h1 class="page-title"><?php echo ( is_tax() ? 'Staff: ' . $wp_query->queried_object->name  : 'Staff Directory' ) ?></h1>
				<?php wp_dropdown_categories([
						'show_option_all'	=> __("Select Category"),
						'taxonomy'			=> 'staff_category',
						'name'				=> 'hpm-staff-cat',
						'orderby'			=> 'name',
						'selected'			=> ( is_tax() ? $wp_query->queried_object->slug : '' ),
						'hierarchical'		=> true,
						'depth'				=> 3,
						'show_count'		=> false,
						'hide_empty'		=> true,
						'value_field'		=> 'slug'
					]); ?>
			</header>
			<section id="search-results">
		<?php
			hpm_staff_echo( $wp_query );

			// Previous/next page navigation.
			the_posts_pagination( [
				'prev_text' => __( '&lt;', 'hpmv2' ),
				'next_text' => __( '&gt;', 'hpmv2' ),
				'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'hpmv2' ) . ' </span>',
			 ] );

		// If no content, include the "No posts found" template.
		else :
			get_template_part( 'content', 'none' );

		endif;
		?>
			</section>
		</main>
	</section>
	<script>
		document.addEventListener('DOMContentLoaded', () => {
			var staffCat = document.querySelector('select#hpm-staff-cat')
			staffCat.addEventListener('change', (e) => {
				if (staffCat.value == 0) {
					window.location.href = '/staff/';
				} else {
					window.location.href = "/staff-category/"+staffCat.value+"/";
				}
			});
		});
	</script>
<?php get_footer(); ?>