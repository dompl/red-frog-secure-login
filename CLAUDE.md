# CLAUDE.md — Red Frog Secure Login Plugin

## Project Context

You are building a custom WordPress plugin called **Red Frog Secure Login** — a visually stunning custom login screen with animated canvas backgrounds and two-factor authentication. The full specification is in `PROMPT.md` at the project root. Read it thoroughly before doing anything.

This is a production plugin for Red Frog Studio (redfrogstudio.co.uk). It must be polished, secure, and WordPress-standards compliant.

---

## Required Skills

You MUST use the following skills throughout this project. Reference them actively — don't just acknowledge them, actually follow their workflows:

### Planning & Architecture

- **writing-plans** — Before writing ANY code, produce a detailed implementation plan. Break the plugin into phases (see Phased Build below). Each phase gets its own plan with acceptance criteria.
- **brainstorming** — Use this at the start to explore design decisions: TOTP implementation approach, canvas animation techniques, how to hook into wp-login.php without breaking core functionality, admin page component structure. Generate multiple options, evaluate trade-offs, then commit.
- **executing-plans** — Follow your written plans step by step. Track progress. Don't skip steps or deviate without updating the plan first.

### Development Workflow

- **subagent-driven-development** — This is a multi-file plugin with distinct concerns (login UI, canvas animation, 2FA crypto, admin settings, AJAX handlers). Dispatch subagents for parallel workstreams where it makes sense.
- **dispatching-parallel-agents** — Use parallel agents to build independent components simultaneously. For example: one agent on `canvas-animation.js`, another on `class-two-factor.php`, another on the admin settings template — these don't depend on each other and can be built in parallel.
- **using-superpowers** — Lean into your full capabilities. This plugin has real complexity: cryptographic TOTP, canvas physics, AJAX auth flows, WordPress hooks architecture. Use extended thinking, parallel execution, and thorough implementation.

### Code Quality

- **test-driven-development** — Write tests BEFORE implementation for critical paths: TOTP code generation/validation, 2FA login flow, settings save/load, nonce verification, rate limiting. Use PHPUnit for PHP, and manual test scripts for JS.
- **requesting-code-review** — After completing each phase, request a code review of your own work. Evaluate security, WordPress standards compliance, accessibility, and code quality.
- **receiving-code-review** — If I provide feedback or flag issues, process it systematically. Don't just patch — understand the root cause and fix it properly.
- **systematic-debugging** — When something doesn't work (and it will — wp-login.php customisation is fiddly), follow a structured debugging process. Reproduce, isolate, hypothesise, verify, fix. Don't guess-and-check randomly.

### Design & Frontend

- **frontend-design** — This is CRITICAL. The entire point of this plugin is that the login page looks incredible. Follow the frontend-design skill religiously. The canvas animation, the card design, the micro-interactions, the typography — all of it must be executed with exceptional attention to detail. No generic AI aesthetics. Reference the design direction in PROMPT.md and commit fully to the cyberpunk terminal aesthetic.

### Git & Branching

- **using-git-worktrees** — Use git worktrees if working on multiple phases or features simultaneously. Keep the main branch clean.
- **finishing-a-development-branch** — When each phase is complete, follow proper branch finishing: review, test, clean up, merge. Don't leave half-finished branches lying around.

### Meta / Skill Creation

- **writing-skills** — If during development you build something reusable (e.g. a WordPress TOTP implementation pattern, a canvas particle system pattern, a wp-login.php customisation pattern), extract it into a reusable skill for future projects.

---

## Phased Build Order

Do NOT try to build everything at once. Follow this sequence:

### Phase 1: Plugin Skeleton & Hooks
- Main plugin file with proper header
- Class autoloading / file includes
- All WordPress hooks registered (even if callbacks are empty stubs)
- Plugin activates and deactivates cleanly
- Settings page appears in admin menu (even if blank)
- **Gate**: Plugin activates without errors, settings page loads

### Phase 2: Custom Login Page — Structure
- Override wp-login.php appearance via hooks
- Load Tailwind CDN, Flowbite CDN, Google Fonts on login page
- Render the login card with proper HTML structure
- Apply dark theme CSS with all CSS variables
- Inputs, labels, button — all styled per PROMPT.md spec
- **Gate**: Login page renders with full dark theme, form submits normally, standard WP login works

### Phase 3: Canvas Animation
- Build `canvas-animation.js` — particle network with connection lines
- Mouse interaction (repulsion force field)
- Pulse wave effect
- Responsive resize handling
- `prefers-reduced-motion` support
- Performance capping (80 desktop / 40 mobile)
- **Gate**: Canvas renders behind login card, animation runs smoothly, respects reduced motion

### Phase 4: Login Page Polish
- Card entrance animation (fade + slide)
- Staggered reveal of form elements
- Input focus micro-interactions (icon colour shift, label colour shift)
- Button hover/active states with glow
- Error state styling (bar + shake animation)
- Remember Me toggle (Flowbite)
- Lost password link with tooltip
- Footer text
- Mobile responsive testing
- **Gate**: Login page is visually complete and matches PROMPT.md spec exactly

### Phase 5: TOTP Two-Factor Authentication — Backend
- TOTP class: secret generation (Base32), code generation, code validation
- User meta storage for secrets and 2FA status
- QR code URI generation (`otpauth://`)
- Transient-based pending session management
- Rate limiting logic
- **Gate**: TOTP codes generate correctly, validate against Google Authenticator, rate limiting works

### Phase 6: 2FA Login Flow — Frontend
- Intercept login form submission via AJAX
- Detect `rf_2fa_required` error response
- Transition from password to 6-digit TOTP input
- Auto-advancing digit inputs with auto-submit
- AJAX verification with proper error handling
- Redirect on success
- Attempt limiting (3 strikes)
- **Gate**: Full 2FA login flow works end-to-end with an authenticator app

### Phase 7: Admin Settings Page
- Settings page template with dark theme wrapper
- General Settings section (toggle, logo upload, particle count, accent colour)
- 2FA Settings section (role enforcement checkboxes, user table with status badges)
- Reset 2FA modal with confirmation
- Settings save via WordPress Settings API
- **Gate**: All settings save and load correctly, user table displays accurate 2FA status

### Phase 8: User Profile 2FA Setup
- 2FA setup section on user profile page
- QR code display in modal/accordion
- Manual secret key display
- Verification input to confirm setup
- Disable 2FA flow (requires current TOTP code)
- **Gate**: Users can set up and disable 2FA from their profile

### Phase 9: Security Hardening & Testing
- Nonce verification on all AJAX endpoints
- Input sanitisation audit
- Output escaping audit
- TOTP secret encryption at rest
- Rate limiting verification
- Session handling edge cases (expired transients, multiple tabs, back button)
- Cross-browser testing (Chrome, Firefox, Safari, Edge)
- **Gate**: Security review passes, no XSS/CSRF/injection vectors

### Phase 10: Final Polish & Documentation
- README.md with installation instructions, screenshots placeholder, feature list
- Code comments and DocBlocks
- Final visual QA against PROMPT.md spec
- Performance check (page load time, canvas FPS)
- Accessibility audit (labels, focus states, contrast, screen reader)
- **Gate**: Plugin is release-ready

---

## Key Technical Decisions

- **No build tools**: No npm, no Vite, no Webpack. Tailwind via CDN, Flowbite via CDN, custom JS as vanilla ES6.
- **No external PHP dependencies for TOTP**: Implement RFC 6238 directly — it's ~100 lines of PHP. No Composer packages for this.
- **Hook into wp-login.php, don't replace it**: Use `login_enqueue_scripts`, `login_head`, `login_form`, `login_footer`, and CSS to restyle. This preserves all core WP login functionality (password reset, registration, redirects, nonces, error handling).
- **AJAX login for 2FA step only**: The initial username/password submission uses the standard WP form POST. Only the 2FA verification step uses AJAX.
- **Settings API**: Use WordPress Settings API properly — `register_setting`, `add_settings_section`, `add_settings_field`. Don't just dump raw `update_option` calls.

---

## Code Style

- PHP: WordPress Coding Standards (tabs for indentation, Yoda conditions, proper DocBlocks)
- JS: ES6+, no jQuery dependency for custom code (though WP admin may load it). Use vanilla JS.
- CSS: Tailwind utilities first, custom CSS only for things Tailwind can't handle (complex selectors targeting WP's login markup, keyframe animations)
- All strings wrapped in `__()` or `esc_html__()` with text domain `rf-secure-login`

---

## File Naming Conventions

- PHP classes: `class-{name}.php` (e.g. `class-two-factor.php`)
- JS files: `kebab-case.js` (e.g. `canvas-animation.js`)
- CSS files: `kebab-case.css` (e.g. `login-style.css`)
- Templates: `{name}.php` in `/templates/` directory

---

## Important Reminders

1. **Read PROMPT.md first**. Every time. It has the full design spec, colour palette, typography, animation details, and component specifications.
2. **The design matters as much as the code**. This is a visual plugin. If the login page doesn't look stunning, the plugin has failed regardless of how clean the PHP is.
3. **Security is non-negotiable**. This is an authentication plugin. Every input must be sanitised, every output escaped, every AJAX call nonce-verified. No shortcuts.
4. **Test with a real authenticator app**. Don't just validate TOTP codes in unit tests. Actually scan the QR code with Google Authenticator or Authy and verify the flow works end-to-end.
5. **Don't break standard WordPress login**. Users who don't have 2FA enabled must be able to log in exactly as before, just with a prettier screen. Password reset must still work. Registration (if enabled) must still work.
