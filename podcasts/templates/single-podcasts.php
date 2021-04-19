<?php
/**
 * RSS2 Feed Template for Podcasts.
 *
 * @package WordPress
 */
	if ( empty( $wp_query->query['name'] ) && $wp_query->query['feed'] == 'feed' ) :
		header('Content-Type: ' . feed_content_type('rss2') . '; charset=' . get_option('blog_charset'), true);
		$more = 1;
		echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>';
		do_action( 'rss_tag_pre', 'rss2' ); ?>
<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:media="http://search.yahoo.com/mrss/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	<?php do_action( 'rss2_ns' ); ?>
>
<channel>
	<title><?php wp_title_rss(); ?></title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php bloginfo_rss('url') ?></link>
	<description><?php bloginfo_rss("description") ?></description>
	<lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></lastBuildDate>
	<language><?php bloginfo_rss( 'language' ); ?></language>
	<sy:updatePeriod><?php
		$duration = 'hourly';
		echo apply_filters( 'rss_update_period', $duration );
	?></sy:updatePeriod>
	<sy:updateFrequency><?php
		$frequency = '1';
		echo apply_filters( 'rss_update_frequency', $frequency );
	?></sy:updateFrequency>
	<?php
	do_action( 'rss2_head');
	while( have_posts()) : the_post();
	?>
	<item>
		<title><?php the_title_rss() ?></title>
		<link><?php the_permalink_rss() ?>?utm_source=rss-podcasts&amp;utm_medium=link&amp;utm_campaign=hpm-rss-link</link>
		<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
		<dc:creator><![CDATA[<?php echo coauthors( ', ', ', ', '', '', false ); ?>]]></dc:creator>
<?php the_category_rss('rss2') ?>
		<guid isPermaLink="true"><?php the_permalink_rss() ?>?utm_source=rss-podcasts&amp;utm_medium=link&amp;utm_campaign=hpm-rss-link</guid>
<?php if ( get_option( 'rss_use_excerpt' ) ) : ?>
		<description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
<?php else : ?>
		<description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
<?php
		$content = get_the_content_feed('rss2');
		if ( strlen( $content ) > 0 ) : ?>
		<content:encoded><![CDATA[<?php echo $content; ?>]]></content:encoded>
<?php 	else : ?>
		<content:encoded><![CDATA[<?php the_excerpt_rss(); ?>]]></content:encoded>
<?php
		endif;
	endif;
	if ( has_post_thumbnail() ) :
		$thumb = wp_get_attachment_image_src( get_post_thumbnail_id(), 'thumbnail' ); ?>
		<media:thumbnail url="<?php echo $thumb[0]; ?>" width="<?php echo $thumb[1]; ?>" height="<?php echo $thumb[2]; ?>" />
<?php endif;
	rss_enclosure();
	do_action( 'rss2_item' ); ?>
	</item>
<?php endwhile; ?>
</channel>
</rss><?php
	else :
		header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
		echo get_option( 'hpm_podcast-'.$wp_query->query['name'] );
	endif; ?>






