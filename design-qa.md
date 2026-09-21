# Design QA — Kids Optics Hero

## Evidence

- Source visual truth: Figma file `HMGem7rmue4gVYy0HSu1io`, nodes `6546:15259` (desktop) and `6577:14838` (mobile).
- Source screenshots: Figma MCP captures, 1920 × 492 and 390 × 600 pixels.
- Implementation: browser-rendered local preview at `http://127.0.0.1:8001/__preview-kids-optics-hero`, captured in the Codex in-app browser during this QA run; the temporary route data was removed afterward.
- Browser viewports: 1920 × 900, 1024 × 900, 768 × 900 and 390 × 900 CSS pixels. The hero measured 1920 × 492, 450, 440 and 390 × 600 CSS pixels respectively.
- Density normalization: source and implementation were compared at their matching 1× CSS sizes; no resampling was required.
- State: default hero state, existing site header visible, booking modal closed for visual comparison.

## Full-view comparison

- Fonts and typography: existing Gilroy webfont, weights, sizes, line heights and wrapping match the Figma definitions.
- Spacing and layout: desktop title `(336, 127)`, description top `220`, CTA top `283`; mobile title top `60`, CTA width `358` and bottom offset `32`.
- Colors and tokens: existing heading token `#1F3462` and shared blue-gradient button component are used.
- Image quality: the supplied desktop/mobile compositions are preserved at 2880 × 738 and 585 × 900; the browser selected AVIF, with WebP and JPEG fallbacks available.
- Copy: title, description and CTA match the supplied design and remain editable in the block admin form.

## Focused comparison

Focused geometry and image-selection checks were run in the browser because typography, CTA placement and responsive asset selection are the fidelity-critical details. The desktop loaded `desktop.avif`; the mobile viewport loaded `mobile.avif`.

## Findings

- No remaining P0/P1/P2 mismatches.

## Comparison history

- Initial desktop pass: `[P2]` the title wrapped because the content wrapper inherited the description width of 624 px.
- Fix: separated the 695 px title width from the 624 px description width.
- Post-fix evidence: title renders on one line at `(336, 127)`; remaining measured positions match Figma.
- Mobile pass: no actionable mismatch found.

## Interaction and runtime checks

- CTA is visible, enabled and opens the existing booking modal.
- Browser console errors: none.
- Responsive asset selection: passed.
- Tablet layout: 768–1279 px uses the desktop composition with a 40% horizontal focus, smaller typography and a compact hero height; the full desktop composition begins at 1280 px.

## Implementation checklist

- [x] Desktop layout matches the selected Figma node.
- [x] Mobile layout matches the selected Figma node.
- [x] Tablet layout keeps text clear of the children at 768 px and 1024 px.
- [x] CTA uses the existing booking flow.
- [x] Editable fields are exposed through the new block definition.
- [x] Above-the-fold images are eager, high priority and size-stable.

final result: passed

---

# Design QA — Kids Optics Frame Catalog

## Evidence

- Source visual truth: Figma file `HMGem7rmue4gVYy0HSu1io`, nodes `6565:13348` (desktop) and `6578:15778` (mobile).
- Source screenshots: Figma MCP captures for the selected nodes. The source and rendered catalog regions were compared at matching content widths; browser chrome, the existing header/footer and the local debug toolbar were excluded from fidelity judgement.
- Implementation: local temporary page `http://127.0.0.1:8001/__preview-kids-optics-catalog`; it contained only the new catalog block and was removed after the QA run.
- Implementation screenshots: `.playwright-cli/page-2026-09-21T07-29-32-728Z.png` (1920 × 1200), `.playwright-cli/page-2026-09-21T07-29-34-742Z.png` (390 × 1000), `.playwright-cli/page-2026-09-21T07-29-37-608Z.png` (1024 × 1100).
- Density normalization: all browser captures use CSS-scale PNG at `deviceScaleFactor: 1`; the Figma source was judged at the same CSS viewport widths. No resampling was used.
- State: default catalog state after choosing Moscow in the site city dialog; no filter is selected and no modal overlaps the catalog.

## Full-view comparison

- Fonts and typography: the existing Gilroy family and dark-blue heading token are used. The title is 34 px on desktop and 28 px on mobile; cards preserve the intended heading/body hierarchy without truncation.
- Spacing and layout: desktop uses the Figma 3 × 400 px card grid and 16 px gaps. Mobile changes to one 358 px-wide card column; the requested tablet state uses a two-column grid at 1024 px. Filter groups do not overflow at any checked width.
- Responsive first page: the catalog renders four cards after a mobile page load and six from 768 px upward. The remaining cards stay inert until Vue appends the next batch; this follows the mobile design density without downloading deferred card images early.
- Colors and visual tokens: the pale blue section background, white cards/filters, dark-blue text, coloured chips and gender-tag borders map to the source palette. No substitute or generic visual treatment was introduced.
- Image quality: the supplied filter illustrations and product images are used directly. Static fallback cards provide AVIF, WebP and JPEG; Curator uploads receive responsive 400/800 px WebP/JPEG Glide sources. Images have fixed dimensions/aspect ratios, preventing layout shift.
- Copy and content: title, labels, card placeholders and desktop/mobile "show more" copy match the design and are editable from the block form.

## Focused comparison

- Filter row and first card were checked closely at 1920 px: 49 px pill controls, the illustration crop, 400 × 313 image ratio, card corner radius and metadata alignment match the Figma composition.
- The mobile source requires narrow age controls and two full-width gender controls; the 390 px render keeps all text readable and preserves 44 px-or-larger tap targets. A separate close-up was not needed because those elements are fully visible in the mobile evidence capture.

## Findings

- No remaining P0/P1/P2 mismatches.
- [P3] The local Laravel debug bar appears in the evidence captures. It is a development-only existing tool and is not part of the rendered site or this component; no product-code change is needed.

## Comparison history

- Initial desktop implementation had an overly broad card/grid container, which left the filters visually misaligned with the 400 px Figma card tracks.
- Fix: constrained the catalog to 1248 px and set the desktop grid to three explicit 400 px tracks, keeping 16 px gaps.
- Post-fix evidence: `.playwright-cli/page-2026-09-21T07-29-32-728Z.png` shows aligned filters and cards; the 390 px and 1024 px evidence confirm the single- and two-column responsive states.

## Interaction and runtime checks

- Catalog controls are semantic buttons and are keyboard reachable; their `data-frame-catalog-*` hooks are ready for the later Vue filtering implementation.
- Desktop load-more verification on `/detskaia-optika`: 12 test cards are configured; cards 1–6 are present before interaction, and clicking `Показать ещё (6)` appends cards 7–12 without navigation, then removes the button. The initially hidden image markup is kept in an inert textarea, so those images are not requested before the click.
- Responsive count verification: after an actual page reload at 390 px, 4 cards are present and 8 await loading; after a reload at 1280 px, 6 cards are present and 6 await loading. The filter-control border opacity was raised from 6% to 12% to retain a visible edge against the section background.
- Filter verification: selecting `7-12 лет` leaves the three matching test cards; combining it with `Для мальчиков` leaves the two matching boy/unisex cards. A repeated click clears each filter, selected controls expose `aria-pressed="true"`, and every filter change resets the visible-card limit before load-more is offered again.
- Image `alt` text, lazy decoding/loading for below-the-fold cards and fixed image geometry are present.
- Browser console: no component errors. Four `ERR_CERT_AUTHORITY_INVALID` events belong to the local browser's external Yandex metrics requests, not to the catalog or its assets.

## Implementation checklist

- [x] Desktop three-column catalog matches the selected Figma node.
- [x] Mobile single-column catalog matches the selected Figma node.
- [x] Tablet two-column intermediate layout was checked at 1024 px.
- [x] Card imagery, gender illustrations and filters use the supplied assets.
- [x] Admin form accepts Curator images and content metadata.
- [x] Responsive image delivery and CLS prevention are implemented.

final result: passed
