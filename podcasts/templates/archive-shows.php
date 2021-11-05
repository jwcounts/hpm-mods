<?php
/**
 * The template for displaying archive pages
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * If you'd like to further customize these archive views, you may create a
 * new template file for each one. For example, tag.php (Tag archives),
 * category.php (Category archives), author.php (Author archives), etc.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage Twenty_Fifteen
 * @since Twenty Fifteen 1.0
 */

get_header(); ?>
	<section id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

		<?php if ( have_posts() ) : ?>

			<header class="page-header">
				<h1 class="page-title">Local Shows</h1>
				<?php
					the_archive_description( '<div class="taxonomy-description">', '</div>' );
				?>
			</header><!-- .page-header -->
			<section class="archive">
			<?php
			// Start the loop.
			while ( have_posts() ) : the_post();
				get_template_part( 'content', 'shows' );
			endwhile;

			// Previous/next page navigation.
			the_posts_pagination( [
				'prev_text' => __( '&lt;', 'hpm-podcasts' ),
				'next_text' => __( '&gt;', 'hpm-podcasts' ),
				'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'hpm-podcasts' ) . ' </span>',
			 ] );

		// If no content, include the "No posts found" template.
		else :
			get_template_part( 'content', 'none' );

		endif;
		?>
			</section>
			<aside>
				<?php get_template_part( 'sidebar', 'none' ); ?>
			</aside>
		</main><!-- .site-main -->
	</section><!-- .content-area -->

<?php get_footer(); ?>