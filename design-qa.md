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
