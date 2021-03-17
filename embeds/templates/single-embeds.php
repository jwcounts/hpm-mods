<?php
/*
Template Name: Default Embed
Template Post Type: embeds
*/
/**
 * The template for displaying embeds
 *
 * @package WordPress
 * @subpackage HPMv2
 * @since HPMv2 1.0
 */
global $wp_query;
$ID = $wp_query->queried_object_id;
$hpm_embed = get_post_meta( $ID, 'hpm_embed', true );
?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js" xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml" dir="ltr" prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb#">
	<head>
		<?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>
		<div id="fb-root"></div>
		<script>window.fbAsyncInit = function() { FB.init({ appId: '523938487799321', xfbml: true, version: 'v10.0' });}; (function(d, s, id){ var js, fjs = d.getElementsByTagName(s)[0]; if (d.getElementById(id)) {return;} js = d.createElement(s); js.id = id; js.src = "//connect.facebook.net/en_US/sdk.js"; fjs.parentNode.insertBefore(js, fjs); }(document, 'script', 'facebook-jssdk'));</script>
<?php if ( $hpm_embed['branding'] ) : ?>
		<div class="container">
			<header id="masthead" class="site-header" role="banner">
				<div class="site-branding">
					<div class="site-logo">
						<a href="/" rel="home" title="<?php bloginfo( 'name' ); ?>">&nbsp;</a>
					</div>
				</div><!-- .site-branding -->
			</header><!-- .site-header -->
		</div>
<?php endif; ?>
		<div id="page" class="hfeed site">
			<div id="content" class="site-content">
				<div id="primary" class="content-area">
					<main id="main" class="site-main" role="main">
						<?PHP while (have_posts()) : the_post(); ?>
						<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
							<header class="entry-header screen-reader-text">
								<?php the_title('<h1 class="entry-title">', '</h1>'); ?>
								<div class="byline-date">
									<?PHP coauthors_posts_links(' / ', ' / ', '<address class="vcard author">', '</address>', true);
									echo " | ";
									$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time> (Last Updated: <time class="updated" datetime="%3$s">%4$s</time>)';
									$time_string = sprintf(
										$time_string,
										esc_attr(get_the_date('c')),
										get_the_date(),
										esc_attr(get_the_modified_date('c')),
										get_the_modified_date()
									);

									printf(
										'<span class="posted-on"><span class="screen-reader-text">%1$s </span>%2$s</span>',
										_x('Posted on', 'Used before publish date.', 'hpmv2'),
										$time_string
									);
									?>
								</div>
							</header><!-- .entry-header -->
							<div class="entry-content">
								<?php the_content( sprintf( __( 'Continue reading %s', 'hpmv2' ), the_title( '<span class="screen-reader-text">', '</span>', false ) ) ); ?>
							</div><!-- .entry-content -->
						</article><!-- #post-## -->
						<?php endwhile; ?>
					</main><!-- .site-main -->
				</div><!-- .content-area -->
			</div><!-- .site -->
		</div>
<?php if ( $hpm_embed['responsive'] ) : ?>
		<script src="https://pym.nprapps.org/pym.v1.min.js"></script>
		<script>var pymChild = new pym.Child({polling: 500});</script>
<?php endif; ?>
		<?php wp_footer(); ?>
	</body>
</html>