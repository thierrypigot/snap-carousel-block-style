/**
 * Snap Carousel — Block Style
 * Keyboard navigation, prev/next buttons, a11y live region
 * Vanilla JS — zero dependency — RTL-safe
 */

( function () {
	'use strict';

	// ── Detect prefers-reduced-motion ──
	var motionQuery = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	function getScrollBehavior() {
		return motionQuery.matches ? 'auto' : 'smooth';
	}

	// ── RTL detection ──
	function isRTL( el ) {
		return getComputedStyle( el ).direction === 'rtl';
	}

	// ── Localized strings (passed from PHP via wp_localize_script) ──
	var l10n = window.snapCarouselL10n || {};

	/**
	 * Initialize a single carousel
	 */
	function initCarousel( carousel ) {
		// Nav and live region are in the parent wrapper (outside the overflow)
		var wrapper    = carousel.closest( '.snap-carousel-wrapper' ) || carousel.parentElement;
		var prevBtn    = wrapper.querySelector( '.snap-carousel-prev' );
		var nextBtn    = wrapper.querySelector( '.snap-carousel-next' );
		var liveRegion = wrapper.querySelector( '.snap-carousel-live' );
		var rtl        = isRTL( carousel );

		// Get scrollable children (exclude nav and live region)
		function getSlides() {
			return Array.from( carousel.children ).filter( function ( child ) {
				return ! child.classList.contains( 'snap-carousel-nav' )
					&& ! child.classList.contains( 'snap-carousel-live' );
			});
		}

		// Calculate scroll amount (first item width + gap)
		function getScrollAmount() {
			var slides = getSlides();
			if ( ! slides.length ) return 300;
			var style = getComputedStyle( carousel );
			var gap   = parseFloat( style.gap ) || 24;
			return slides[0].offsetWidth + gap;
		}

		// Normalize scroll position for RTL compatibility
		// In RTL, Chrome/Firefox return negative scrollLeft values
		function getScrollProgress() {
			var scrollLeft = carousel.scrollLeft;
			var maxScroll  = carousel.scrollWidth - carousel.clientWidth;

			if ( rtl ) {
				// Normalize: Math.abs gives us 0 = start, maxScroll = end
				return {
					current: Math.abs( scrollLeft ),
					max: maxScroll
				};
			}

			return {
				current: scrollLeft,
				max: maxScroll
			};
		}

		// Update button disabled states
		function updateButtons() {
			if ( ! prevBtn || ! nextBtn ) return;

			var pos = getScrollProgress();

			prevBtn.disabled = pos.current <= 10;
			nextBtn.disabled = pos.current >= pos.max - 10;
		}

		// Announce position to screen readers
		function announcePosition() {
			if ( ! liveRegion ) return;

			var slides   = getSlides();
			var total    = slides.length;
			var visible  = [];

			slides.forEach( function ( slide, i ) {
				var rect      = slide.getBoundingClientRect();
				var container = carousel.getBoundingClientRect();

				if ( rect.left >= container.left - 10 && rect.right <= container.right + 10 ) {
					visible.push( i + 1 );
				}
			});

			if ( visible.length > 0 ) {
				var first = visible[0];
				var last  = visible[ visible.length - 1 ];
				var text  = first === last
					? ( l10n.itemOf || 'Item %1$d of %2$d' ).replace( '%1$d', first ).replace( '%2$d', total )
					: ( l10n.itemsOf || 'Items %1$d to %2$d of %3$d' ).replace( '%1$d', first ).replace( '%2$d', last ).replace( '%3$d', total );
				liveRegion.textContent = text;
			}
		}

		// ── Scroll helpers (RTL-aware) ──
		function scrollNext() {
			var amount = rtl ? -getScrollAmount() : getScrollAmount();
			carousel.scrollBy( { left: amount, behavior: getScrollBehavior() } );
		}

		function scrollPrev() {
			var amount = rtl ? getScrollAmount() : -getScrollAmount();
			carousel.scrollBy( { left: amount, behavior: getScrollBehavior() } );
		}

		// ── Event: scroll — separate debounce for buttons (150ms) and announcements (300ms) ──
		var buttonTimer;
		var announceTimer;
		carousel.addEventListener( 'scroll', function () {
			clearTimeout( buttonTimer );
			buttonTimer = setTimeout( updateButtons, 150 );

			clearTimeout( announceTimer );
			announceTimer = setTimeout( announcePosition, 300 );
		}, { passive: true } );

		// ── Event: buttons ──
		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', scrollPrev );
		}

		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', scrollNext );
		}

		// ── Event: keyboard (when carousel has focus) ──
		carousel.addEventListener( 'keydown', function ( e ) {
			switch ( e.key ) {
				case 'ArrowRight':
					e.preventDefault();
					// ArrowRight = physically right: next in LTR, prev in RTL
					if ( rtl ) { scrollPrev(); } else { scrollNext(); }
					break;

				case 'ArrowLeft':
					e.preventDefault();
					// ArrowLeft = physically left: prev in LTR, next in RTL
					if ( rtl ) { scrollNext(); } else { scrollPrev(); }
					break;

				case 'Home':
					e.preventDefault();
					carousel.scrollTo( { left: 0, behavior: getScrollBehavior() } );
					break;

				case 'End':
					e.preventDefault();
					carousel.scrollTo( { left: rtl ? -carousel.scrollWidth : carousel.scrollWidth, behavior: getScrollBehavior() } );
					break;
			}
		});

		// ── Initial state: reset scroll and update buttons ──
		carousel.scrollLeft = 0;
		updateButtons();

		// Re-reset after full page load (images, fonts cause layout shift)
		window.addEventListener( 'load', function () {
			carousel.scrollLeft = 0;
			updateButtons();
		});

		// Recalculate on resize
		var resizeTimer;
		window.addEventListener( 'resize', function () {
			clearTimeout( resizeTimer );
			resizeTimer = setTimeout( function () {
				rtl = isRTL( carousel );
				updateButtons();
			}, 200 );
		});
	}

	/**
	 * Init on DOM ready
	 */
	function init() {
		var carousels = document.querySelectorAll( '[class*="is-style-snap-carousel"]' );
		carousels.forEach( initCarousel );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

} )();
