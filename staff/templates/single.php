<?php
/**
 * The template for displaying show pages
 *
 * @package WordPress
 * @subpackage HPMv2
 * @since HPMv2 1.0
 */

get_header(); ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">
		<?php
			while ( have_posts() ) : the_post();
				$staff = get_post_meta( get_the_ID(), 'hpm_staff_meta', true );
				$staff_authid = get_post_meta( get_the_ID(), 'hpm_staff_authid', true );
				$staff_pic = wp_get_attachment_image_src( get_post_thumbnail_id(), 'medium' ); ?>

			<header class="page-header">
				<div id="author-wrap">
					<div class="author-wrap-left">
			<?php
				if ( !empty( $staff_pic ) ) : ?>
						<img src="<?PHP	echo $staff_pic[0]; ?>" class="author-thumb" />
			<?php
				endif; ?>
						<h1 class="entry-title"><?php the_title(); ?></h1>
						<h3><?php echo $staff['title']; ?></h3>
			<?php
				if ( !empty( $staff ) ): ?>
						<div class="author-social">
				<?php
					if (!empty( $staff['facebook'] ) ) : ?>
							<div class="social-icon">
								<a href="<?php echo $staff['facebook']; ?>" target="_blank"><span class="fa fa-facebook" aria-hidden="true"></span></a>
							</div>
			<?php
					endif;
					if ( !empty( $staff['twitter'] ) ) : ?>
							<div class="social-icon">
								<a href="<?php echo $staff['twitter']; ?>" target="_blank"><span class="fa fa-twitter" aria-hidden="true"></span></a>
							</div>
			<?php
					endif;
					if ( !empty( $staff['email'] ) ) : ?>
							<div class="social-icon">
								<a href="mailto:<?php echo $staff['email']; ?>" target="_blank"><span class="fa fa-envelope" aria-hidden="true"></span></a>
							</div>
			<?php
					endif; ?>
						</div>
					</div>
					<div class="author-info-wrap">
				<?php
					$author_bio = get_the_content();
					if ( $author_bio == "<p>Biography pending.</p>" || $author_bio == "<p>Biography pending</p>" ) :
						$author_bio = '';
					endif;
					echo apply_filters( 'hpm_filter_text', $author_bio );
				?>
					</div>
				</div>
			<?php
				endif; ?>
			</header><!-- .page-header -->
		<?php
			endwhile; ?>
			<aside class="column-right">
				<?php get_template_part( 'sidebar', 'none' ); ?>
			</aside>
		<?php
			if ( !empty( $staff_authid ) && $staff_authid > 0 ) :
				echo $nice_name = get_the_author_meta( 'user_nicename', $staff_authid );
				$auth = new WP_query( [
					'author' => $staff_authid,
					'posts_per_page' => 15,
					'post_type' => 'post',
					'post_status' => 'publish'
				 ] );
				if ( $auth->have_posts() ) : ?>
			<section id="search-results">
		<?php
					while ( $auth->have_posts() ) : $auth->the_post();
						get_template_part( 'content', get_post_format() );
					endwhile;
						wp_reset_postdata(); ?>
				<div class="readmore">
					<a href="/articles/author/<?php echo $nice_name; ?>/page/2">View More Stories</a>
				</div>
			</section>
				<?php
					endif;
				endif; ?>
		</main><!-- .site-main -->
	</div><!-- .content-area -->

<?php get_footer(); ?>
