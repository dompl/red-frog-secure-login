/**
 * Login Form Behaviour
 *
 * Handles AJAX login submission, 2FA digit input management,
 * 2FA setup flow, backup code entry, and password reset form
 * validation. Designed to work with the custom login templates
 * rendered by RF_Login_Page.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */
(function () {
	'use strict';

	/* ---------------------------------------------------------------
	 * Configuration
	 * ------------------------------------------------------------- */

	var config = typeof rfSecureLogin !== 'undefined' ? rfSecureLogin : {};
	var i18n   = config.i18n || {};

	/* ---------------------------------------------------------------
	 * Element References
	 * ------------------------------------------------------------- */

	var form            = document.getElementById( 'rf-login-form' );
	var card            = document.getElementById( 'rf-login-card' );
	var errorContainer  = document.getElementById( 'rf-error-container' );
	var submitBtn       = document.getElementById( 'rf-submit-btn' );
	var submitBtnText   = document.getElementById( 'rf-submit-btn-text' );
	var submitBtnLoader = document.getElementById( 'rf-submit-btn-loader' );
	var passwordGroup   = document.getElementById( 'rf-password-group' );
	var rememberRow     = document.getElementById( 'rf-remember-row' );
	var tfaGroup        = document.getElementById( 'rf-2fa-group' );
	var tfaTokenInput   = document.getElementById( 'rf-2fa-token' );
	var tfaDigits       = document.querySelectorAll( '.rf-2fa-digit' );
	var tfaSetupWrap    = document.getElementById( 'rf-2fa-setup' );
	var backupGroup     = document.getElementById( 'rf-backup-group' );
	var backupInput     = document.getElementById( 'rf-backup-code' );
	var backupToggle    = document.getElementById( 'rf-backup-toggle' );
	var authToggle      = document.getElementById( 'rf-auth-toggle' );

	/* Track 2FA verification attempts */
	var tfaAttempts = 0;
	var MAX_TFA_ATTEMPTS = 3;

	/* ---------------------------------------------------------------
	 * Helper: Escape HTML
	 * ------------------------------------------------------------- */

	/**
	 * Escape HTML entities in a string to prevent XSS.
	 *
	 * @param {string} text Raw text.
	 * @return {string} Escaped HTML string.
	 */
	function escapeHtml( text ) {
		var div = document.createElement( 'div' );
		div.textContent = text;
		return div.innerHTML;
	}

	/* ---------------------------------------------------------------
	 * Helper: Loading State
	 * ------------------------------------------------------------- */

	/**
	 * Toggle the loading state of the submit button.
	 *
	 * When loading, the button is disabled, its text label is hidden,
	 * and the spinner SVG is shown. When not loading, the reverse.
	 *
	 * @param {boolean} loading Whether to enter loading state.
	 * @param {string}  [text]  Optional button text to display while loading.
	 */
	function setLoading( loading, text ) {
		if ( ! submitBtn ) {
			return;
		}

		submitBtn.disabled = loading;

		if ( submitBtnText ) {
			if ( loading && text ) {
				submitBtnText.textContent = text;
			}
			submitBtnText.style.opacity = loading ? '0' : '1';
		}

		if ( submitBtnLoader ) {
			submitBtnLoader.classList.toggle( 'hidden', ! loading );
		}

		if ( loading ) {
			submitBtn.classList.add( 'rf-btn-loading' );
		} else {
			submitBtn.classList.remove( 'rf-btn-loading' );
			if ( submitBtnText ) {
				submitBtnText.textContent = submitBtnText.getAttribute( 'data-default' ) || i18n.signIn || 'Sign In';
			}
		}
	}

	/* ---------------------------------------------------------------
	 * Helper: Error Display
	 * ------------------------------------------------------------- */

	/**
	 * Display an error message in the error container and trigger
	 * the card shake animation.
	 *
	 * @param {string} message Error message (will be escaped).
	 */
	function showError( message ) {
		if ( ! errorContainer ) {
			return;
		}

		errorContainer.innerHTML = '<div class="rf-error-message">' + escapeHtml( message ) + '</div>';
		errorContainer.classList.remove( 'hidden' );
		errorContainer.removeAttribute( 'aria-hidden' );

		/* Shake animation on the card */
		shakeElement( card );
	}

	/**
	 * Clear all error messages and hide the error container.
	 */
	function clearErrors() {
		if ( ! errorContainer ) {
			return;
		}

		errorContainer.innerHTML = '';
		errorContainer.classList.add( 'hidden' );
		errorContainer.setAttribute( 'aria-hidden', 'true' );
	}

	/* ---------------------------------------------------------------
	 * Helper: Success State
	 * ------------------------------------------------------------- */

	/**
	 * Apply the success glow animation to the login card.
	 */
	function showSuccess() {
		if ( card ) {
			card.classList.add( 'rf-success-glow' );
		}
	}

	/* ---------------------------------------------------------------
	 * Helper: Shake Animation
	 * ------------------------------------------------------------- */

	/**
	 * Apply a CSS shake animation to an element. Removes the class
	 * after the animation completes so it can be re-triggered.
	 *
	 * @param {HTMLElement} el Element to shake.
	 */
	function shakeElement( el ) {
		if ( ! el ) {
			return;
		}

		el.classList.remove( 'rf-shake' );

		/* Force reflow so re-adding the class restarts the animation */
		void el.offsetWidth;

		el.classList.add( 'rf-shake' );

		el.addEventListener( 'animationend', function onEnd() {
			el.classList.remove( 'rf-shake' );
			el.removeEventListener( 'animationend', onEnd );
		});
	}

	/* ---------------------------------------------------------------
	 * Helper: AJAX POST
	 * ------------------------------------------------------------- */

	/**
	 * Send a POST request to the WordPress AJAX endpoint.
	 *
	 * Builds a FormData object, appends the action parameter and the
	 * security nonce, then sends via fetch. Returns parsed JSON on
	 * success or a standardised error object on network failure.
	 *
	 * @param {string} action WordPress AJAX action name.
	 * @param {Object} data   Key/value pairs to include in the request.
	 * @return {Promise<Object>} Parsed JSON response.
	 */
	async function ajaxPost( action, data ) {
		var formData = new FormData();

		formData.append( 'action', action );

		if ( data && typeof data === 'object' ) {
			Object.keys( data ).forEach( function ( key ) {
				formData.append( key, data[ key ] );
			});
		}

		try {
			var response = await fetch( config.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData
			});

			if ( ! response.ok ) {
				return {
					status: 'error',
					message: i18n.networkError || 'A network error occurred. Please try again.'
				};
			}

			return await response.json();
		} catch ( e ) {
			return {
				status: 'error',
				message: i18n.networkError || 'A network error occurred. Please try again.'
			};
		}
	}

	/* ---------------------------------------------------------------
	 * 2FA Transition: Show Digit Inputs
	 * ------------------------------------------------------------- */

	/**
	 * Transition the form from the password step to the 2FA code
	 * entry step. Stores the pending session token, fades out the
	 * password group, reveals the 2FA digit inputs, and focuses
	 * the first digit.
	 *
	 * @param {string} token The 2FA pending session token.
	 */
	function show2FAInputs( token ) {
		/* Store the token */
		if ( tfaTokenInput ) {
			tfaTokenInput.value = token;
		}

		/* Reset attempt counter */
		tfaAttempts = 0;

		/* Fade out the password group */
		if ( passwordGroup ) {
			passwordGroup.classList.add( 'rf-fade-out' );

			setTimeout( function () {
				passwordGroup.classList.add( 'hidden' );
				passwordGroup.classList.remove( 'rf-fade-out' );
			}, 300 );
		}

		/* Hide remember-me row */
		if ( rememberRow ) {
			setTimeout( function () {
				rememberRow.classList.add( 'hidden' );
			}, 300 );
		}

		/* Show 2FA group after password fades */
		setTimeout( function () {
			if ( tfaGroup ) {
				tfaGroup.classList.remove( 'hidden' );
				tfaGroup.classList.add( 'rf-fade-in-delayed' );
			}

			/* Update button text */
			if ( submitBtnText ) {
				submitBtnText.setAttribute( 'data-default', i18n.verifying ? i18n.verifying.replace( '...', '' ) : 'Verify' );
				submitBtnText.textContent = 'Verify';
			}

			/* Focus first digit */
			if ( tfaDigits.length > 0 ) {
				tfaDigits[0].focus();
			}
		}, 350 );
	}

	/* ---------------------------------------------------------------
	 * 2FA Transition: Show Setup UI
	 * ------------------------------------------------------------- */

	/**
	 * Transition the form to show the 2FA setup interface, including
	 * a QR code and manual secret key, when a user needs to configure
	 * their authenticator app before proceeding.
	 *
	 * @param {Object} data Response data containing qr_url, secret, and token.
	 */
	function show2FASetup( data ) {
		/* Store the token */
		if ( tfaTokenInput ) {
			tfaTokenInput.value = data.token || '';
		}

		/* Hide password group and remember-me */
		if ( passwordGroup ) {
			passwordGroup.classList.add( 'rf-fade-out' );
			setTimeout( function () {
				passwordGroup.classList.add( 'hidden' );
				passwordGroup.classList.remove( 'rf-fade-out' );
			}, 300 );
		}

		if ( rememberRow ) {
			setTimeout( function () {
				rememberRow.classList.add( 'hidden' );
			}, 300 );
		}

		/* Hide the default submit button */
		if ( submitBtn ) {
			setTimeout( function () {
				submitBtn.classList.add( 'hidden' );
			}, 300 );
		}

		/* Format the secret in groups of 4 characters */
		var secretFormatted = '';
		if ( data.secret ) {
			secretFormatted = data.secret.replace( /(.{4})/g, '$1 ' ).trim();
		}

		/* Build the setup UI */
		if ( tfaSetupWrap ) {
			tfaSetupWrap.innerHTML =
				'<div class="rf-2fa-setup-inner rf-fade-in-delayed">' +
					'<h3 class="rf-2fa-setup-title">' +
						escapeHtml( 'Set Up Two-Factor Authentication' ) +
					'</h3>' +
					'<p class="rf-2fa-setup-desc">' +
						escapeHtml( 'Scan the QR code below with your authenticator app (Google Authenticator, Authy, etc.), then enter the 6-digit code to verify.' ) +
					'</p>' +
					'<div class="rf-2fa-qr-wrap">' +
						'<img src="' + escapeHtml( data.qr_url || '' ) + '" alt="' + escapeHtml( 'QR Code for authenticator app' ) + '" class="rf-2fa-qr-img" width="200" height="200" />' +
					'</div>' +
					'<div class="rf-2fa-manual-key">' +
						'<span class="rf-2fa-manual-label">' + escapeHtml( 'Manual entry key:' ) + '</span>' +
						'<code class="rf-2fa-secret-code">' + escapeHtml( secretFormatted ) + '</code>' +
					'</div>' +
					'<div class="rf-2fa-setup-verify">' +
						'<label for="rf-2fa-setup-code" class="rf-input-label">' +
							escapeHtml( 'Verification Code' ) +
						'</label>' +
						'<input type="text" id="rf-2fa-setup-code" class="rf-input rf-2fa-setup-input" maxlength="6" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" placeholder="000000" />' +
						'<input type="hidden" id="rf-2fa-setup-secret" value="' + escapeHtml( data.secret || '' ) + '" />' +
						'<button type="button" id="rf-2fa-setup-submit" class="rf-btn rf-btn-primary rf-2fa-setup-btn">' +
							escapeHtml( 'Verify & Activate' ) +
						'</button>' +
					'</div>' +
				'</div>';

			setTimeout( function () {
				tfaSetupWrap.classList.remove( 'hidden' );
			}, 350 );

			/* Attach the setup handler */
			init2FASetupHandler();
		}
	}

	/* ---------------------------------------------------------------
	 * 2FA Digit Input Management
	 * ------------------------------------------------------------- */

	/**
	 * Initialise event listeners for the 6 individual 2FA digit
	 * inputs. Handles typed input, backspace navigation, paste
	 * distribution, and auto-submit when all digits are filled.
	 */
	function init2FADigitInputs() {
		if ( ! tfaDigits || tfaDigits.length === 0 ) {
			return;
		}

		tfaDigits.forEach( function ( input, index ) {
			/* On input: strip non-digits, keep single char, auto-advance */
			input.addEventListener( 'input', function () {
				/* Strip anything that is not a digit */
				this.value = this.value.replace( /[^0-9]/g, '' );

				/* Keep only the last character if multiple were entered */
				if ( this.value.length > 1 ) {
					this.value = this.value.charAt( this.value.length - 1 );
				}

				/* Auto-advance to next input */
				if ( this.value.length === 1 && index < tfaDigits.length - 1 ) {
					tfaDigits[ index + 1 ].focus();
				}

				/* Check if all digits are filled */
				checkAutoSubmit();
			});

			/* On keydown: handle backspace navigation */
			input.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Backspace' && this.value === '' && index > 0 ) {
					e.preventDefault();
					tfaDigits[ index - 1 ].value = '';
					tfaDigits[ index - 1 ].focus();
				}

				/* Allow Enter to trigger manual submit */
				if ( e.key === 'Enter' ) {
					e.preventDefault();
					var code = collectDigits();
					if ( code.length === 6 ) {
						submit2FACode( code );
					}
				}
			});

			/* On paste: spread pasted digits across inputs */
			input.addEventListener( 'paste', function ( e ) {
				e.preventDefault();

				var pasteData = ( e.clipboardData || window.clipboardData ).getData( 'text' );
				var digits    = pasteData.replace( /[^0-9]/g, '' );

				if ( ! digits ) {
					return;
				}

				/* Spread digits starting from the current input */
				var pos = index;
				for ( var i = 0; i < digits.length && pos < tfaDigits.length; i++, pos++ ) {
					tfaDigits[ pos ].value = digits.charAt( i );
				}

				/* Focus the last filled input, or the next empty one */
				var focusIndex = Math.min( index + digits.length, tfaDigits.length ) - 1;
				tfaDigits[ focusIndex ].focus();

				/* Check for auto-submit */
				checkAutoSubmit();
			});

			/* On focus: select existing content for easy overwrite */
			input.addEventListener( 'focus', function () {
				this.select();
			});
		});
	}

	/**
	 * Collect digits from all 2FA inputs into a single string.
	 *
	 * @return {string} The concatenated 6-digit code.
	 */
	function collectDigits() {
		var code = '';
		tfaDigits.forEach( function ( input ) {
			code += input.value;
		});
		return code;
	}

	/**
	 * Check if all 6 digits are filled and auto-submit if so.
	 */
	function checkAutoSubmit() {
		var code = collectDigits();
		if ( code.length === 6 ) {
			submit2FACode( code );
		}
	}

	/**
	 * Clear all 2FA digit inputs and focus the first one.
	 */
	function clearDigits() {
		tfaDigits.forEach( function ( input ) {
			input.value = '';
		});
		if ( tfaDigits.length > 0 ) {
			tfaDigits[0].focus();
		}
	}

	/* ---------------------------------------------------------------
	 * 2FA Code Submission
	 * ------------------------------------------------------------- */

	/**
	 * Submit the 2FA TOTP code to the server for verification.
	 *
	 * On success, shows the success glow and redirects. On error,
	 * shakes the digit group and clears the inputs. After exceeding
	 * the maximum attempts, shows an expiry message and reloads.
	 *
	 * @param {string} code The 6-digit TOTP code.
	 */
	async function submit2FACode( code ) {
		if ( ! code || code.length !== 6 ) {
			return;
		}

		clearErrors();
		setLoading( true, i18n.verifying || 'Verifying...' );

		var token = tfaTokenInput ? tfaTokenInput.value : '';

		var result = await ajaxPost( 'rf_verify_2fa', {
			rf_2fa_token: token,
			code: code,
			security: config.nonce
		});

		setLoading( false );

		if ( result.status === 'success' ) {
			showSuccess();
			setTimeout( function () {
				window.location.href = result.redirect || config.adminUrl || '/wp-admin/';
			}, 500 );
			return;
		}

		/* Increment attempt counter */
		tfaAttempts++;

		if ( tfaAttempts >= MAX_TFA_ATTEMPTS || result.code === 'session_expired' ) {
			showError( i18n.tooManyAttempts || 'Too many failed attempts. Please log in again.' );
			setTimeout( function () {
				window.location.href = config.loginUrl || window.location.href;
			}, 2000 );
			return;
		}

		/* Show error and reset inputs */
		showError( result.message || i18n.invalidCode || 'Invalid verification code. Please try again.' );
		shakeElement( tfaGroup );
		clearDigits();
	}

	/* ---------------------------------------------------------------
	 * Backup Code Toggle
	 * ------------------------------------------------------------- */

	/**
	 * Initialise the toggle between authenticator code input and
	 * backup code input. Clicking "Use a backup code" hides the
	 * digit inputs and shows the backup code text field. Clicking
	 * "Use authenticator code" reverses the swap.
	 */
	function initBackupCodeToggle() {
		if ( backupToggle ) {
			backupToggle.addEventListener( 'click', function ( e ) {
				e.preventDefault();

				/* Hide 2FA digits, show backup input */
				if ( tfaGroup ) {
					tfaGroup.querySelector( '.rf-2fa-digits-wrap' ) &&
						tfaGroup.querySelector( '.rf-2fa-digits-wrap' ).classList.add( 'hidden' );
				}
				if ( backupGroup ) {
					backupGroup.classList.remove( 'hidden' );
					backupGroup.classList.add( 'rf-fade-in-delayed' );
				}
				if ( backupInput ) {
					backupInput.focus();
				}

				/* Swap toggle visibility */
				this.classList.add( 'hidden' );
				if ( authToggle ) {
					authToggle.classList.remove( 'hidden' );
				}
			});
		}

		if ( authToggle ) {
			authToggle.addEventListener( 'click', function ( e ) {
				e.preventDefault();

				/* Hide backup input, show 2FA digits */
				if ( backupGroup ) {
					backupGroup.classList.add( 'hidden' );
					backupGroup.classList.remove( 'rf-fade-in-delayed' );
				}
				if ( tfaGroup ) {
					tfaGroup.querySelector( '.rf-2fa-digits-wrap' ) &&
						tfaGroup.querySelector( '.rf-2fa-digits-wrap' ).classList.remove( 'hidden' );
				}

				/* Swap toggle visibility */
				this.classList.add( 'hidden' );
				if ( backupToggle ) {
					backupToggle.classList.remove( 'hidden' );
				}

				/* Focus first digit */
				if ( tfaDigits.length > 0 ) {
					tfaDigits[0].focus();
				}
			});
		}

		/* Backup code submission via Enter key */
		if ( backupInput ) {
			backupInput.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Enter' ) {
					e.preventDefault();
					var code = this.value.trim();
					if ( code ) {
						submitBackupCode( code );
					}
				}
			});
		}

		/* Backup code submission via button */
		var backupSubmitBtn = document.getElementById( 'rf-backup-submit' );
		if ( backupSubmitBtn ) {
			backupSubmitBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var code = backupInput ? backupInput.value.trim() : '';
				if ( code ) {
					submitBackupCode( code );
				}
			});
		}
	}

	/* ---------------------------------------------------------------
	 * Backup Code Submission
	 * ------------------------------------------------------------- */

	/**
	 * Submit a backup code to the server for verification.
	 *
	 * Uses the same AJAX endpoint as TOTP codes but with a
	 * type parameter set to 'backup'.
	 *
	 * @param {string} code The backup code.
	 */
	async function submitBackupCode( code ) {
		if ( ! code ) {
			return;
		}

		clearErrors();
		setLoading( true, i18n.verifying || 'Verifying...' );

		var token = tfaTokenInput ? tfaTokenInput.value : '';

		var result = await ajaxPost( 'rf_verify_2fa', {
			rf_2fa_token: token,
			code: code,
			type: 'backup',
			security: config.nonce
		});

		setLoading( false );

		if ( result.status === 'success' ) {
			showSuccess();
			setTimeout( function () {
				window.location.href = result.redirect || config.adminUrl || '/wp-admin/';
			}, 500 );
			return;
		}

		/* Increment attempt counter */
		tfaAttempts++;

		if ( tfaAttempts >= MAX_TFA_ATTEMPTS || result.code === 'session_expired' ) {
			showError( i18n.tooManyAttempts || 'Too many failed attempts. Please log in again.' );
			setTimeout( function () {
				window.location.href = config.loginUrl || window.location.href;
			}, 2000 );
			return;
		}

		showError( result.message || i18n.invalidCode || 'Invalid backup code. Please try again.' );
		shakeElement( backupGroup );

		if ( backupInput ) {
			backupInput.value = '';
			backupInput.focus();
		}
	}

	/* ---------------------------------------------------------------
	 * 2FA Setup Handler
	 * ------------------------------------------------------------- */

	/**
	 * Initialise the event handler for the 2FA setup verification
	 * flow. When the user scans the QR code and enters a verification
	 * code, this submits it to activate 2FA and then shows backup codes.
	 */
	function init2FASetupHandler() {
		var setupCodeInput = document.getElementById( 'rf-2fa-setup-code' );
		var setupSubmitBtn = document.getElementById( 'rf-2fa-setup-submit' );
		var setupSecret    = document.getElementById( 'rf-2fa-setup-secret' );

		if ( ! setupSubmitBtn || ! setupCodeInput ) {
			return;
		}

		/**
		 * Handle the setup verification submission.
		 */
		async function handleSetupSubmit() {
			var code   = setupCodeInput.value.replace( /[^0-9]/g, '' );
			var secret = setupSecret ? setupSecret.value : '';
			var token  = tfaTokenInput ? tfaTokenInput.value : '';

			if ( code.length !== 6 ) {
				showError( 'Please enter a valid 6-digit code.' );
				setupCodeInput.focus();
				return;
			}

			clearErrors();
			setupSubmitBtn.disabled = true;
			setupSubmitBtn.textContent = i18n.verifying || 'Verifying...';

			var result = await ajaxPost( 'rf_setup_2fa', {
				rf_2fa_token: token,
				code: code,
				secret: secret,
				security: config.nonce
			});

			setupSubmitBtn.disabled = false;
			setupSubmitBtn.textContent = 'Verify & Activate';

			if ( result.status === 'success' ) {
				/* Show backup codes */
				showBackupCodes( result.backup_codes || [], result.redirect || config.adminUrl || '/wp-admin/' );
				return;
			}

			showError( result.message || i18n.invalidCode || 'Invalid verification code. Please try again.' );
			setupCodeInput.value = '';
			setupCodeInput.focus();
		}

		setupSubmitBtn.addEventListener( 'click', handleSetupSubmit );

		setupCodeInput.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' ) {
				e.preventDefault();
				handleSetupSubmit();
			}
		});
	}

	/**
	 * Display the backup codes after successful 2FA setup, with a
	 * confirmation button that redirects to the dashboard.
	 *
	 * @param {Array}  codes       Array of backup code strings.
	 * @param {string} redirectUrl URL to redirect to after acknowledgement.
	 */
	function showBackupCodes( codes, redirectUrl ) {
		if ( ! tfaSetupWrap ) {
			return;
		}

		var codesHtml = '';
		if ( codes.length > 0 ) {
			codesHtml = '<ul class="rf-backup-codes-list">';
			codes.forEach( function ( code ) {
				codesHtml += '<li class="rf-backup-code-item"><code>' + escapeHtml( code ) + '</code></li>';
			});
			codesHtml += '</ul>';
		}

		tfaSetupWrap.innerHTML =
			'<div class="rf-2fa-setup-inner rf-fade-in-delayed">' +
				'<div class="rf-2fa-success-icon">' +
					'<svg xmlns="http://www.w3.org/2000/svg" class="rf-icon-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
						'<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>' +
						'<polyline points="22 4 12 14.01 9 11.01"/>' +
					'</svg>' +
				'</div>' +
				'<h3 class="rf-2fa-setup-title">' +
					escapeHtml( 'Two-Factor Authentication Activated' ) +
				'</h3>' +
				'<p class="rf-2fa-setup-desc">' +
					escapeHtml( 'Save these backup codes in a secure location. Each code can only be used once if you lose access to your authenticator app.' ) +
				'</p>' +
				codesHtml +
				'<button type="button" id="rf-backup-acknowledge" class="rf-btn rf-btn-primary rf-2fa-setup-btn">' +
					escapeHtml( "I've saved these codes" ) +
				'</button>' +
			'</div>';

		/* Handle acknowledgement click */
		var ackBtn = document.getElementById( 'rf-backup-acknowledge' );
		if ( ackBtn ) {
			ackBtn.addEventListener( 'click', function () {
				showSuccess();
				setTimeout( function () {
					window.location.href = redirectUrl;
				}, 500 );
			});
		}
	}

	/* ---------------------------------------------------------------
	 * Main Form Submit Handler (AJAX Login)
	 * ------------------------------------------------------------- */

	if ( form ) {
		/* Store the default button text for reset */
		if ( submitBtnText ) {
			submitBtnText.setAttribute( 'data-default', submitBtnText.textContent );
		}

		form.addEventListener( 'submit', async function ( e ) {
			e.preventDefault();

			clearErrors();
			setLoading( true, i18n.authenticating || 'Authenticating...' );

			/* Collect form data */
			var username    = form.querySelector( '[name="log"]' );
			var password    = form.querySelector( '[name="pwd"]' );
			var nonce       = form.querySelector( '[name="rf_login_nonce_field"]' );
			var redirectTo  = form.querySelector( '[name="redirect_to"]' );
			var rememberMe  = form.querySelector( '[name="rememberme"]' );

			var payload = {
				log: username ? username.value : '',
				pwd: password ? password.value : '',
				rf_login_nonce_field: nonce ? nonce.value : '',
				redirect_to: redirectTo ? redirectTo.value : '',
				rememberme: rememberMe && rememberMe.checked ? 'forever' : '',
				security: config.nonce
			};

			/* reCAPTCHA v3 token (if configured) */
			if ( config.recaptchaKey && typeof grecaptcha !== 'undefined' ) {
				try {
					var recaptchaToken = await grecaptcha.execute( config.recaptchaKey, { action: 'login' } );
					payload.g_recaptcha_response = recaptchaToken;
				} catch ( recaptchaError ) {
					/* Silently continue without reCAPTCHA — server will handle validation */
				}
			}

			var result = await ajaxPost( 'rf_login', payload );

			setLoading( false );

			switch ( result.status ) {
				case 'success':
					showSuccess();
					setTimeout( function () {
						window.location.href = result.redirect || config.adminUrl || '/wp-admin/';
					}, 500 );
					break;

				case '2fa_required':
					show2FAInputs( result.token || '' );
					break;

				case '2fa_setup_required':
					show2FASetup( result );
					break;

				case 'error':
				default:
					showError( result.message || i18n.networkError || 'An error occurred. Please try again.' );
					break;
			}
		});
	}

	/* ---------------------------------------------------------------
	 * Password Reset Form Validation
	 * ------------------------------------------------------------- */

	var resetPassForm = document.getElementById( 'rf-resetpass-form' );

	if ( resetPassForm ) {
		resetPassForm.addEventListener( 'submit', function ( e ) {
			var pass1 = resetPassForm.querySelector( '[name="pass1"]' );
			var pass2 = resetPassForm.querySelector( '[name="pass2"]' );

			if ( pass1 && pass2 && pass1.value !== pass2.value ) {
				e.preventDefault();
				clearErrors();
				showError( 'Passwords do not match. Please try again.' );
				pass2.focus();
				return;
			}

			if ( pass1 && ! pass1.value ) {
				e.preventDefault();
				clearErrors();
				showError( 'Please enter a new password.' );
				pass1.focus();
			}
		});

		/* Live validation: show mismatch feedback as user types */
		var resetPass1 = resetPassForm.querySelector( '[name="pass1"]' );
		var resetPass2 = resetPassForm.querySelector( '[name="pass2"]' );

		if ( resetPass1 && resetPass2 ) {
			resetPass2.addEventListener( 'input', function () {
				if ( this.value && resetPass1.value && this.value !== resetPass1.value ) {
					this.classList.add( 'rf-input-error' );
				} else {
					this.classList.remove( 'rf-input-error' );
				}
			});
		}
	}

	/* ---------------------------------------------------------------
	 * Lost Password Form Submit Handler
	 * ------------------------------------------------------------- */

	var lostPassForm = document.getElementById( 'rf-lostpassword-form' );

	if ( lostPassForm && submitBtn ) {
		if ( submitBtnText && ! submitBtnText.getAttribute( 'data-default' ) ) {
			submitBtnText.setAttribute( 'data-default', submitBtnText.textContent );
		}
	}

	/* ---------------------------------------------------------------
	 * Initialise
	 * ------------------------------------------------------------- */

	init2FADigitInputs();
	initBackupCodeToggle();

})();
