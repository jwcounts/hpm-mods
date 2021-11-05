<?php
/**
 * The template for displaying the Podcasts archive
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package HPM_Podcasts
 */

get_header(); ?>
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">
		<?php if ( have_posts() ) : ?>
			<header class="page-header">
				<h1 class="page-title">Podcasts</h1>
				<?php the_archive_description( '<div class="taxonomy-description">', '</div>' ); ?>
			</header>
			<section class="archive">
			<?php
			while ( have_posts() ) : the_post();
				get_template_part( 'content', 'podcasts' );
			endwhile;

			the_posts_pagination( [
				'prev_text' => __( '&lt;', 'hpm-podcasts' ),
				'next_text' => __( '&gt;', 'hpm-podcasts' ),
				'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'hpm-podcasts' ) . ' </span>',
			] );
		else :
			get_template_part( 'content', 'none' );
		endif; ?>
			</section>
			<aside>
				<?php get_template_part( 'sidebar', 'none' ); ?>
			</aside>
		</main>
	</div>
<?php get_footer(); ?>