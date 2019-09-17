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
			</header><!-- .page-header -->
			<section id="search-results">
			<?php
			while ( have_posts() ) : the_post();
				$staff = get_post_meta( get_the_ID(), 'hpm_staff_meta', true );
				$author_bio = get_the_content();
				if ( $author_bio == "<p>Biography pending.</p>" || $author_bio == "<p>Biography pending</p>" || $author_bio == '' ) :
					$bio_link = false;
				else :
					$bio_link = true;
				endif; ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<?php
					if ( has_post_thumbnail() ) : ?>
						<div class="staff-thumb">
					<?php echo ( $bio_link ? '<a href="' . get_the_permalink() . '" aria-hidden="true">' : ''); ?>
							<img src="<?php the_post_thumbnail_url('post-thumbnail'); ?>" alt="<?php echo get_the_title() . ': ' . $staff['title'] ?>" />
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
								<a href="mailto:<?php echo $staff['email']; ?>" target="_blank"><span class="fa fa-envelope" aria-hidden="true"></span></a>
							</div>
			<?php
					endif;
					if ( !empty( $staff['twitter'] ) ) : ?>
							<div class="social-icon">
								<a href="<?php echo $staff['twitter']; ?>" target="_blank"><span class="fa fa-twitter" aria-hidden="true"></span></a>
							</div>
			<?php
					endif;
					if (!empty( $staff['facebook'] ) ) : ?>
							<div class="social-icon">
								<a href="<?php echo $staff['facebook']; ?>" target="_blank"><span class="fa fa-facebook" aria-hidden="true"></span></a>
							</div>
			<?php
					endif; ?>

						</header><!-- .entry-header -->
						<div class="entry-summary">
							<p><?php echo $staff['title']; ?></p>
						</div><!-- .entry-summary -->
					</div>
				</article><!-- #post-## -->
			<?php
			endwhile;

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
		</main><!-- .site-main -->
	</section><!-- .content-area -->
	<script>
		jQuery(document).ready(function($){
			$('select#hpm-staff-cat').on('change', function() {
				if (this.value == 0) {
					window.location.href = '/staff/';
				} else {
					window.location.href = "/staff-category/"+this.value+"/";
				}
			});
		});
	</script>
<?php get_footer(); ?>