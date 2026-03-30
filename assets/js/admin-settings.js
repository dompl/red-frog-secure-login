/**
 * Red Frog Secure Login — Admin Settings JavaScript.
 *
 * Handles tab switching, media uploader, range sliders,
 * reCAPTCHA toggle, users table AJAX, and 2FA reset modal.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */

( function() {
	'use strict';

	/**
	 * Configuration from wp_localize_script.
	 *
	 * @type {Object}
	 */
	const config = window.rfAdminSettings || {};

	/* ======================================================================
	   Tab Switching
	   ====================================================================== */

	/**
	 * Initialise tab navigation.
	 */
	function initTabs() {
		const tabs = document.querySelectorAll( '.rf-tab' );
		const panels = document.querySelectorAll( '.rf-tab-content' );
		const saveBar = document.getElementById( 'rf-save-bar' );
		const form = document.getElementById( 'rf-settings-form' );

		tabs.forEach( function( tab ) {
			tab.addEventListener( 'click', function() {
				const target = tab.getAttribute( 'data-tab' );

				// Update tab active states.
				tabs.forEach( function( t ) {
					t.classList.remove( 'active' );
					t.setAttribute( 'aria-selected', 'false' );
				} );
				tab.classList.add( 'active' );
				tab.setAttribute( 'aria-selected', 'true' );

				// Show/hide panels.
				panels.forEach( function( panel ) {
					if ( panel.id === 'tab-' + target ) {
						panel.style.display = '';
						panel.classList.add( 'active' );
					} else {
						panel.style.display = 'none';
						panel.classList.remove( 'active' );
					}
				} );

				// Show save bar only for form tabs.
				const formTabs = [ 'general', 'security' ];
				if ( saveBar ) {
					saveBar.style.display = formTabs.indexOf( target ) !== -1 ? '' : 'none';
				}
				if ( form ) {
					form.style.display = formTabs.indexOf( target ) !== -1 ? '' : 'none';
				}

				// Load users when switching to users tab.
				if ( 'users' === target ) {
					loadUsersTable();
				}

				// Re-initialise Flowbite after DOM changes.
				tryInitFlowbite();
			} );
		} );
	}

	/* ======================================================================
	   Media Uploader (Logo)
	   ====================================================================== */

	/**
	 * Initialise the WordPress media uploader for logo selection.
	 */
	function initMediaUploader() {
		const uploadBtn = document.getElementById( 'rf-upload-logo-btn' );
		const removeBtn = document.getElementById( 'rf-remove-logo-btn' );
		const hiddenInput = document.getElementById( 'rf_login_logo' );
		const previewContainer = document.getElementById( 'rf-logo-preview' );

		if ( ! uploadBtn || ! hiddenInput || ! previewContainer ) {
			return;
		}

		let mediaFrame = null;

		uploadBtn.addEventListener( 'click', function( e ) {
			e.preventDefault();

			// If frame already exists, reopen.
			if ( mediaFrame ) {
				mediaFrame.open();
				return;
			}

			// Create a new media frame.
			mediaFrame = wp.media( {
				title: 'Select Login Logo',
				button: {
					text: 'Use as Logo',
				},
				multiple: false,
				library: {
					type: 'image',
				},
			} );

			// When an image is selected.
			mediaFrame.on( 'select', function() {
				const attachment = mediaFrame.state().get( 'selection' ).first().toJSON();

				// Update hidden input.
				hiddenInput.value = attachment.id;

				// Update preview.
				updateLogoPreview( attachment.url );

				// Show remove button.
				if ( removeBtn ) {
					removeBtn.style.display = '';
				}
			} );

			mediaFrame.open();
		} );

		// Remove logo.
		if ( removeBtn ) {
			removeBtn.addEventListener( 'click', function( e ) {
				e.preventDefault();

				hiddenInput.value = '0';
				clearLogoPreview();
				removeBtn.style.display = 'none';
			} );
		}
	}

	/**
	 * Update the logo preview area with an image.
	 *
	 * @param {string} url The image URL.
	 */
	function updateLogoPreview( url ) {
		const previewContainer = document.getElementById( 'rf-logo-preview' );
		if ( ! previewContainer ) {
			return;
		}

		let img = previewContainer.querySelector( 'img' );
		const placeholder = document.getElementById( 'rf-logo-placeholder' );

		if ( placeholder ) {
			placeholder.style.display = 'none';
		}

		if ( ! img ) {
			img = document.createElement( 'img' );
			img.id = 'rf-logo-preview-img';
			img.alt = 'Login logo preview';
			previewContainer.appendChild( img );
		}

		img.src = url;
		img.style.display = '';
	}

	/**
	 * Clear the logo preview area.
	 */
	function clearLogoPreview() {
		const previewContainer = document.getElementById( 'rf-logo-preview' );
		if ( ! previewContainer ) {
			return;
		}

		const img = previewContainer.querySelector( 'img' );
		if ( img ) {
			img.style.display = 'none';
			img.src = '';
		}

		const placeholder = document.getElementById( 'rf-logo-placeholder' );
		if ( placeholder ) {
			placeholder.style.display = '';
		}
	}

	/* ======================================================================
	   Range Sliders
	   ====================================================================== */

	/**
	 * Initialise range sliders to update adjacent value displays.
	 */
	function initRangeSliders() {
		const particleRange = document.getElementById( 'rf_particle_count' );
		const particleValue = document.getElementById( 'rf-particle-value' );

		if ( particleRange && particleValue ) {
			particleRange.addEventListener( 'input', function() {
				particleValue.textContent = particleRange.value;
			} );
		}

		const thresholdRange = document.getElementById( 'rf_recaptcha_threshold' );
		const thresholdValue = document.getElementById( 'rf-threshold-value' );

		if ( thresholdRange && thresholdValue ) {
			thresholdRange.addEventListener( 'input', function() {
				thresholdValue.textContent = parseFloat( thresholdRange.value ).toFixed( 1 );
			} );
		}
	}

	/* ======================================================================
	   reCAPTCHA Toggle
	   ====================================================================== */

	/**
	 * Toggle reCAPTCHA fields enabled/disabled state.
	 */
	function initRecaptchaToggle() {
		const toggle = document.getElementById( 'rf_recaptcha_enabled' );
		const fieldsContainer = document.getElementById( 'rf-recaptcha-fields' );

		if ( ! toggle || ! fieldsContainer ) {
			return;
		}

		function updateFields() {
			const isEnabled = toggle.checked;
			const inputs = fieldsContainer.querySelectorAll( 'input:not([type="hidden"]):not([type="checkbox"])' );

			if ( isEnabled ) {
				fieldsContainer.removeAttribute( 'data-disabled' );
				inputs.forEach( function( input ) {
					input.removeAttribute( 'disabled' );
				} );
			} else {
				fieldsContainer.setAttribute( 'data-disabled', 'true' );
				inputs.forEach( function( input ) {
					input.setAttribute( 'disabled', '' );
				} );
			}
		}

		toggle.addEventListener( 'change', updateFields );
	}

	/* ======================================================================
	   Users Table (AJAX)
	   ====================================================================== */

	/** Whether users have been loaded at least once. */
	let usersLoaded = false;

	/** Timeout ID for search debounce. */
	let searchTimeout = null;

	/**
	 * Load the users table via AJAX.
	 *
	 * @param {string} [search=''] Optional search term.
	 */
	function loadUsersTable( search ) {
		search = search || '';

		const tbody = document.getElementById( 'rf-users-tbody' );
		if ( ! tbody ) {
			return;
		}

		// Show loading state.
		tbody.innerHTML = '<tr><td colspan="5" class="rf-table-loading">' +
			'<div class="rf-loading-spinner"></div> Loading users...</td></tr>';

		const data = new FormData();
		data.append( 'action', 'rf_get_users' );
		data.append( '_ajax_nonce', config.nonce );
		if ( search ) {
			data.append( 'search', search );
		}

		fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data,
		} )
		.then( function( response ) {
			return response.json();
		} )
		.then( function( result ) {
			if ( result.success && result.data && Array.isArray( result.data.users ) ) {
				renderUsersTable( result.data.users );
				usersLoaded = true;
			} else {
				tbody.innerHTML = '<tr><td colspan="5" class="rf-table-loading">' +
					'No users found.</td></tr>';
			}
		} )
		.catch( function() {
			tbody.innerHTML = '<tr><td colspan="5" class="rf-table-loading">' +
				'Failed to load users. Please refresh the page.</td></tr>';
		} );
	}

	/**
	 * Render users into the table body.
	 *
	 * @param {Array} users Array of user objects.
	 */
	function renderUsersTable( users ) {
		const tbody = document.getElementById( 'rf-users-tbody' );
		if ( ! tbody ) {
			return;
		}

		if ( 0 === users.length ) {
			tbody.innerHTML = '<tr><td colspan="5" class="rf-table-loading">' +
				'No users found.</td></tr>';
			return;
		}

		let html = '';

		users.forEach( function( user ) {
			const badgeClass = user.twofa_enabled ? 'rf-badge-active' : 'rf-badge-inactive';
			const badgeText = user.twofa_enabled ? 'Active' : 'Inactive';
			const escapedName = escapeHtml( user.display_name );
			const escapedEmail = escapeHtml( user.email );
			const escapedRole = escapeHtml( user.role );

			html += '<tr data-user-id="' + user.id + '">';
			html += '<td><strong>' + escapedName + '</strong></td>';
			html += '<td>' + escapedEmail + '</td>';
			html += '<td><span style="text-transform:capitalize;">' + escapedRole + '</span></td>';
			html += '<td><span class="' + badgeClass + '">' + badgeText + '</span></td>';
			html += '<td><div class="rf-user-actions">';

			if ( user.twofa_enabled ) {
				html += '<button type="button" class="rf-btn-danger rf-btn-sm rf-reset-2fa-btn" data-user-id="' + user.id + '" data-username="' + escapeHtml( user.display_name ) + '">';
				html += 'Reset 2FA</button>';
			}

			html += '<button type="button" class="rf-btn-ghost rf-btn-sm rf-regen-backup-btn" data-user-id="' + user.id + '">';
			html += 'Regen Backup</button>';

			html += '</div></td>';
			html += '</tr>';
		} );

		tbody.innerHTML = html;

		// Bind action buttons.
		bindUserActions();
	}

	/**
	 * Bind click events for user action buttons.
	 */
	function bindUserActions() {
		// Reset 2FA buttons.
		const resetBtns = document.querySelectorAll( '.rf-reset-2fa-btn' );
		resetBtns.forEach( function( btn ) {
			btn.addEventListener( 'click', function() {
				openResetModal( btn.getAttribute( 'data-user-id' ), btn.getAttribute( 'data-username' ) );
			} );
		} );

		// Regenerate backup codes buttons.
		const regenBtns = document.querySelectorAll( '.rf-regen-backup-btn' );
		regenBtns.forEach( function( btn ) {
			btn.addEventListener( 'click', function() {
				regenBackupCodes( btn.getAttribute( 'data-user-id' ), btn );
			} );
		} );
	}

	/**
	 * Initialise the users search input.
	 */
	function initUserSearch() {
		const searchInput = document.getElementById( 'rf-user-search' );
		if ( ! searchInput ) {
			return;
		}

		searchInput.addEventListener( 'input', function() {
			clearTimeout( searchTimeout );
			searchTimeout = setTimeout( function() {
				loadUsersTable( searchInput.value.trim() );
			}, 350 );
		} );
	}

	/**
	 * Regenerate backup codes for a user.
	 *
	 * @param {string|number} userId The user ID.
	 * @param {HTMLElement}   btn    The clicked button.
	 */
	function regenBackupCodes( userId, btn ) {
		const originalText = btn.textContent;
		btn.textContent = 'Regenerating...';
		btn.disabled = true;

		const data = new FormData();
		data.append( 'action', 'rf_regen_backup_codes' );
		data.append( '_ajax_nonce', config.nonce );
		data.append( 'user_id', userId );

		fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data,
		} )
		.then( function( response ) {
			return response.json();
		} )
		.then( function( result ) {
			if ( result.success ) {
				btn.textContent = 'Done!';
				btn.classList.remove( 'rf-btn-ghost' );
				btn.classList.add( 'rf-btn-secondary' );
				setTimeout( function() {
					btn.textContent = originalText;
					btn.disabled = false;
					btn.classList.remove( 'rf-btn-secondary' );
					btn.classList.add( 'rf-btn-ghost' );
				}, 2000 );
			} else {
				btn.textContent = 'Error';
				setTimeout( function() {
					btn.textContent = originalText;
					btn.disabled = false;
				}, 2000 );
			}
		} )
		.catch( function() {
			btn.textContent = originalText;
			btn.disabled = false;
		} );
	}

	/* ======================================================================
	   Reset 2FA Modal
	   ====================================================================== */

	/**
	 * Open the reset 2FA confirmation modal.
	 *
	 * @param {string|number} userId   The user ID.
	 * @param {string}        username The display name.
	 */
	function openResetModal( userId, username ) {
		const modal = document.getElementById( 'rf-reset-modal' );
		const usernameEl = document.getElementById( 'rf-modal-username' );
		const confirmBtn = document.getElementById( 'rf-modal-confirm' );

		if ( ! modal || ! confirmBtn ) {
			return;
		}

		if ( usernameEl ) {
			usernameEl.textContent = username || 'User #' + userId;
		}

		confirmBtn.setAttribute( 'data-user-id', userId );
		modal.style.display = '';
	}

	/**
	 * Close the reset 2FA modal.
	 */
	function closeResetModal() {
		const modal = document.getElementById( 'rf-reset-modal' );
		if ( modal ) {
			modal.style.display = 'none';
		}
	}

	/**
	 * Initialise reset modal event listeners.
	 */
	function initResetModal() {
		const cancelBtn = document.getElementById( 'rf-modal-cancel' );
		const closeBtn = document.getElementById( 'rf-modal-close' );
		const confirmBtn = document.getElementById( 'rf-modal-confirm' );
		const overlay = document.getElementById( 'rf-reset-modal' );

		if ( cancelBtn ) {
			cancelBtn.addEventListener( 'click', closeResetModal );
		}

		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', closeResetModal );
		}

		// Close on overlay click.
		if ( overlay ) {
			overlay.addEventListener( 'click', function( e ) {
				if ( e.target === overlay ) {
					closeResetModal();
				}
			} );
		}

		// Close on Escape.
		document.addEventListener( 'keydown', function( e ) {
			if ( 'Escape' === e.key && overlay && 'none' !== overlay.style.display ) {
				closeResetModal();
			}
		} );

		// Confirm reset.
		if ( confirmBtn ) {
			confirmBtn.addEventListener( 'click', function() {
				const userId = confirmBtn.getAttribute( 'data-user-id' );
				if ( ! userId ) {
					return;
				}

				confirmBtn.textContent = 'Resetting...';
				confirmBtn.disabled = true;

				const data = new FormData();
				data.append( 'action', 'rf_reset_user_2fa' );
				data.append( '_ajax_nonce', config.nonce );
				data.append( 'user_id', userId );

				fetch( config.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: data,
				} )
				.then( function( response ) {
					return response.json();
				} )
				.then( function( result ) {
					closeResetModal();

					// Reset button text.
					confirmBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd"/></svg> Reset 2FA';
					confirmBtn.disabled = false;

					if ( result.success ) {
						// Refresh the users table.
						const searchInput = document.getElementById( 'rf-user-search' );
						const searchVal = searchInput ? searchInput.value.trim() : '';
						loadUsersTable( searchVal );
					}
				} )
				.catch( function() {
					closeResetModal();
					confirmBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd"/></svg> Reset 2FA';
					confirmBtn.disabled = false;
				} );
			} );
		}
	}

	/* ======================================================================
	   Accordion
	   ====================================================================== */

	/**
	 * Initialise accordion expand/collapse behaviour.
	 */
	function initAccordion() {
		const triggers = document.querySelectorAll( '.rf-accordion-trigger' );

		triggers.forEach( function( trigger ) {
			trigger.addEventListener( 'click', function() {
				const targetId = trigger.getAttribute( 'data-accordion' );
				const body = document.getElementById( targetId );

				if ( ! body ) {
					return;
				}

				const isOpen = trigger.classList.contains( 'active' );

				if ( isOpen ) {
					trigger.classList.remove( 'active' );
					trigger.setAttribute( 'aria-expanded', 'false' );
					body.style.display = 'none';
					body.classList.remove( 'active' );
				} else {
					trigger.classList.add( 'active' );
					trigger.setAttribute( 'aria-expanded', 'true' );
					body.style.display = '';
					body.classList.add( 'active' );
				}
			} );
		} );
	}

	/* ======================================================================
	   Utility Functions
	   ====================================================================== */

	/**
	 * Escape HTML entities in a string.
	 *
	 * @param {string} str Raw string.
	 * @return {string} Escaped string.
	 */
	function escapeHtml( str ) {
		if ( ! str ) {
			return '';
		}
		const div = document.createElement( 'div' );
		div.appendChild( document.createTextNode( str ) );
		return div.innerHTML;
	}

	/**
	 * Try to initialise Flowbite if it is loaded.
	 */
	function tryInitFlowbite() {
		if ( 'function' === typeof window.initFlowbite ) {
			window.initFlowbite();
		}
	}

	/* ======================================================================
	   Initialisation
	   ====================================================================== */

	/**
	 * Run all initialisers when the DOM is ready.
	 */
	function init() {
		initTabs();
		initMediaUploader();
		initRangeSliders();
		initRecaptchaToggle();
		initUserSearch();
		initResetModal();
		initAccordion();
		tryInitFlowbite();
	}

	// Wait for DOMContentLoaded.
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

} )();
