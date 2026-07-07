---
name: Stark Industrial
colors:
  surface: '#fbf9f8'
  surface-dim: '#dbdad9'
  surface-bright: '#fbf9f8'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f5f3f3'
  surface-container: '#efeded'
  surface-container-high: '#e9e8e7'
  surface-container-highest: '#e4e2e2'
  on-surface: '#1b1c1c'
  on-surface-variant: '#3f4942'
  inverse-surface: '#303031'
  inverse-on-surface: '#f2f0f0'
  outline: '#6f7a71'
  outline-variant: '#bec9bf'
  surface-tint: '#126c45'
  primary: '#005131'
  on-primary: '#ffffff'
  primary-container: '#106b44'
  on-primary-container: '#96e9b7'
  inverse-primary: '#86d7a7'
  secondary: '#5f5e5e'
  on-secondary: '#ffffff'
  secondary-container: '#e2dfde'
  on-secondary-container: '#636262'
  tertiary: '#454647'
  on-tertiary: '#ffffff'
  tertiary-container: '#5c5e5e'
  on-tertiary-container: '#d7d7d7'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#a1f4c2'
  primary-fixed-dim: '#86d7a7'
  on-primary-fixed: '#002111'
  on-primary-fixed-variant: '#005232'
  secondary-fixed: '#e5e2e1'
  secondary-fixed-dim: '#c8c6c5'
  on-secondary-fixed: '#1c1b1b'
  on-secondary-fixed-variant: '#474746'
  tertiary-fixed: '#e2e2e2'
  tertiary-fixed-dim: '#c6c6c7'
  on-tertiary-fixed: '#1a1c1c'
  on-tertiary-fixed-variant: '#454747'
  background: '#fbf9f8'
  on-background: '#1b1c1c'
  surface-variant: '#e4e2e2'
typography:
  display-lg:
    fontFamily: Bebas Neue
    fontSize: 64px
    fontWeight: '400'
    lineHeight: '1.0'
    letterSpacing: 0.02em
  headline-lg:
    fontFamily: Bebas Neue
    fontSize: 48px
    fontWeight: '400'
    lineHeight: '1.1'
  headline-md:
    fontFamily: Bebas Neue
    fontSize: 32px
    fontWeight: '400'
    lineHeight: '1.1'
  headline-sm:
    fontFamily: Work Sans
    fontSize: 20px
    fontWeight: '700'
    lineHeight: '1.4'
  body-lg:
    fontFamily: Work Sans
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Work Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.5'
  label-caps:
    fontFamily: Work Sans
    fontSize: 12px
    fontWeight: '700'
    lineHeight: '1.2'
    letterSpacing: 0.05em
spacing:
  unit: 4px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 64px
  grid-pattern-size: 32px
---

## Brand & Style

The design system is rooted in the "Stark Industrial" aesthetic, projecting a persona of engineering precision, structural integrity, and B2B reliability. It is designed for industrial sectors and circular economy platforms where efficiency and transparency are paramount.

The visual language is a blend of **Brutalism** and **Modern Corporate**, utilizing rigid grid structures, high-contrast monochrome photography with dark overlays, and a strict 1px border logic. The emotional response should be one of "Uncompromising Professionalism"—clean, direct, and utilitarian. Whitespace is used not for luxury, but for clarity and focus.

## Colors

The palette is anchored by a deep **Emerald Green** (#106B44), representing sustainability and growth within an industrial context.

- **Primary:** Emerald Green is used for primary actions, success states, and progress indicators.
- **Secondary:** Charcoal Black (#1A1A1A) provides high-contrast grounding for headings and dark-mode containers.
- **Backgrounds:** A tiered light-gray system. The main background is a Stark White, with subtle Light Gray (#F4F4F4) used for secondary sections or grid backgrounds.
- **Grid Lines:** A faint Charcoal at 5-10% opacity is used to render technical grid patterns over background layers.

## Typography

Typography establishes a clear hierarchy between industrial "Signage" (Headings) and technical "Documentation" (Body).

- **Headlines:** Use **Bebas Neue**. Its condensed, vertical nature mimics architectural blueprints and industrial signage. Use it for all major page titles and section headers.
- **Body & UI:** Use **Work Sans**. Its clean, neutral, and slightly technical feel ensures legibility in dense data and forms.
- **Labels:** Small caps with increased letter spacing should be used for form labels and metadata to maintain a disciplined, "engineered" look.

## Layout & Spacing

This design system uses a **Rigid Fluid Grid**. While content stretches, it is strictly governed by a 12-column layout on desktop and a 4-column layout on mobile.

- **Grid Background:** A subtle 32px square grid pattern is applied to main container backgrounds to reinforce the industrial theme.
- **Whitespace:** Use generous vertical margins (minimum 80px between major sections) to prevent the high-contrast elements from feeling cluttered.
- **Borders:** A 1px solid border is the primary separator. Avoid drop shadows; use borders to define structural depth.

## Elevation & Depth

Hierarchy is achieved through **Tonal Layering** and **High-Contrast Overlays** rather than shadows.

- **Flat Depth:** All elements are flush with the surface. 
- **Dark Overlays:** Images of industrial architecture must use a 40-60% black overlay to ensure white text remains legible.
- **Contrast Tiers:** Use Charcoal (#1A1A1A) blocks to pull primary content forward against a Light Gray or White background.
- **Focus States:** Use a 1px Emerald Green border for active inputs. Never use blurs or soft shadows for focus.

## Shapes

The shape language is strictly **Rectilinear**. All corners are 90 degrees (0px radius). This applies to buttons, input fields, cards, and image containers. 

The lack of roundedness is a core brand pillar, reflecting the hard edges of industrial materials like steel and concrete.

## Components

### Buttons
- **Primary:** Solid Emerald Green background, Stark White text in all-caps Work Sans Bold. Include a right-facing arrow icon (→) for directional flow.
- **Secondary:** Transparent background with 1px Charcoal border.
- **Shape:** Sharp corners, height: 48px or 56px.

### Input Fields
- **Default:** 1px Light Gray border, Stark White background.
- **Active:** 1px Emerald Green border.
- **Labels:** Positioned above the field in `label-caps` typography.

### Chips & Tags
- **Style:** Small, sharp rectangles. Use Emerald Green background with white text for "Verified" or "Active" states. Use Charcoal for categories.

### Progress Indicators
- Use a segmented step-system (1, 2, 3) connected by a solid 2px line. Active steps are Emerald Green squares; inactive steps are Charcoal or Gray.

### Cards
- No shadows. Use a 1px border (#E5E5E5) and high-contrast typography. Images within cards should maintain the sharp 90-degree corners.