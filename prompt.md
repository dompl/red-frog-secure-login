# PROMPT.md — Red Frog Custom Login Plugin

## Project Overview

Build a complete, production-ready WordPress plugin called **"Red Frog Secure Login"** (`red-frog-secure-login`). This plugin replaces the default WordPress login screen (`wp-login.php`) with a stunning, dark-themed custom login page featuring an animated HTML5 canvas background, and provides a backend settings page under **Settings → Secure Login** for managing two-factor authentication (2FA via TOTP).

The plugin must be self-contained — all CSS and JS are bundled within the plugin. No external build tools required (no Vite, no npm). Tailwind CSS is loaded via CDN (with `?plugins=forms` for form styling). Flowbite JS is loaded via CDN for interactive components (modals, toggles, tooltips, dropdowns). The plugin should work out of the box on any WordPress install.

---

## Design Direction — CRITICAL

This is **NOT** a generic login page. This must be **visually unforgettable**. Follow these aesthetic principles:

### Theme: "Cyberpunk Command Terminal"

A dark, atmospheric login experience that feels like accessing a secure system. Think: sci-fi command centres, hacking interfaces from movies, neon-accented dashboards.

### Colour Palette (CSS Variables)

```css
:root {
  --bg-primary: #0a0a0f;        /* Near-black base */
  --bg-secondary: #12121a;      /* Card/panel background */
  --bg-tertiary: #1a1a2e;       /* Subtle hover/active states */
  --accent-primary: #00ff88;    /* Neon green — primary accent */
  --accent-secondary: #00d4ff;  /* Cyan blue — secondary accent */
  --accent-warn: #ff3366;       /* Hot pink — errors/warnings */
  --accent-glow: #00ff8833;     /* Green glow (translucent) */
  --text-primary: #e0e0e0;      /* Main text */
  --text-muted: #6b7280;        /* Muted/secondary text */
  --text-bright: #ffffff;       /* Headings, active elements */
  --border-subtle: #1f2937;     /* Subtle borders */
  --border-focus: #00ff88;      /* Focus ring colour */
}
```

### Typography

- **Headings / Logo**: Use Google Font `"Orbitron"` (import via `<link>`) — geometric, futuristic, techy.
- **Body / Inputs / Labels**: Use Google Font `"JetBrains Mono"` — monospaced, feels like a terminal.
- **Fallback stack**: `"JetBrains Mono", "Fira Code", "Courier New", monospace`

### Animated Canvas Background — MANDATORY

The login page must have a **full-screen HTML5 `<canvas>` element** behind everything (z-index: 0) with the following animation:

1. **Particle Network**: Dozens of small glowing dots (colour: `--accent-primary` at ~30% opacity) floating slowly in random directions across the screen.
2. **Connection Lines**: When two particles are within 150px of each other, draw a thin line between them (colour: `--accent-secondary` at ~15% opacity). Lines fade based on distance.
3. **Mouse Interaction**: Particles within 200px of the mouse cursor should gently repel away from it, creating a "force field" effect.
4. **Pulse Effect**: Every 5 seconds, a subtle radial pulse wave emanates from a random point on the canvas (a ring that expands and fades).
5. **Performance**: Use `requestAnimationFrame`. Cap at ~80 particles on desktop, ~40 on mobile. Detect `prefers-reduced-motion` and disable animation if set.

The canvas must be responsive and resize with the window (`ResizeObserver` or `window.resize`).

### Login Card Design

The login form sits centred on top of the canvas in a card with:

- **Background**: `--bg-secondary` with `backdrop-filter: blur(20px)` and slight transparency (`rgba(18, 18, 26, 0.85)`).
- **Border**: 1px solid `--border-subtle` with a subtle `box-shadow: 0 0 40px rgba(0, 255, 136, 0.05)`.
- **Border glow on hover**: The card border subtly transitions to `--accent-primary` at ~30% opacity on hover.
- **Width**: `max-w-md` (Tailwind), centred vertically and horizontally.
- **Rounded corners**: `rounded-2xl`.
- **Padding**: Generous — `p-10` minimum.

### Logo Area

- At the top of the card, display the site's custom logo (via `get_custom_logo()` or `bloginfo('name')` fallback).
- If no custom logo is set, display the site name in `Orbitron` font, `text-2xl`, colour `--accent-primary`, with a subtle `text-shadow: 0 0 20px var(--accent-glow)`.
- Below the logo, a tagline: "Secure Access" in `JetBrains Mono`, `text-sm`, colour `--text-muted`, with `letter-spacing: 0.2em` and `text-transform: uppercase`.

### Form Inputs

- **Style**: Dark inputs with `bg-[--bg-tertiary]` background, `border border-[--border-subtle]`, `text-[--text-primary]`.
- **Focus state**: `border-[--accent-primary]` with `ring-2 ring-[--accent-glow]` and a subtle green glow `box-shadow`.
- **Labels**: Above each input, in `JetBrains Mono`, `text-xs`, `uppercase`, `tracking-widest`, colour `--text-muted`.
- **Placeholder text**: colour `--text-muted` at 50% opacity.
- **Input icons**: Inline SVG icons (user icon for username, lock icon for password) inside the input using Tailwind's `relative`/`absolute` positioning. Icons in `--text-muted`, transitioning to `--accent-primary` on focus.
- **Inputs must have smooth transitions** on all state changes (`transition-all duration-300`).

### Login Button

- **Background**: Gradient from `--accent-primary` to `--accent-secondary` (left to right).
- **Text**: `--bg-primary` (dark text on bright button), `font-bold`, `uppercase`, `tracking-wider`.
- **Hover**: Slight scale (`scale-[1.02]`), intensified glow shadow (`box-shadow: 0 0 30px var(--accent-glow)`).
- **Active/Click**: Brief scale-down (`scale-[0.98]`).
- **Full width**, `rounded-xl`, `py-3`.
- **Loading state**: When form submits, button text changes to a pulsing "Authenticating..." with a spinning SVG loader.

### 2FA Code Input (Conditional)

When 2FA is enabled for a user and they submit valid credentials:

- The password field fades out (CSS transition) and is replaced by a **6-digit TOTP code input**.
- Use 6 individual `<input>` fields side by side (each `w-12 h-14 text-center text-2xl`), auto-advancing focus on input.
- Styled identically to other inputs but with larger text.
- Auto-submit when all 6 digits are entered.
- Invalid code shows an error with a brief shake animation on the input group.

### Error & Success States

- **Errors**: Displayed above the form in a subtle bar with `bg-[--accent-warn]/10`, `border-l-4 border-[--accent-warn]`, `text-[--accent-warn]`, with a fade-in animation.
- **Success**: Brief flash of `--accent-primary` glow on the card border before redirect.

### Additional Login Page Details

- "Remember Me" checkbox: Styled with Flowbite's toggle component, in green accent.
- "Lost your password?" link: Below the button, `text-sm`, `text-[--text-muted]`, hover transitions to `--accent-primary`. Use Flowbite tooltip on hover saying "Reset via email".
- Footer: At the very bottom of the page (outside the card), small text "Protected by Red Frog Secure Login" in `--text-muted`, `text-xs`.

### Animations & Micro-interactions

- **Card entrance**: On page load, the card fades in and slides up slightly (`opacity 0→1`, `translateY 20px→0`) over 600ms with `ease-out`.
- **Input focus**: Labels shift colour from muted to `--accent-primary`.
- **Typing indicator**: A subtle pulsing cursor-like dot appears at the end of the active input's label.
- **Shake on error**: The card shakes horizontally (CSS `@keyframes shake`) when login fails.
- **Staggered reveal**: Each form element (logo, tagline, username, password, button) appears with a staggered delay (100ms increments).

---

## Plugin Architecture

```
red-frog-secure-login/
├── red-frog-secure-login.php        # Main plugin file (plugin header, hooks, init)
├── includes/
│   ├── class-login-page.php         # Handles custom login page rendering
│   ├── class-two-factor.php         # 2FA logic (TOTP generation, validation, user meta)
│   ├── class-settings.php           # Admin settings page
│   └── class-ajax-handler.php       # AJAX endpoints for login + 2FA verification
├── assets/
│   ├── css/
│   │   └── login-style.css          # Custom CSS for login page (supplements Tailwind)
│   ├── js/
│   │   ├── canvas-animation.js      # Particle network canvas animation
│   │   ├── login-form.js            # Login form behaviour (AJAX submit, 2FA flow, validation)
│   │   └── admin-settings.js        # Admin settings page interactivity
│   └── images/
│       └── (placeholder for logo fallback if needed)
├── templates/
│   ├── login-page.php               # Full HTML template for custom login page
│   └── admin-settings-page.php      # Admin settings page template
├── vendor/
│   └── (TOTP library files — see below)
└── README.md
```

---

## Technical Specifications

### Main Plugin File (`red-frog-secure-login.php`)

**Plugin Header:**
```php
/**
 * Plugin Name: Red Frog Secure Login
 * Plugin URI: https://redfrogstudio.co.uk
 * Description: A stunning custom login screen with animated backgrounds and two-factor authentication.
 * Version: 1.0.0
 * Author: Dom Kapelewski
 * Author URI: https://redfrogstudio.co.uk
 * License: GPL v2 or later
 * Text Domain: rf-secure-login
 */
```

**Hooks to register:**

- `login_enqueue_scripts` — Enqueue Tailwind CDN, Flowbite CDN, Google Fonts, custom CSS, canvas JS, login form JS.
- `login_head` — Inject CSS variables and any inline styles.
- `login_headerurl` — Return site URL.
- `login_headertext` — Return site name.
- `login_body_class` — Add custom body classes.
- `login_message` — Custom messages.
- `login_form` — Inject 2FA hidden fields and containers.
- `login_footer` — Inject canvas element, footer text, and JS initialisation.
- `wp_authenticate` or `authenticate` filter — Intercept login to check 2FA.
- `admin_menu` — Register settings page under Settings.
- `admin_init` — Register settings.
- `wp_ajax_rf_verify_2fa` — AJAX endpoint for 2FA code verification.
- `wp_ajax_nopriv_rf_verify_2fa` — Same for non-logged-in users.

### Custom Login Page (`class-login-page.php`)

Override the default `wp-login.php` appearance using the hooks above. Do **NOT** create a completely custom page template that replaces `wp-login.php` — instead, heavily restyle it using WordPress's login customisation hooks so that all core login functionality (password reset, registration if enabled, redirects, nonces) continues to work.

Key approach:
- Use `login_enqueue_scripts` to load all assets.
- Use `login_head` to output the full `<style>` block that overhauls the login page appearance.
- Use `login_footer` to inject the `<canvas>` element and initialise the particle animation JS.
- Use `login_form` to inject the hidden 2FA container (initially hidden, shown via JS after successful credential check).
- Apply Tailwind utility classes where possible via inline HTML in the hooks, and use the custom CSS file for complex selectors targeting `#login`, `#loginform`, `.login h1`, etc.

### Two-Factor Authentication (`class-two-factor.php`)

Implement TOTP (Time-based One-Time Password) compatible with Google Authenticator, Authy, etc.

**TOTP Implementation (No External Dependencies):**
- Implement a lightweight TOTP class directly in PHP (RFC 6238). This is straightforward:
  - Generate a 16-character Base32-encoded secret.
  - Use `hash_hmac('sha1', ...)` with the secret and current time step (30-second intervals).
  - Extract a 6-digit code from the HMAC result using dynamic truncation.
  - Allow a ±1 time step window for clock skew tolerance.
- Store the user's TOTP secret in `user_meta` (`_rf_2fa_secret`).
- Store 2FA enabled status in `user_meta` (`_rf_2fa_enabled` → `yes`/`no`).
- Generate a `otpauth://` URI for QR code generation.

**QR Code for Setup:**
- Use Google Charts API to generate QR code image: `https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl={otpauth_uri}` (or bundle a simple PHP QR code library if preferred — but the Google Charts approach is simpler and keeps the plugin lightweight).

**Login Flow with 2FA:**

1. User submits username + password via the standard WordPress login form.
2. The `authenticate` filter validates credentials normally.
3. If credentials are valid AND the user has 2FA enabled:
   - Do **not** complete authentication yet.
   - Store a transient (`_rf_2fa_pending_{hash}`) with the user ID, valid for 5 minutes.
   - Return a `WP_Error` with a custom code (`rf_2fa_required`) and a message that JS intercepts.
   - The JS detects this specific error, hides the password field, and reveals the 2FA input.
4. User enters the 6-digit TOTP code.
5. JS sends an AJAX request to `wp_ajax_nopriv_rf_verify_2fa` with the code and the pending session hash.
6. PHP validates the code against the user's stored secret.
7. If valid: programmatically log the user in (`wp_set_auth_cookie()`, `wp_set_current_user()`) and return the redirect URL via JSON.
8. If invalid: return an error. Allow up to 3 attempts before requiring a fresh login.

### Admin Settings Page (`class-settings.php`)

Register under **Settings → Secure Login** (`add_options_page`).

**Settings Page Design:**

Use Tailwind CDN + Flowbite CDN on the admin page too (scoped within a wrapper div `.rf-settings-wrap` to avoid conflicts with WP admin styles).

The settings page should include:

#### Section 1: General Settings
- **Enable Custom Login Page**: Flowbite toggle switch. When off, the default WP login is used.
- **Login Page Logo**: Media uploader button (using `wp.media`) to select a custom logo specifically for the login page (separate from the theme's custom logo). Preview the selected image.
- **Background Particle Count**: Number input (range 20–120, default 80). Controls how many particles appear on the canvas.
- **Accent Colour**: Colour picker (`wp-color-picker`) for `--accent-primary`. Defaults to `#00ff88`.

#### Section 2: Two-Factor Authentication
- **Heading**: "Two-Factor Authentication (TOTP)" with a description: "Add an extra layer of security by requiring a one-time code from an authenticator app."
- **Enforce 2FA for roles**: Multi-select checkboxes for WordPress roles (Administrator, Editor, Author, etc.). When checked, users with those roles will be required to set up 2FA on their next login.
- **Per-user 2FA setup**: Below the role enforcement, show a table of all users who have 2FA enabled/disabled:
  - Columns: Username, Email, Role, 2FA Status (green badge "Active" / grey badge "Inactive"), Actions (Reset 2FA button).
  - Use Flowbite's table component with dark styling.
  - "Reset 2FA" removes the user's secret and disables their 2FA (with a Flowbite confirmation modal).

#### Section 3: User's Own 2FA Setup (Profile Page)
- Add a section to each user's **Profile** page (`show_user_profile` and `edit_user_profile` hooks).
- If 2FA is not yet enabled:
  - Show a "Set Up Two-Factor Authentication" button.
  - Clicking it reveals (via Flowbite modal or accordion): the QR code, the manual secret key (displayed in monospace groups of 4), and an input to enter a verification code to confirm setup.
  - User scans QR with their authenticator app, enters the code, and if valid, 2FA is activated.
- If 2FA is already enabled:
  - Show status: "Two-Factor Authentication is **active**" with a green indicator.
  - Show a "Disable 2FA" button (requires entering a current TOTP code to disable, for security).

#### Admin Page Styling
- The admin settings page should match the dark cyberpunk aesthetic of the login page as much as possible within the WP admin context.
- Wrap everything in a container with dark background (`--bg-primary`), rounded corners, and the same colour palette.
- Use Flowbite components: toggles, modals, tables, badges, tooltips.
- Section headings in `Orbitron` font.
- Body text in the default WP admin font (don't override globally, only within `.rf-settings-wrap`).

---

## Asset Loading

### Login Page Assets (enqueued via `login_enqueue_scripts`):
```
Tailwind CSS CDN:    https://cdn.tailwindcss.com?plugins=forms
Flowbite CSS CDN:    https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css
Flowbite JS CDN:     https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js
Google Fonts:        https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=JetBrains+Mono:wght@300;400;500;700&display=swap
Custom CSS:          assets/css/login-style.css
Canvas JS:           assets/js/canvas-animation.js
Login Form JS:       assets/js/login-form.js
```

### Admin Settings Page Assets (enqueued only on the settings page):
```
Tailwind CSS CDN:    (same as above)
Flowbite CSS/JS CDN: (same as above)
Google Fonts:        (same as above)
WP Color Picker:     wp-color-picker (core WP script)
WP Media:            wp.media (core WP script)
Custom Admin JS:     assets/js/admin-settings.js
```

---

## Security Requirements

- All AJAX requests must include and verify WordPress nonces.
- TOTP secrets are stored encrypted in user_meta (use `wp_hash()` or `openssl_encrypt` with a salt derived from `AUTH_KEY`).
- Rate limit 2FA verification attempts: max 3 per pending session, max 10 per IP per hour (stored in transients).
- The 2FA pending session transient should be deleted immediately after successful verification or after 3 failed attempts.
- Sanitise and validate all inputs on the settings page.
- Use `current_user_can('manage_options')` for settings page access.
- Escape all output with `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()` as appropriate.

---

## WordPress Coding Standards

- Follow WordPress PHP Coding Standards.
- Use proper text domain (`rf-secure-login`) for all translatable strings.
- Prefix all functions, classes, hooks, and options with `rf_` or `RF_`.
- Use `wp_enqueue_script` / `wp_enqueue_style` for all asset loading — no hardcoded `<script>` or `<link>` tags.
- Use `wp_localize_script` to pass AJAX URLs, nonces, and configuration to JS.
- Register settings with the Settings API (`register_setting`, `add_settings_section`, `add_settings_field`).
- All database interactions via WordPress APIs (`get_option`, `update_option`, `get_user_meta`, `update_user_meta`, `set_transient`, `get_transient`).

---

## Responsive Design

- Login page must look perfect on mobile (320px+), tablet (768px+), and desktop (1024px+).
- Canvas animation should reduce particle count on mobile (check `window.innerWidth < 768`).
- Login card should be `w-full` on mobile with `px-6` padding, `max-w-md` on desktop.
- 2FA digit inputs should scale down slightly on mobile (`w-10 h-12` instead of `w-12 h-14`).

---

## Accessibility

- All form inputs must have associated `<label>` elements.
- Focus states must be clearly visible (the green glow ring).
- Canvas animation must respect `prefers-reduced-motion: reduce`.
- Error messages must be associated with inputs via `aria-describedby`.
- The 2FA input group must have `role="group"` and an `aria-label`.
- Colour contrast: all text must meet WCAG AA against its background.

---

## Summary of Deliverables

1. Complete plugin directory `red-frog-secure-login/` with all files listed in the architecture above.
2. The custom login page must be visually stunning with the animated particle canvas.
3. 2FA (TOTP) must be fully functional — setup via profile page, enforcement via settings, validation on login.
4. Admin settings page must be clean, dark-themed, and use Flowbite components.
5. All code must be secure, properly sanitised, and follow WordPress standards.
6. Plugin must work immediately after activation with sensible defaults (custom login enabled, no 2FA enforced by default).
