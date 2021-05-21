<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0"
	exclude-result-prefixes="xhtml feedburner" xmlns:feedburner="http://rssnamespace.org/feedburner/ext/1.0"
	xmlns:xhtml="http://www.w3.org/1999/xhtml">
	<xsl:output method="html" doctype-public="HTML" />
	<xsl:variable name="title" select="/rss/channel/title" />
	<xsl:variable name="feedUrl" select="/rss/channel/atom10:link[@rel='self']/@href"
		xmlns:atom10="http://www.w3.org/2005/Atom" />
	<xsl:template match="/">
		<xsl:element name="html">
			<head>
				<title><xsl:value-of select="$title" /> from Houston Public Media</title>
				<link rel="alternate" type="application/rss+xml" title="{$title}" href="{$feedUrl}" />
				<link rel="stylesheet" href="https://cdn.hpm.io/assets/fonts/fontawesome/css/all.css" type="text/css" media="all" />
				<link href="https://cdn.hpm.io/assets/css/style.css" rel="stylesheet" type="text/css" media="all" />
				<xsl:element name="meta">
					<xsl:attribute name="charset">UTF-8</xsl:attribute>
				</xsl:element>
				<xsl:element name="meta">
					<xsl:attribute name="name">viewport</xsl:attribute>
					<xsl:attribute name="content">width=device-width, initial-scale=1, maximum-scale=1</xsl:attribute>
				</xsl:element>
				<xsl:element name="meta">
					<xsl:attribute name="name">description</xsl:attribute>
					<xsl:attribute name="content"><xsl:value-of select="description" /></xsl:attribute>
				</xsl:element>
				<xsl:element name="meta">
					<xsl:attribute name="http-equiv">X-UA-Compatible</xsl:attribute>
					<xsl:attribute name="content">IE=edge,chrome=1</xsl:attribute>
				</xsl:element>
				<xsl:element name="link">
					<xsl:attribute name="rel">shortcut icon</xsl:attribute>
					<xsl:attribute name="href">https://cdn.hpm.io/assets/images/favicon/icon-48.png</xsl:attribute>
				</xsl:element>
				<xsl:element name="link">
					<xsl:attribute name="rel">icon</xsl:attribute>
					<xsl:attribute name="href">https://cdn.hpm.io/assets/images/favicon/icon-192.png</xsl:attribute>
					<xsl:attribute name="type">image/png</xsl:attribute>
					<xsl:attribute name="sizes">192x192</xsl:attribute>
				</xsl:element>
				<xsl:element name="link">
					<xsl:attribute name="rel">apple-touch-icon</xsl:attribute>
					<xsl:attribute name="href">https://cdn.hpm.io/assets/images/favicon/apple-touch-icon-180.png</xsl:attribute>
					<xsl:attribute name="type">image/png</xsl:attribute>
					<xsl:attribute name="sizes">180x180</xsl:attribute>
				</xsl:element>
				<xsl:element name="script">
					<xsl:attribute name="type">text/javascript</xsl:attribute>
					<xsl:attribute name="src">https://cdn.hpm.io/assets/js/analytics/index.js</xsl:attribute>
				</xsl:element>
				<xsl:element name="script">
					<xsl:attribute name="type">text/javascript</xsl:attribute>
					<xsl:attribute name="src">https://www.google-analytics.com/analytics.js</xsl:attribute>
				</xsl:element>
				<xsl:element name="script">
					<xsl:attribute name="type">text/javascript</xsl:attribute>
					<xsl:attribute name="src">https://cdn.hpm.io/assets/js/main.js?v=1</xsl:attribute>
				</xsl:element>
				<xsl:element name="script">
					<xsl:attribute name="type">text/javascript</xsl:attribute>
					<xsl:attribute name="src">https://cdn.hpm.io/assets/js/plyr/plyr.js?v=1</xsl:attribute>
				</xsl:element>
				<style type="text/css">.pod-desc { font: 500 1.125em/1.125em var(--hpm-font-main); color: rgb(142,144,144); }</style>
			</head>
			<xsl:apply-templates select="rss/channel" />
		</xsl:element>
	</xsl:template>
	<xsl:template match="channel">
		<body class="page page-template-page-series">
			<div class="container">
				<header id="masthead" class="site-header" role="banner">
					<div class="site-branding">
						<div class="site-logo">
							<a href="/" rel="home" title="Houston Public Media"></a>
						</div>
						<div id="top-schedule">
							<div class="top-schedule-label"><button data-href="#top-schedule-wrap"><span class="fas fa-calendar" aria-hidden="true"></span>Schedules</button></div>
							<div class="top-schedule-link-wrap">
								<div class="top-schedule-links"><a href="/tv8">TV 8 Guide</a></div>
								<div class="top-schedule-links"><a href="/news887">News 88.7</a></div>
								<div class="top-schedule-links"><a href="/classical">Classical</a></div>
								<div class="top-schedule-links"><a href="/mixtape">Mixtape</a></div>
							</div>
						</div>
						<div id="top-listen"><button data-href="/listen-live" data-dialog="480:855"><span class="fas fa-microphone" aria-hidden="true"></span>Listen</button></div>
						<div id="top-watch"><button data-href="/watch-live" data-dialog="820:850"><span class="fas fa-tv" aria-hidden="true"></span>Watch</button></div>
						<div id="top-donate"><a href="/donate"><span class="fas fa-heart" aria-hidden="true"></span><br /><span class="top-mobile-text">Donate</span></a></div>
						<div id="top-mobile-menu" style=""><span class="fas fa-bars" aria-hidden="true"></span></div>
						<nav id="site-navigation" class="main-navigation" role="navigation">
							<div class="menu-main-header-nav-container">
								<ul id="menu-main-header-nav" class="nav-menu">
									<li class="nav-top">
										<a href="/news/" class="nav-item-head-main">News</a>
									</li>
									<li class="nav-top">
										<a href="/arts-culture/" class="nav-item-head-main">Arts/Culture</a>
									</li>
									<li class="nav-top">
										<a href="/education/" class="nav-item-head-main">Education</a>
									</li>
									<li class="nav-top">
										<a href="/shows/" class="nav-item-head-main">Shows</a>
									</li>
									<li class="nav-top">
										<a href="/podcasts/" class="nav-item-head-main">Podcasts</a>
									</li>
									<li class="nav-top">
										<a href="/support/" class="nav-item-head-main">Support</a>
									</li>
									<li class="nav-top nav-donate">
										<a href="/donate" class="nav-item-head-main">Donate</a></li>
									<li class="nav-top nav-passport">
										<a href="/support/passport/" class="nav-item-head-main">Passport</a>
									</li>
									<li class="nav-top nav-uh">
										<a href="https://uh.edu" class="nav-item-head-main">UH</a>
									</li>
									<li class="nav-top nav-top-mobile">
										<a href="/about/" class="nav-item-head-main">About</a>
									</li>
									<li class="nav-top nav-top-mobile">
										<a href="/contact-us/" class="nav-item-head-main">Contact Us</a>
									</li>
								</ul>
							</div>
							<div class="clear"></div>
						</nav><!-- .main-navigation -->
					</div>
				</header>
			</div>
			<div id="page" class="hfeed site">
				<div id="content" class="site-content">
					<div id="primary" class="content-area">
						<main id="main" class="site-main" role="main">
							<article>
								<header class="entry-header">
									<h1 class="entry-title">
										<xsl:choose>
											<xsl:when test="link">
												<a href="{link}" title="Link to original website"><xsl:value-of select="$title" /></a>
											</xsl:when>
											<xsl:otherwise>
												<xsl:value-of select="$title" />
											</xsl:otherwise>
										</xsl:choose>
									</h1>
								</header>
								<div class="entry-content">
									<div class="alignleft">
										<xsl:apply-templates select="image" />
									</div>
									<div class="alignleft">
										<h2>Subscribe Now!</h2>
										<p>Do a search for "<strong><xsl:value-of select="$title" /></strong>" or "<strong>Houston Public Media</strong>" in your podcast app of choice</p>
										<p>Or copy/paste this address:</p>
										<p><form><input type="text" value="{$feedUrl}" style="padding: 0.5em; width: 100%; font-family: var(--hpm-font-main);" /></form></p>
									</div>
								</div>
							</article>
							<aside class="column-right">
								<h2>About <xsl:value-of select="$title" /></h2>
								<div class="pod-desc"><xsl:value-of select="description" disable-output-escaping="no" /></div>
							</aside>
							<section id="search-results">
								<xsl:apply-templates select="item" />
							</section>
						</main>
					</div>
				</div>
			</div>
			<footer id="colophon" class="site-footer" role="contentinfo">
				<section>
					<div class="site-info">
						<div class="foot-logo">
							<a href="https://www.houstonpublicmedia.org/" rel="home" title="Houston Public Media">
								<img src="https://cdn.hpm.io/assets/images/HPM-PBS-NPR-White.png" alt="Houston Public Media" />
							</a>
						</div>
					</div>
					<div class="foot-nav">
						<div class="foot-hpm">
							<h3>Houston Public Media</h3>
							<nav id="second-navigation" class="footer-navigation" role="navigation">
								<div class="menu-footer-navigation-container">
									<ul id="menu-footer-navigation" class="nav-menu">
										<li><a href="https://www.houstonpublicmedia.org/about/">About</a></li>
										<li><a href="https://www.houstonpublicmedia.org/about/careers/">Careers</a></li>
										<li><a href="https://www.uh.edu/president/communications/communicae/20200608-commitment-to-the-city/index.php">Commitment</a></li>
										<li><a href="https://www.houstonpublicmedia.org/tv8/">TV</a></li>
										<li><a href="https://www.houstonpublicmedia.org/news887/">Radio</a></li>
										<li><a href="https://www.houstonpublicmedia.org/news/">News</a></li>
										<li><a href="https://www.houstonpublicmedia.org/shows/">Shows</a></li>
									</ul>
								</div>
								<div class="clear"></div>
							</nav>
						</div>
						<div class="foot-comply">
							<h3>Compliance</h3>
							<nav id="third-navigation" class="footer-navigation" role="navigation">
								<div class="menu-footer-compliance-container">
									<ul id="menu-footer-compliance" class="nav-menu">
										<li><a href="https://www.houstonpublicmedia.org/about/corporation-for-public-broadcasting-cpb-compliance/">CPB Compliance</a></li>
										<li><a href="https://www.houstonpublicmedia.org/about/fcc-station-information/">FCC Station Information</a></li>
										<li><a href="https://publicfiles.fcc.gov/fm-profile/kuhf">KUHF Public File</a></li>
										<li><a href="https://publicfiles.fcc.gov/tv-profile/kuht">KUHT Public File</a></li>
										<li><a href="http://www.uhsystem.edu/privacy-notice/">Privacy Policy</a></li>
										<li><a href="https://www.houstonpublicmedia.org/about/additional-disclosures/">Additional Disclosures</a></li>
									</ul>
								</div>
								<div class="clear"></div>
							</nav>
						</div>
					</div>
					<div class="foot-newsletter">
						<h3>Subscribe to Our Newsletters</h3>
						<h4><a href="https://www.houstonpublicmedia.org/support/newslettereguide-signup/">Today in Houston</a></h4>
						<p>Let the Houston Public Media newsroom help you start your day.</p>
						<h4><a href="https://www.houstonpublicmedia.org/support/newslettereguide-signup/">This Week</a></h4>
						<p>Get highlights, trending news, and behind-the-scenes insights from Houston Public Media delivered to your inbox each week.</p>
					</div>
					<div class="foot-contact">
						<p class="foot-button"><a href="/contact-us/">Contact Us</a></p>
						<p>4343 Elgin, Houston, TX 77204-0008</p>
						<div id="footer-social">
							<div class="footer-social-icon footer-facebook">
								<a href="https://www.facebook.com/houstonpublicmedia" target="_blank"><span class="fab fa-facebook-f" aria-hidden="true"></span></a>
							</div>
							<div class="footer-social-icon footer-twitter">
								<a href="https://twitter.com/houstonpubmedia" target="_blank"><span class="fab fa-twitter" aria-hidden="true"></span></a>
							</div>
							<div class="footer-social-icon footer-instagram">
								<a href="https://instagram.com/houstonpubmedia" target="_blank"><span class="fab fa-instagram" aria-hidden="true"></span></a>
							</div>
							<div class="footer-social-icon footer-youtube">
								<a href="https://www.youtube.com/user/houstonpublicmedia" target="_blank"><span class="fab fa-youtube" aria-hidden="true"></span></a>
							</div>
							<div class="footer-social-icon footer-linkedin">
								<a href="https://linkedin.com/company/houstonpublicmedia" target="_blank"><span class="fab fa-linkedin-in" aria-hidden="true"></span></a>
							</div>
						</div>
					</div>
				</section>
				<div class="foot-tag">
					<p>Houston Public Media is supported with your gifts to the Houston Public Media Foundation and is licensed to the <a href="https://www.uh.edu" target="_blank">University of Houston</a></p>
					<p>Copyright © 2021</p>
				</div>
			</footer>
			<script type="text/javascript">hpm.audioPlayers();var pods = document.querySelectorAll('.pod-desc');if (pods !== null) { Array.from(pods).forEach((p) => {p.innerHTML = p.innerText; }); }</script>
		</body>
	</xsl:template>
	<xsl:template match="item" xmlns:dc="http://purl.org/dc/elements/1.1/">
		<xsl:if test="position() = 1">
			<h2 style="padding-left: 1em;">Current Feed Content</h2>
		</xsl:if>
		<article>
			<div class="search-result-content-full">
				<header class="entry-header">
					<h2 class="entry-title">
						<xsl:choose>
							<xsl:when test="guid[@isPermaLink='true' or not(@isPermaLink)]">
								<a href="{guid}">
									<xsl:value-of select="title" />
								</a>
							</xsl:when>
							<xsl:when test="link">
								<a href="{link}">
									<xsl:value-of select="title" />
								</a>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="title" />
							</xsl:otherwise>
						</xsl:choose>
					</h2>
				</header>
				<div class="entry-summary">
					<p><span class="posted-on">
						<xsl:if test="count(child::pubDate)=1"><span>Posted:</span>
							<xsl:text> </xsl:text>
							<xsl:value-of select="pubDate" />
						</xsl:if>
						<xsl:if test="count(child::dc:date)=1"><span>Posted:</span>
							<xsl:text> </xsl:text>
							<xsl:value-of select="dc:date" />
						</xsl:if>
					</span></p>
					<xsl:if test="count(child::enclosure)=1">
						<div class="article-player-wrap">
							<audio controls="controls" class="js-player">
								<source src="{enclosure/@url}?source=podcast-feed-page" type="audio/mpeg" />
								Your browser does not support the <code>audio</code> element. <a href="{enclosure/@url}">Click here to play.</a>
							</audio>
						</div>
					</xsl:if>
					<div class="pod-desc"><xsl:call-template name="outputContent" /></div>
				</div>
			</div>
		</article>
	</xsl:template>
	<xsl:template match="image">
		<a href="{link}" title="Link to original website"><img src="{url}" id="feedimage" alt="{title}" /></a>
		<xsl:text />
	</xsl:template>
	<xsl:template name="outputContent">
		<xsl:choose>
			<xsl:when test="xhtml:body">
				<xsl:copy-of select="xhtml:body/*" />
			</xsl:when>
			<xsl:when test="xhtml:div">
				<xsl:copy-of select="xhtml:div" />
			</xsl:when>
			<xsl:when xmlns:content="http://purl.org/rss/1.0/modules/content/" test="content:encoded">
				<xsl:value-of select="content:encoded" disable-output-escaping="yes" />
			</xsl:when>
			<xsl:when test="description">
				<xsl:value-of select="description" disable-output-escaping="yes" />
			</xsl:when>
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>