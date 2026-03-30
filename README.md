# Red Frog Secure Login

A visually stunning custom WordPress login screen with animated canvas backgrounds, TOTP two-factor authentication, backup codes, reCAPTCHA v3 integration, and automatic updates via GitHub.

Built by [Dom Kapelewski](https://redfrogstudio.co.uk) at **Red Frog Studio**.

---

## Features

- **Cyberpunk-themed login page** -- a dark, atmospheric login experience with neon accents, custom typography (Orbitron + JetBrains Mono), and polished micro-interactions.
- **Animated HTML5 canvas background** -- particle network with connection lines, mouse-reactive force field, periodic pulse waves, and full responsive support. Respects `prefers-reduced-motion`.
- **TOTP two-factor authentication** -- compatible with Google Authenticator, Authy, and any RFC 6238 compliant app. No external PHP dependencies.
- **Backup codes** -- one-time-use recovery codes for emergency access when an authenticator app is unavailable.
- **reCAPTCHA v3 integration** -- invisible bot protection on the login form with configurable score threshold.
- **Role-based 2FA enforcement** -- require specific WordPress roles (Administrator, Editor, etc.) to enable two-factor authentication.
- **Admin settings page** -- dark-themed settings panel under Settings > Secure Login with Flowbite UI components, media uploader, and per-user 2FA management table.
- **User profile 2FA setup** -- users can enable/disable 2FA and view backup codes directly from their WordPress profile page.
- **GitHub auto-updates** -- the plugin checks for new releases on GitHub and supports one-click updates from the WordPress dashboard. No manual downloads required after initial installation.
- **Fully self-contained** -- no build tools, no npm, no Composer. Tailwind CSS and Flowbite loaded via CDN. Works out of the box on any WordPress install.

## Requirements

- **WordPress** 6.0 or later
- **PHP** 8.0 or later
- **OpenSSL** PHP extension (for TOTP secret encryption)

## Installation

1. Download the latest release `.zip` file from the [GitHub releases page](https://github.com/dompl/red-frog-secure-login/releases).
2. In your WordPress admin, go to **Plugins > Add New > Upload Plugin**.
3. Select the downloaded `.zip` file and click **Install Now**.
4. Click **Activate**.

The custom login page is enabled by default. Visit your site's login page to see it in action.

## Auto-Updates

After initial installation, the plugin checks GitHub for new releases every 12 hours. When an update is available, it appears in the standard WordPress **Dashboard > Updates** screen and can be installed with a single click -- just like any plugin from the WordPress.org repository.

## Configuration

Navigate to **Settings > Secure Login** in the WordPress admin to configure the plugin.

### General Settings

- **Enable Custom Login Page** -- toggle the custom login screen on or off. When disabled, the default WordPress login page is used.
- **Login Page Logo** -- upload a custom logo for the login page (separate from your theme's logo). If not set, the site name is displayed in the Orbitron typeface.
- **Background Particle Count** -- control the number of animated particles on the login canvas (range: 20--120, default: 80).

The accent colour and other visual properties can be customised by overriding CSS variables (e.g. `--accent-primary`, `--bg-primary`) via a child theme or the WordPress Customizer's "Additional CSS" panel. Target the `body.rf-login-page` selector for specificity.

### reCAPTCHA v3

- **Enable reCAPTCHA** -- toggle reCAPTCHA v3 bot protection on the login form.
- **Site Key / Secret Key** -- enter your Google reCAPTCHA v3 credentials.
- **Score Threshold** -- minimum reCAPTCHA score to allow login (default: 0.5).

### Two-Factor Authentication

- **Enforce 2FA for Roles** -- select which WordPress roles must set up two-factor authentication.
- **User Management Table** -- view all users with their 2FA status (Active/Inactive) and reset 2FA for individual users.

## Setting Up 2FA (For Users)

1. Log in to WordPress and go to **Users > Your Profile**.
2. Scroll down to the **Two-Factor Authentication** section.
3. Click **Set Up Two-Factor Authentication**.
4. Scan the displayed QR code with your authenticator app (Google Authenticator, Authy, Microsoft Authenticator, etc.).
5. Enter the 6-digit code from your app to verify and activate 2FA.
6. Save the backup codes displayed -- these are single-use recovery codes in case you lose access to your authenticator app.

To disable 2FA, return to your profile and click **Disable 2FA**. You will need to enter a current TOTP code to confirm.

## Login Flow with 2FA

1. Enter your username and password as usual.
2. If 2FA is enabled on your account, the form transitions to a 6-digit code input.
3. Enter the code from your authenticator app (or a backup code).
4. The code auto-submits when all 6 digits are entered.
5. On success, you are redirected to the WordPress dashboard.

## Author

**Dom Kapelewski**
[Red Frog Studio](https://redfrogstudio.co.uk)

## License

This plugin is licensed under the [GNU General Public License v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

```
Red Frog Secure Login
Copyright (C) 2026 Dom Kapelewski / Red Frog Studio

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```
