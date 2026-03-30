<?php
/**
 * Plugin Name:       Snap Carousel — Block Style
 * Description:       Turn any Group block into an accessible horizontal carousel — in one click. CSS scroll-snap, keyboard navigation, ARIA, zero dependency.
 * Version:           1.0.3
 * Author:            WeAre[WP]
 * Author URI:        https://www.wearewp.pro
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       snap-carousel-block-style
 * Requires at least: 6.4
 * Requires PHP:      8.0
 *
 * @package WearewpSnapCarousel
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

define( 'WEAREWP_SNAPCAROUSEL_VERSION', '1.0.3' );

/**
 * ─────────────────────────────────────────────
 * Register block styles
 * ─────────────────────────────────────────────
 */
add_action( 'init', function () {

	$variations = [
		[
			'name'  => 'snap-carousel',
			'label' => __( 'Carousel (3 items)', 'snap-carousel-block-style' ),
		],
		[
			'name'  => 'snap-carousel-single',
			'label' => __( 'Carousel (1 item)', 'snap-carousel-block-style' ),
		],
		[
			'name'  => 'snap-carousel-duo',
			'label' => __( 'Carousel (2 items)', 'snap-carousel-block-style' ),
		],
		[
			'name'  => 'snap-carousel-quad',
			'label' => __( 'Carousel (4 items)', 'snap-carousel-block-style' ),
		],
	];

	foreach ( $variations as $style ) {
		register_block_style( 'core/group', $style );
		register_block_style( 'core/query', $style );
	}
});

/**
 * ─────────────────────────────────────────────
 * Register CSS + JS (front-end only, enqueued on demand)
 * Assets are only enqueued when a carousel
 * is detected via the render_block filter.
 * ─────────────────────────────────────────────
 */
add_action( 'wp_enqueue_scripts', function () {

	wp_register_style(
		'wearewp-snapcarousel-style',
		plugin_dir_url( __FILE__ ) . 'assets/snap-carousel.css',
		[],
		WEAREWP_SNAPCAROUSEL_VERSION
	);

	wp_register_script(
		'wearewp-snapcarousel-script',
		plugin_dir_url( __FILE__ ) . 'assets/snap-carousel.js',
		[],
		WEAREWP_SNAPCAROUSEL_VERSION,
		true
	);

	// Pass translatable strings to JavaScript
	wp_localize_script( 'wearewp-snapcarousel-script', 'wearewpSnapcarouselL10n', [
		/* translators: %1$d: current item number, %2$d: total items */
		'itemOf'  => __( 'Item %1$d of %2$d', 'snap-carousel-block-style' ),
		/* translators: %1$d: first visible item, %2$d: last visible item, %3$d: total items */
		'itemsOf' => __( 'Items %1$d to %2$d of %3$d', 'snap-carousel-block-style' ),
	] );
});

/**
 * ─────────────────────────────────────────────
 * Enqueue editor-specific CSS for carousel preview
 * ─────────────────────────────────────────────
 */
add_action( 'enqueue_block_editor_assets', function () {

	wp_enqueue_style(
		'wearewp-snapcarousel-editor-style',
		plugin_dir_url( __FILE__ ) . 'assets/snap-carousel-editor.css',
		[],
		WEAREWP_SNAPCAROUSEL_VERSION
	);
});

/**
 * ─────────────────────────────────────────────
 * Shared helpers for carousel rendering
 * ─────────────────────────────────────────────
 */

/**
 * Generate navigation buttons HTML for a carousel.
 *
 * @param string $uid Unique carousel ID for aria-controls.
 * @return string Navigation HTML.
 */
function wearewp_snapcarousel_nav_html( string $uid ): string {
	return sprintf(
		'<nav class="snap-carousel-nav" aria-label="%4$s">
			<button class="snap-carousel-prev" aria-controls="%1$s" aria-label="%2$s" disabled>
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M13 4L7 10L13 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<button class="snap-carousel-next" aria-controls="%1$s" aria-label="%3$s">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M7 4L13 10L7 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
		</nav>',
		esc_attr( $uid ),
		esc_attr__( 'Previous items', 'snap-carousel-block-style' ),
		esc_attr__( 'Next items', 'snap-carousel-block-style' ),
		esc_attr__( 'Carousel navigation', 'snap-carousel-block-style' )
	);
}

/**
 * Wrap block content with nav, live region, and outer wrapper.
 *
 * @param string $block_content The carousel HTML.
 * @param string $uid           Unique carousel ID.
 * @return string Wrapped HTML.
 */
function wearewp_snapcarousel_wrap( string $block_content, string $uid ): string {
	$nav_html    = wearewp_snapcarousel_nav_html( $uid );
	$live_region = '<div class="snap-carousel-live sr-only" aria-live="polite" aria-atomic="true"></div>';
	return '<div class="snap-carousel-wrapper">' . $nav_html . $block_content . $live_region . '</div>';
}

/**
 * ─────────────────────────────────────────────
 * render_block filter: inject ARIA + nav (Group)
 * ─────────────────────────────────────────────
 *
 * @since 1.0.0
 */
add_filter( 'render_block_core/group', function ( string $block_content, array $block ): string {

	$class_name = $block['attrs']['className'] ?? '';

	if ( ! preg_match( '/\bis-style-snap-carousel\b/', $class_name ) ) {
		return $block_content;
	}

	wp_enqueue_style( 'wearewp-snapcarousel-style' );
	wp_enqueue_script( 'wearewp-snapcarousel-script' );

	$uid   = 'snap-carousel-' . wp_unique_id();
	$total = count( $block['innerBlocks'] ?? [] );

	/** @since 1.0.0 */
	$aria_label = apply_filters( 'wearewp_snapcarousel_aria_label', __( 'Scrollable content', 'snap-carousel-block-style' ), $block );

	// ── Inject ARIA attributes on the container ──
	$block_content = preg_replace(
		'/<div\b([^>]*class="[^"]*is-style-snap-carousel[^"]*"[^>]*)>/',
		'<div$1 id="' . esc_attr( $uid ) . '" role="region" aria-roledescription="' . esc_attr__( 'carousel', 'snap-carousel-block-style' ) . '" aria-label="' . esc_attr( $aria_label ) . '" tabindex="0">',
		$block_content,
		1
	);

	// ── Inject role="group" + aria-label on direct children ──
	$dom = new \DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $block_content . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();

	$container = $dom->getElementById( $uid );
	if ( $container ) {
		$slide_index = 0;
		foreach ( $container->childNodes as $child ) {
			if ( $child->nodeType !== XML_ELEMENT_NODE ) {
				continue;
			}
			$slide_index++;
			$label = sprintf(
				__( '%1$d of %2$d', 'snap-carousel-block-style' ),
				$slide_index,
				$total
			);
			$child->setAttribute( 'role', 'group' );
			$child->setAttribute( 'aria-roledescription', esc_attr__( 'slide', 'snap-carousel-block-style' ) );
			$child->setAttribute( 'aria-label', esc_attr( $label ) );
		}

		$body = $dom->getElementsByTagName( 'body' )->item( 0 );
		$block_content = '';
		foreach ( $body->childNodes as $node ) {
			$block_content .= $dom->saveHTML( $node );
		}
	}

	return wearewp_snapcarousel_wrap( $block_content, $uid );

}, 10, 2 );

/**
 * ─────────────────────────────────────────────
 * render_block filter: inject ARIA + nav (Query Loop)
 * ─────────────────────────────────────────────
 *
 * For Query blocks, the scrollable container is the inner
 * <ul class="wp-block-post-template">, not the outer <div>.
 *
 * @since 1.1.0
 */
add_filter( 'render_block_core/query', function ( string $block_content, array $block ): string {

	$class_name = $block['attrs']['className'] ?? '';

	if ( ! preg_match( '/\bis-style-snap-carousel\b/', $class_name ) ) {
		return $block_content;
	}

	wp_enqueue_style( 'wearewp-snapcarousel-style' );
	wp_enqueue_script( 'wearewp-snapcarousel-script' );

	$uid = 'snap-carousel-' . wp_unique_id();

	/** @since 1.0.0 */
	$aria_label = apply_filters( 'wearewp_snapcarousel_aria_label', __( 'Scrollable content', 'snap-carousel-block-style' ), $block );

	// ── Parse HTML to find the <ul class="wp-block-post-template"> ──
	$dom = new \DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $block_content . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();

	// Find the post-template <ul>
	$post_template = null;
	foreach ( $dom->getElementsByTagName( 'ul' ) as $ul ) {
		if ( str_contains( $ul->getAttribute( 'class' ) ?? '', 'wp-block-post-template' ) ) {
			$post_template = $ul;
			break;
		}
	}

	if ( $post_template ) {
		// Set ARIA on the <ul> (the actual scroll container)
		$post_template->setAttribute( 'id', $uid );
		$post_template->setAttribute( 'role', 'region' );
		$post_template->setAttribute( 'aria-roledescription', esc_attr__( 'carousel', 'snap-carousel-block-style' ) );
		$post_template->setAttribute( 'aria-label', esc_attr( $aria_label ) );
		$post_template->setAttribute( 'tabindex', '0' );

		// Count <li> children
		$total = 0;
		foreach ( $post_template->childNodes as $child ) {
			if ( $child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'li' ) {
				$total++;
			}
		}

		// Label each <li> as a slide
		$slide_index = 0;
		foreach ( $post_template->childNodes as $child ) {
			if ( $child->nodeType !== XML_ELEMENT_NODE || $child->nodeName !== 'li' ) {
				continue;
			}
			$slide_index++;
			$label = sprintf(
				__( '%1$d of %2$d', 'snap-carousel-block-style' ),
				$slide_index,
				$total
			);
			$child->setAttribute( 'role', 'group' );
			$child->setAttribute( 'aria-roledescription', esc_attr__( 'slide', 'snap-carousel-block-style' ) );
			$child->setAttribute( 'aria-label', esc_attr( $label ) );
		}
	}

	// Extract body content
	$body = $dom->getElementsByTagName( 'body' )->item( 0 );
	$block_content = '';
	foreach ( $body->childNodes as $node ) {
		$block_content .= $dom->saveHTML( $node );
	}

	return wearewp_snapcarousel_wrap( $block_content, $uid );

}, 10, 2 );
