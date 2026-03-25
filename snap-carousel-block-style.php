<?php
/**
 * Plugin Name:       Snap Carousel — Block Style
 * Description:       Turn any Group block into an accessible horizontal carousel — in one click. CSS scroll-snap, keyboard navigation, ARIA, zero dependency.
 * Version:           1.0.2
 * Author:            WeAre[WP]
 * Author URI:        https://www.wearewp.pro
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       snap-carousel-block-style
 * Requires at least: 6.4
 * Requires PHP:      8.0
 *
 * @package SnapCarousel
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

define( 'SNAP_CAROUSEL_VERSION', '1.0.2' );

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
		'snap-carousel-style',
		plugin_dir_url( __FILE__ ) . 'assets/snap-carousel.css',
		[],
		SNAP_CAROUSEL_VERSION
	);

	wp_register_script(
		'snap-carousel-script',
		plugin_dir_url( __FILE__ ) . 'assets/snap-carousel.js',
		[],
		SNAP_CAROUSEL_VERSION,
		true
	);

	// Pass translatable strings to JavaScript
	wp_localize_script( 'snap-carousel-script', 'snapCarouselL10n', [
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
		'snap-carousel-editor-style',
		plugin_dir_url( __FILE__ ) . 'assets/snap-carousel-editor.css',
		[],
		SNAP_CAROUSEL_VERSION
	);
});

/**
 * ─────────────────────────────────────────────
 * render_block filter: inject ARIA + nav
 * ─────────────────────────────────────────────
 *
 * Detects Group blocks with an is-style-snap-carousel*
 * class and enriches the HTML output.
 *
 * @since 1.0.0
 */
add_filter( 'render_block_core/group', function ( string $block_content, array $block ): string {

	// Only process blocks that carry one of our styles (via block attributes,
	// not $block_content, to avoid matching parent wrappers)
	$class_name = $block['attrs']['className'] ?? '';

	if ( ! preg_match( '/\bis-style-snap-carousel\b/', $class_name ) ) {
		return $block_content;
	}

	// Enqueue assets only when a carousel is actually rendered
	wp_enqueue_style( 'snap-carousel-style' );
	wp_enqueue_script( 'snap-carousel-script' );

	// Generate a unique ID for aria-controls
	$uid = 'snap-carousel-' . wp_unique_id();

	// Count direct children (innerBlocks)
	$total = count( $block['innerBlocks'] ?? [] );

	/**
	 * Filters the aria-label applied to the carousel container.
	 *
	 * @since 1.0.0
	 *
	 * @param string $label Default label ("Scrollable content").
	 * @param array  $block The parsed block data.
	 */
	$aria_label = apply_filters( 'snap_carousel_aria_label', __( 'Scrollable content', 'snap-carousel-block-style' ), $block );

	// ── Inject ARIA attributes on the container ──
	// Target the first div of the block
	$block_content = preg_replace(
		'/<div\b([^>]*class="[^"]*is-style-snap-carousel[^"]*"[^>]*)>/',
		'<div$1 id="' . esc_attr( $uid ) . '" role="region" aria-roledescription="' . esc_attr__( 'carousel', 'snap-carousel-block-style' ) . '" aria-label="' . esc_attr( $aria_label ) . '" tabindex="0">',
		$block_content,
		1
	);

	// ── Inject role="group" + aria-label on each direct child ──
	// innerBlocks are rendered as direct child divs
	$slide_index = 0;
	$block_content = preg_replace_callback(
		'/(<div\b[^>]*class="[^"]*wp-block-(?:group|column|image|cover|woocommerce)[^"]*"[^>]*)(>)/',
		function ( $matches ) use ( &$slide_index, $total ) {
			// Skip the carousel container itself
			if ( str_contains( $matches[0], 'is-style-snap-carousel' ) ) {
				return $matches[0];
			}
			$slide_index++;
			$label = sprintf(
				/* translators: %1$d: current slide number, %2$d: total slides */
				__( '%1$d of %2$d', 'snap-carousel-block-style' ),
				$slide_index,
				$total
			);
			return $matches[1] . ' role="group" aria-roledescription="' . esc_attr__( 'slide', 'snap-carousel-block-style' ) . '" aria-label="' . esc_attr( $label ) . '"' . $matches[2];
		},
		$block_content
	);

	// ── Navigation buttons ──
	$nav_html = sprintf(
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

	// ── Live region ──
	$live_region = '<div class="snap-carousel-live sr-only" aria-live="polite" aria-atomic="true"></div>';

	// ── Wrap in a wrapper to place nav outside the overflow ──
	$block_content = '<div class="snap-carousel-wrapper">' . $nav_html . $block_content . $live_region . '</div>';

	return $block_content;

}, 10, 2 );
