# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-03-30

### Added
- Custom cyberpunk-themed login page replacing wp-login.php
- Animated HTML5 canvas particle network with connection lines and mouse interaction
- Pulse wave effects on the canvas background
- TOTP two-factor authentication (RFC 6238) compatible with Google Authenticator, Authy, etc.
- AES-256-CBC encryption for TOTP secrets at rest
- 10 one-time backup recovery codes per user (hashed storage)
- Google reCAPTCHA v3 (invisible) integration for bot protection
- Role-based 2FA enforcement with inline setup during login
- Admin settings page with 4 tabs: General, Security, Users, Help
- Custom login page logo uploader
- Configurable particle count for canvas animation
- reCAPTCHA score threshold configuration
- User 2FA management table with search, status badges, and reset capabilities
- Help documentation with accordion sections
- User profile 2FA setup, disable, and backup code management
- Rate limiting: 3 attempts per 2FA session, 10 per IP per hour
- All login flow templates: login, lost password, reset password, registration, check email
- Staggered entrance animations and micro-interactions on login page
- prefers-reduced-motion support for accessibility
- WCAG AA compliant colour contrast
- Full keyboard navigation support
- GitHub-based auto-updates via Releases API
- Red Frog Studio branding on login pages and admin settings
- WordPress coding standards compliance throughout
- Clean uninstall removing all plugin data
