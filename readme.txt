=== Snap Carousel — Block Style ===
Contributors: wearewp
Tags: carousel, slider, gutenberg, block-style, accessible
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.3
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn any Group block into an accessible horizontal carousel — in one click. CSS scroll-snap, zero dependency.

== Description ==

This plugin adds 4 block styles to the WordPress **Group** block (core/group):

- **Carousel (3 items)** — displays 3 visible items
- **Carousel (1 item)** — full-width slideshow mode
- **Carousel (2 items)** — displays 2 visible items
- **Carousel (4 items)** — displays 4 visible items

= Features =

- 100% CSS scroll-snap for scrolling
- Prev/next navigation buttons
- Keyboard navigation (Arrow keys, Home, End)
- Full ARIA attributes (role="region", aria-roledescription, aria-live)
- **Peek effect**: partial visibility of the next item, signaling scrollable content
- Responsive (auto tablet/mobile adaptation)
- Respects `prefers-reduced-motion`
- Works with any child block (images, columns, groups, WooCommerce…)
- ~2 KB CSS + ~2 KB JS, zero dependency
- Fully internationalized (i18n ready, French translation included)
- Easy to customize via CSS custom properties or overrides

= Accessibility (WCAG 2.2 AA) =

- `role="region"` + `aria-roledescription="carousel"` on the container
- `role="group"` + `aria-roledescription="slide"` on each item
- `aria-label="X of Y"` for position context
- `tabindex="0"` for keyboard focus
- `aria-live="polite"` to announce changes
- Buttons with `aria-label` and `aria-controls`
- Touch targets 44×44px minimum
- Visible focus indicator

== Installation ==

1. Upload the `snap-carousel-block-style` folder to `/wp-content/plugins/`
2. Activate the plugin in the Plugins menu
3. You're all set!

== Usage ==

1. In the editor, create a **Group** block
2. Set the group layout to **Row**
3. Add child blocks (images, groups, columns…)
4. In the sidebar panel → Styles → choose **Carousel (3 items)** (or another variant)
5. Publish

Navigation buttons and accessibility attributes are automatically injected on the front-end.

== Customization ==

The carousel is designed to work out of the box, but you can easily override styles in your theme's `style.css` or via the WordPress Customizer > Additional CSS.

= Disable the peek effect =

By default, items are slightly narrower than the visible area so the next item "peeks" in — signaling there is more content to scroll. To disable this and show full-width items:

`/* Disable peek — 3 items variant */
.is-style-snap-carousel > * {
    flex-basis: calc(33.333% - 1rem) !important;
}

/* Disable peek — 1 item variant */
.is-style-snap-carousel-single > * {
    flex-basis: 100% !important;
}

/* Disable peek — 2 items variant */
.is-style-snap-carousel-duo > * {
    flex-basis: calc(50% - 0.75rem) !important;
}

/* Disable peek — 4 items variant */
.is-style-snap-carousel-quad > * {
    flex-basis: calc(25% - 1.125rem) !important;
}`

= Customize navigation arrows =

`/* Arrow color and background */
.snap-carousel-prev,
.snap-carousel-next {
    background: #0073aa;
    color: #ffffff;
    border-color: #0073aa;
}

/* Arrow hover state */
.snap-carousel-prev:hover,
.snap-carousel-next:hover {
    background: #005177;
    color: #ffffff;
}

/* Arrow size (default: 44px — WCAG minimum touch target) */
.snap-carousel-prev,
.snap-carousel-next {
    width: 48px;
    height: 48px;
}

/* Square arrows instead of round */
.snap-carousel-prev,
.snap-carousel-next {
    border-radius: 8px;
}`

= Customize spacing =

`/* Gap between items */
[class*="is-style-snap-carousel"] {
    gap: 2rem;
}

/* Space above carousel (room for navigation) */
.snap-carousel-wrapper {
    padding-top: 4rem;
}`

= Customize focus indicator =

`/* Custom focus color for keyboard navigation */
[class*="is-style-snap-carousel"]:focus-visible {
    outline-color: #ff6600;
    outline-width: 3px;
}

.snap-carousel-prev:focus-visible,
.snap-carousel-next:focus-visible {
    outline-color: #ff6600;
}`

= Use with WordPress theme.json design tokens =

The carousel already uses `--wp--preset--color--base`, `--wp--preset--color--contrast` and `--wp--preset--color--primary` tokens. You can override these per-block in theme.json or via CSS:

`/* Example: dark themed carousel */
.snap-carousel-wrapper {
    --wp--preset--color--base: #1a1a2e;
    --wp--preset--color--contrast: #e0e0e0;
    --wp--preset--color--primary: #e94560;
}`

== Frequently Asked Questions ==

= Does this plugin work with any WordPress theme? =

Yes. Snap Carousel uses standard WordPress Block Styles API and CSS custom properties. It works with any block theme (FSE) and most classic themes that support the Group block in Row layout. The navigation arrows automatically adapt to your theme's colors via `--wp--preset--color--*` design tokens.

= What types of content can I put inside the carousel? =

Any block that WordPress allows inside a Group block: images, columns, groups, cover blocks, WooCommerce product blocks, custom HTML… The carousel simply applies horizontal scroll-snap to the Group's direct children.

= Is this plugin accessible? =

Yes. Accessibility was a core design goal, not an afterthought. The carousel meets WCAG 2.2 AA requirements: full ARIA carousel pattern (`role="region"`, `aria-roledescription`, `aria-live`), keyboard navigation (Arrow keys, Home, End), 44×44px minimum touch targets, visible focus indicators, and `prefers-reduced-motion` support.

= What is the "peek" effect? =

By default, items are slightly narrower than the visible area, so the next item is partially visible — signaling that there is more content to scroll. This is a well-known UX pattern that improves discoverability, especially on touch devices where navigation arrows may not be visible. You can disable it with a simple CSS override (see the Customization section).

= Will this plugin slow down my site? =

No. The total footprint is approximately 2 KB CSS + 2 KB JS (minified), with zero external dependencies. Assets are only loaded on pages that actually contain a carousel, so there is no performance penalty on other pages.

= Can I use different carousel variants on the same page? =

Yes. Each Group block independently gets its own style. You can have a 1-item slideshow hero at the top, a 3-item carousel in the middle, and a 4-item grid-style carousel at the bottom — all on the same page.

= What happens if I deactivate the plugin? =

Your content remains intact. The Group block reverts to its default Row layout. No data is lost — the plugin only adds a visual style and does not modify your content in the database.

= Can I customize the carousel appearance? =

Absolutely. The plugin uses WordPress design tokens and semantic CSS classes. You can override arrow colors, sizes, shapes, spacing, focus indicators, and even disable the peek effect — all via simple CSS. See the Customization section for copy-paste examples.

= Does it work with WooCommerce? =

Yes. You can wrap WooCommerce product blocks inside a Group block, apply a carousel style, and it works out of the box. This is a great way to showcase featured products.

= Does the carousel auto-play? =

No, intentionally. Auto-playing carousels are a well-documented accessibility barrier (WCAG 2.2.2 Pause, Stop, Hide) and tend to lower engagement. The carousel is user-driven: scroll, swipe, keyboard, or click the navigation arrows.

= Why does the Group justification setting have no effect on the carousel? =

The carousel requires items to flow from the start edge for CSS scroll-snap to work properly. The justification control (left, center, right, space-between) is automatically overridden to ensure correct scroll behavior. This is a browser constraint, not a plugin limitation. RTL (right-to-left) languages are fully supported — items automatically flow from right to left.

= Does the carousel support RTL languages? =

Yes. The carousel detects the page direction automatically. In RTL mode (Arabic, Hebrew, etc.), items flow from right to left, navigation arrows adapt their direction, and keyboard navigation (ArrowLeft/ArrowRight) follows the expected visual direction.

== Technical Notes ==

- CSS overrides `flex-wrap: nowrap` on the Row container to force horizontal scrolling
- Items use a slightly reduced `flex-basis` to create a peek effect (next item partially visible)
- Navigation buttons are positioned `absolute` at the top right (adjust `top` value for your theme)
- JS uses a 150ms debounce on scroll for button state updates and 300ms for screen reader announcements
- Compatible with WooCommerce blocks (products, etc.)

== About ==

Snap Carousel is built and maintained by [WeAre[WP]](https://www.wearewp.pro), a french WordPress agency specializing in accessible, high-performance websites for businesses of all sizes.

Need help with your WordPress project? [Get in touch](https://www.wearewp.pro/contact).

== Changelog ==

= 1.0.3 =
* Fix: unique prefixes (wearewp_snapcarousel) for all defines, filters, handles and JS globals — WordPress.org Plugin Review compliance
* Fix: version constant centralized as WEAREWP_SNAPCAROUSEL_VERSION
* New: GitHub Actions release workflow for automated ZIP packaging

= 1.0.2 =
* Fix: Group justification (center, right, space-between) no longer breaks the carousel
* New: full RTL (right-to-left) language support — navigation, keyboard, and scroll detection
* Fix: improved scroll end detection tolerance for sub-pixel rounding
* Enhancement: navigation buttons use CSS logical properties (inset-inline-end)

= 1.0.1 =
* Fix: text domain aligned with plugin slug (snap-carousel-block-style)
* Fix: removed deprecated load_plugin_textdomain() call
* Fix: removed Domain Path header (not needed for wordpress.org hosted plugins)

= 1.0.0 =
* Initial release
* 4 carousel style variations (1, 2, 3, 4 items)
* Peek effect enabled by default (partial next item visible as scroll affordance)
* Full WCAG 2.2 AA accessibility
* i18n ready with French (fr_FR) translation
* CSS customization examples included in readme
